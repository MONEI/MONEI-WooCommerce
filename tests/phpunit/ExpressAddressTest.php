<?php
/**
 * Wallet address normalization for express checkout.
 *
 * A wallet hands back a display name for the state and its own spelling for every
 * other field. WooCommerce matches shipping zones and validates addresses on the
 * state code, so a state that fails to resolve turns into "no shipping method
 * available" or a rejected order — the single most common express checkout failure.
 *
 * The state fixtures are the real WooCommerce values, read from
 * `WC()->countries->get_states()` on WooCommerce 11.0.1.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Services\express\ExpressCheckoutAjaxHandler;
use PHPUnit\Framework\TestCase;

class ExpressAddressTest extends TestCase {

	const ES_STATES = array(
		'C'  => 'A Coruña',
		'VI' => 'Araba/Álava',
		'M'  => 'Madrid',
		'MA' => 'Málaga',
		'B'  => 'Barcelona',
	);

	const US_STATES = array(
		'CA' => 'California',
		'NY' => 'New York',
		'VA' => 'Virginia',
		'WA' => 'Washington',
		'WV' => 'West Virginia',
	);

	const IE_STATES = array(
		'CE' => 'Clare',
		'CW' => 'Carlow',
	);

	/**
	 * Portugal has no state list in WooCommerce, so nothing may be rewritten.
	 */
	const PT_STATES = array();

	public function test_spanish_province_name_resolves_to_its_code() {
		$this->assertSame( 'M', ExpressCheckoutAjaxHandler::normalize_state_code( 'Madrid', self::ES_STATES ) );
	}

	public function test_spanish_province_without_accents_resolves() {
		// Wallets routinely strip diacritics; WooCommerce stores "Málaga".
		$this->assertSame( 'MA', ExpressCheckoutAjaxHandler::normalize_state_code( 'Malaga', self::ES_STATES ) );
		$this->assertSame( 'C', ExpressCheckoutAjaxHandler::normalize_state_code( 'A Coruna', self::ES_STATES ) );
	}

	public function test_already_normalized_spanish_code_is_untouched() {
		// Single-letter Spanish codes must not be re-matched against province names.
		$this->assertSame( 'M', ExpressCheckoutAjaxHandler::normalize_state_code( 'M', self::ES_STATES ) );
	}

	public function test_portugal_has_no_state_codes_so_value_is_preserved() {
		// Rewriting or blanking this would fail WooCommerce address validation for a
		// country whose state field is free text.
		$this->assertSame( 'Lisboa', ExpressCheckoutAjaxHandler::normalize_state_code( 'Lisboa', self::PT_STATES ) );
	}

	public function test_us_state_name_resolves_to_its_code() {
		$this->assertSame( 'CA', ExpressCheckoutAjaxHandler::normalize_state_code( 'California', self::US_STATES ) );
	}

	public function test_us_state_code_in_lower_case_is_upper_cased() {
		$this->assertSame( 'CA', ExpressCheckoutAjaxHandler::normalize_state_code( 'ca', self::US_STATES ) );
	}

	public function test_state_name_containing_another_state_name_resolves_to_itself() {
		// A containment-only match resolves "West Virginia" to VA, because VA is listed
		// first. The exact-name pass has to run before containment.
		$this->assertSame( 'WV', ExpressCheckoutAjaxHandler::normalize_state_code( 'West Virginia', self::US_STATES ) );
	}

	public function test_decorated_county_name_resolves() {
		// Chrome's Irish county dropdown offers "Co. Clare".
		$this->assertSame( 'CE', ExpressCheckoutAjaxHandler::normalize_state_code( 'Co. Clare', self::IE_STATES ) );
	}

	public function test_unknown_state_is_returned_unchanged() {
		// Guessing here would silently ship to the wrong place; WooCommerce should be
		// the one to reject it.
		$this->assertSame( 'Atlantis', ExpressCheckoutAjaxHandler::normalize_state_code( 'Atlantis', self::US_STATES ) );
	}

	public function test_empty_state_stays_empty() {
		$this->assertSame( '', ExpressCheckoutAjaxHandler::normalize_state_code( '', self::ES_STATES ) );
	}

	public function test_monei_address_shape_maps_to_woocommerce_fields() {
		// monei.js hands back exactly these keys — verified against the v3 bundle.
		$mapped = ExpressCheckoutAjaxHandler::map_wallet_address(
			array(
				'name'    => 'Ada Lovelace',
				'email'   => 'ada@example.com',
				'phone'   => '600000000',
				'address' => array(
					'line1'   => 'Calle Mayor 1',
					'line2'   => '3B',
					'city'    => 'Madrid',
					'state'   => 'Madrid',
					'zip'     => '28013',
					'country' => 'es',
				),
			)
		);

		$this->assertSame( 'Ada', $mapped['first_name'] );
		$this->assertSame( 'Lovelace', $mapped['last_name'] );
		$this->assertSame( 'Calle Mayor 1', $mapped['address_1'] );
		$this->assertSame( '3B', $mapped['address_2'] );
		$this->assertSame( 'Madrid', $mapped['city'] );
		$this->assertSame( '28013', $mapped['postcode'] );
		$this->assertSame( 'ES', $mapped['country'] );
		$this->assertSame( 'ada@example.com', $mapped['email'] );
		$this->assertSame( '600000000', $mapped['phone'] );
	}

	public function test_partial_mid_flow_address_yields_empty_strings_not_notices() {
		// onShippingAddressChange gets country, city, state and postcode only.
		$mapped = ExpressCheckoutAjaxHandler::map_wallet_address(
			array(
				'country' => 'PT',
				'city'    => 'Lisboa',
				'state'   => '',
				'zip'     => '1000-001',
			)
		);

		$this->assertSame( 'PT', $mapped['country'] );
		$this->assertSame( '1000-001', $mapped['postcode'] );
		$this->assertSame( '', $mapped['address_1'] );
		$this->assertSame( '', $mapped['first_name'] );
	}

	public function test_alternative_wallet_spellings_are_accepted() {
		$mapped = ExpressCheckoutAjaxHandler::map_wallet_address(
			array(
				'givenName'    => 'Grace',
				'familyName'   => 'Hopper',
				'addressLine1' => '1 Main St',
				'locality'     => 'New York',
				'region'       => 'NY',
				'postalCode'   => '10001',
				'countryCode'  => 'US',
			)
		);

		$this->assertSame( 'Grace', $mapped['first_name'] );
		$this->assertSame( 'Hopper', $mapped['last_name'] );
		$this->assertSame( '1 Main St', $mapped['address_1'] );
		$this->assertSame( 'New York', $mapped['city'] );
		$this->assertSame( 'NY', $mapped['state'] );
		$this->assertSame( '10001', $mapped['postcode'] );
		$this->assertSame( 'US', $mapped['country'] );
	}

	public function test_single_word_name_becomes_the_first_name() {
		$mapped = ExpressCheckoutAjaxHandler::map_wallet_address( array( 'name' => 'Cher' ) );

		$this->assertSame( 'Cher', $mapped['first_name'] );
		$this->assertSame( '', $mapped['last_name'] );
	}

	public function test_multi_word_name_keeps_compound_surname_last() {
		$mapped = ExpressCheckoutAjaxHandler::map_wallet_address( array( 'name' => 'Ana Maria Ruiz' ) );

		$this->assertSame( 'Ana Maria', $mapped['first_name'] );
		$this->assertSame( 'Ruiz', $mapped['last_name'] );
	}

	public function test_explicit_name_fields_win_over_the_combined_name() {
		$mapped = ExpressCheckoutAjaxHandler::map_wallet_address(
			array(
				'name'       => 'Wrong Person',
				'first_name' => 'Ada',
				'last_name'  => 'Lovelace',
			)
		);

		$this->assertSame( 'Ada', $mapped['first_name'] );
		$this->assertSame( 'Lovelace', $mapped['last_name'] );
	}

	public function test_malformed_values_are_dropped_instead_of_crashing() {
		// A wallet returning an object where a string is expected must not produce an
		// array in a WooCommerce address field.
		$mapped = ExpressCheckoutAjaxHandler::map_wallet_address(
			array(
				'city'    => array( 'unexpected' => 'object' ),
				'address' => 'not-an-object',
				'state'   => '  Madrid  ',
				'country' => 'ES',
			)
		);

		$this->assertSame( '', $mapped['city'] );
		$this->assertSame( 'Madrid', $mapped['state'] );
		$this->assertSame( 'ES', $mapped['country'] );
	}

	public function test_empty_address_returns_every_field_as_an_empty_string() {
		$mapped = ExpressCheckoutAjaxHandler::map_wallet_address( array() );

		$this->assertSame(
			array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone' ),
			array_keys( $mapped )
		);
		$this->assertSame( array( '' ), array_values( array_unique( $mapped ) ) );
	}
}
