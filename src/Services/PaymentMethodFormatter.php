<?php
/**
 * Payment Method Formatter Service
 *
 * Formats payment method information for display in admin and customer-facing areas.
 * Similar to PrestaShop's PaymentMethodFormatter.
 *
 * @package Monei
 * @since 6.5.0
 */

namespace Monei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PaymentMethodFormatter
 */
class PaymentMethodFormatter {

	/**
	 * Card brand display names
	 */
	private const CARD_BRANDS = array(
		'visa'            => 'Visa',
		'mastercard'      => 'MasterCard',
		'amex'            => 'American Express',
		'americanexpress' => 'American Express',
		'discover'        => 'Discover',
		'dinersclub'      => 'Diners Club',
		'jcb'             => 'JCB',
		'unionpay'        => 'UnionPay',
		'maestro'         => 'Maestro',
	);

	/**
	 * Payment method display names
	 */
	private const PAYMENT_METHODS = array(
		'card'       => 'Card',
		'bizum'      => 'Bizum',
		'paypal'     => 'PayPal',
		'applepay'   => 'Apple Pay',
		'apple_pay'  => 'Apple Pay',
		'googlepay'  => 'Google Pay',
		'google_pay' => 'Google Pay',
		'multibanco' => 'Multibanco',
		'mbway'      => 'MB Way',
	);

	/**
	 * Format payment method display name
	 *
	 * @param string      $method Payment method (card, bizum, paypal, etc).
	 * @param string|null $brand  Card brand (visa, mastercard, etc).
	 * @return string
	 */
	public function format_payment_method_name( string $method, ?string $brand = null ): string {
		$method = strtolower( $method );

		if ( 'card' === $method && $brand ) {
			$brand = strtolower( $brand );
			return self::CARD_BRANDS[ $brand ] ?? ucfirst( $brand );
		}

		return self::PAYMENT_METHODS[ $method ] ?? ucfirst( $method );
	}

	/**
	 * Format card number with last 4 digits
	 *
	 * @param string|null $last4 Last 4 digits of card.
	 * @return string
	 */
	public function format_card_number( ?string $last4 ): string {
		if ( empty( $last4 ) ) {
			return '';
		}

		return '•••• ' . $last4;
	}

	/**
	 * Obfuscate email address for security
	 * Uses fixed-length dots to prevent length-based identification
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	public function obfuscate_email( string $email ): string {
		if ( empty( $email ) ) {
			return '';
		}

		$parts = explode( '@', $email );
		if ( 2 !== count( $parts ) ) {
			return $email;
		}

		$local_part = $parts[0];
		$domain     = $parts[1];

		$local_length = strlen( $local_part );

		// Security-focused obfuscation:
		// - Use fixed-length dots to prevent length-based guessing
		// - Show only first character to minimize information leakage
		if ( $local_length <= 1 ) {
			// Single character email - just show dots
			$obfuscated_local = '•••';
		} else {
			// Show only first character with fixed 3 dots
			$obfuscated_local = substr( $local_part, 0, 1 ) . '•••';
		}

		// Keep full domain visible for service identification
		return $obfuscated_local . '@' . $domain;
	}

	/**
	 * Format payment method display based on payment information
	 *
	 * Examples:
	 * - "Visa •••• 1234"
	 * - "Apple Pay •••• 5678"
	 * - "Bizum ••••1234"
	 * - "PayPal (a•••@gmail.com)"
	 *
	 * @param array $payment_info Flattened payment information array.
	 * @return string
	 */
	public function format_payment_method_display( array $payment_info ): string {
		$payment_method_display = '';
		$method_type            = $payment_info['method'] ?? '';

		// Check tokenizationMethod first for wallet payments (Apple Pay, Google Pay)
		if ( ! empty( $payment_info['tokenizationMethod'] ) ) {
			$tokenization_method = $payment_info['tokenizationMethod'];

			switch ( $tokenization_method ) {
				case 'applePay':
					$payment_method_display = 'Apple Pay';
					break;
				case 'googlePay':
					$payment_method_display = 'Google Pay';
					break;
			}

			// Add card details if available
			if ( $payment_method_display && ! empty( $payment_info['last4'] ) ) {
				$payment_method_display .= ' ' . $this->format_card_number( $payment_info['last4'] );
			}
		}

		// If no tokenization method or not a recognized wallet, use existing logic
		if ( ! $payment_method_display ) {
			if ( ! empty( $payment_info['brand'] ) ) {
				// Card payment display
				$brand                  = strtolower( $payment_info['brand'] );
				$payment_method_display = $this->format_payment_method_name( 'card', $brand );

				// Add card type inline if available
				if ( ! empty( $payment_info['type'] ) ) {
					$payment_method_display .= ' ' . ucfirst( $payment_info['type'] );
				}

				if ( ! empty( $payment_info['last4'] ) ) {
					$payment_method_display .= ' ' . $this->format_card_number( $payment_info['last4'] );
				}
			} elseif ( ! empty( $method_type ) ) {
				// Non-card payment methods
				$payment_method_display = $this->format_payment_method_name( $method_type );

				// Handle specific methods with extra formatting
				switch ( $method_type ) {
					case 'bizum':
						// Add phone number for Bizum if available
						if ( ! empty( $payment_info['phoneNumber'] ) ) {
							$last4                   = substr( $payment_info['phoneNumber'], -4 );
							$payment_method_display .= ' ••••' . $last4;
						}
						break;

					case 'paypal':
						// Add PayPal email if available (obfuscated)
						if ( ! empty( $payment_info['email'] ) ) {
							$payment_method_display .= ' (' . $this->obfuscate_email( $payment_info['email'] ) . ')';
						}
						break;
				}
			}
		}

		return $payment_method_display;
	}

	/**
	 * Flatten payment method data structure from MONEI SDK Payment object
	 *
	 * @param \Monei\Model\Payment $payment MONEI payment object.
	 * @return array Flattened payment data.
	 */
	public function flatten_payment_method_data( $payment ): array {
		$flattened = array();

		$payment_method = $payment->getPaymentMethod();
		if ( ! $payment_method ) {
			return $flattened;
		}

		// Get base method type
		$flattened['method'] = $payment_method->getMethod();

		// Card details
		$card = $payment_method->getCard();
		if ( $card ) {
			// Get tokenization method (for Apple Pay, Google Pay) from card
			if ( method_exists( $card, 'getTokenizationMethod' ) && $card->getTokenizationMethod() ) {
				$flattened['tokenizationMethod'] = $card->getTokenizationMethod();
			}
			if ( $card->getBrand() ) {
				$flattened['brand'] = $card->getBrand();
			}
			if ( $card->getLast4() ) {
				$flattened['last4'] = $card->getLast4();
			}
			if ( $card->getType() ) {
				$flattened['type'] = $card->getType();
			}
			if ( $card->getCardholderName() ) {
				$flattened['cardholderName'] = $card->getCardholderName();
			}
		}

		// Bizum details
		$bizum = $payment_method->getBizum();
		if ( $bizum && $bizum->getPhoneNumber() ) {
			$flattened['phoneNumber'] = $bizum->getPhoneNumber();
		}

		// PayPal details
		$paypal = $payment_method->getPaypal();
		if ( $paypal && $paypal->getEmail() ) {
			$flattened['email'] = $paypal->getEmail();
		}

		return $flattened;
	}

	/**
	 * Extract and format payment method information from MONEI payment object
	 *
	 * @param \Monei\Model\Payment $payment MONEI payment object.
	 * @return string Formatted payment method display.
	 */
	public function get_payment_method_display_from_payment( $payment ): string {
		$flattened = $this->flatten_payment_method_data( $payment );
		return $this->format_payment_method_display( $flattened );
	}
}
