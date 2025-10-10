<?php

namespace Monei\Gateways\Abstracts;

use Monei\Model\PaymentStatus;
use Monei\ApiException;
use Exception;
use MoneiPaymentServices;
use WC_Blocks_Utils;
use WC_Geolocation;
use WC_Monei_Logger;
use WC_Order;
use WC_Payment_Tokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class that will be inherited by all integrated components payment methods.
 * Class WC_Monei_Payment_Gateway_Component
 *
 * @since 5.0
 */
abstract class WCMoneiPaymentGatewayComponent extends WCMoneiPaymentGateway {

	const APPLE_GOOGLE_ID = 'monei_apple_google';

	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param int    $order_id
	 * @param string|null $allowed_payment_method
	 * @return array
	 */
	public function process_payment( $order_id, $allowed_payment_method = null ) {
		$order   = new WC_Order( $order_id );
		$payload = $this->create_payload( $order, $allowed_payment_method );
		$payload = $this->handler === null || ! ( $this->handler->is_subscription_order( $order_id ) ) ? $payload : $this->handler->create_subscription_payload( $order, $allowed_payment_method, $payload );

		/**
		 * If payment is tokenized ( saved cc ) we just need to create_payment with token and everything will work fine.
		 * If payment is normal cc, we will do 2 steps.
		 * First Step: Create Payment without token.
		 * Second Step: Confirm Payment with Token and cardholderName.
		 * Strong CustomerAuthentication and PSD2 normative requires cardholder name to be sent for each transaction.
		 * See: https://docs.monei.com/docs/guides/send-cardholder-name/
		 */
		try {
			$create_payment = $this->moneiPaymentServices->create_payment( $payload );
			do_action( 'wc_gateway_monei_create_payment_success', $payload, $create_payment, $order );

			$this->log(
				function () use ( $allowed_payment_method ) {
					return 'WC_Monei_API::create_payment ' . $allowed_payment_method; },
				'debug'
			);
			$this->log(
				function () use ( $payload ) {
					return $payload;
				},
				'debug'
			);
			$this->log(
				function () use ( $create_payment ) {
					return $create_payment;
				},
				'debug'
			);

			$confirm_payment = false;
			// We need to return the payment ID to the frontend and confirm payment there if we arrive from block checkout
			// and when we are not in redirect flow (component cc), but user didn't choose any tokenized saved method
			if ( $this->isBlockCheckout() && ! $this->redirect_flow && ! isset( $payload['paymentToken'] ) ) {
				return array(
					'result'      => 'success',
					'redirect'    => false,
					'paymentId'   => $create_payment->getId(),  // Send the paymentId back to the client
					'token'       => $this->get_frontend_generated_monei_token(),  // Send the token back to the client
					'completeUrl' => $payload['completeUrl'],
					'failUrl'     => $payload['failUrl'],
					'orderId'     => $order_id,
				);
			}

			// We need to confirm payment, when we are not in redirect flow (component cc), but user didn't choose any tokenized saved method.
			if ( ! $this->redirect_flow && ! isset( $payload['paymentToken'] ) ) {
				// We do 2 steps, in order to confirm card holder Name in the second step.
				$confirm_payload = array(
					'paymentToken'  => $this->get_frontend_generated_monei_token(),
					'paymentMethod' => array(
						'card' => array(
							'cardholderName' => $this->get_frontend_generated_monei_cardholder( $order ),
						),
					),
				);

				$confirm_payment = $this->moneiPaymentServices->confirm_payment( $create_payment->getId(), $confirm_payload );
				do_action( 'wc_gateway_monei_confirm_payment_success', $confirm_payload, $confirm_payment, $order );

				$this->log(
					function () use ( $allowed_payment_method ) {
						return 'WC_Monei_API::confirm_payment ' . $allowed_payment_method;
					},
					'debug'
				);
				$this->log(
					function () use ( $create_payment ) {
						return $create_payment->getId();
					},
					'debug'
				);
				$this->log(
					function () use ( $confirm_payload ) {
						return $confirm_payload;
					},
					'debug'
				);
				$this->log(
					function () use ( $confirm_payment ) {
						return $confirm_payment;
					},
					'debug'
				);
			}

			/** Depends if we came in 1 step or 2. */
			$payment_result = $confirm_payment ?: $create_payment;
			// Get redirect URL from nextAction, or fall back to order received page
			$next_action = $payment_result->getNextAction();
			if ( $next_action && $next_action->getRedirectUrl() ) {
				$next_action_redirect = $next_action->getRedirectUrl();
			} else {
				// If no redirect URL from MONEI (e.g., immediately successful payment with saved card),
				// redirect to order received page
				$next_action_redirect = $this->get_return_url( $order );
			}

			// Add payment ID and status to redirect URL for order verification (similar to blocks checkout)
			// This ensures order is marked as paid even if IPN hasn't arrived yet (race condition fix)
			/** @var string $payment_status */
			$payment_status = $payment_result->getStatus();
			if ( PaymentStatus::SUCCEEDED === $payment_status || PaymentStatus::AUTHORIZED === $payment_status || PaymentStatus::PENDING === $payment_status ) {
				$redirect_url         = add_query_arg(
					array(
						'id'      => $payment_result->getId(),
						'orderId' => $order_id,
						'status'  => $payment_status,
					),
					$next_action_redirect
				);
				$next_action_redirect = $redirect_url;
			}

			return array(
				'result'   => 'success',
				'redirect' => $next_action_redirect,
			);
		} catch ( ApiException $e ) {
			do_action( 'wc_gateway_monei_process_payment_error', $e, $order );
			// Parse API exception and get user-friendly error message
			$error_info = $this->statusCodeHandler->parse_api_exception( $e );

				// Log the technical details
			if ( $error_info['statusCode'] ) {
				WC_Monei_Logger::logError( sprintf( 'Payment error - Status Code: %s, Raw Message: %s', $error_info['statusCode'], $error_info['rawMessage'] ) );
			} else {
				WC_Monei_Logger::logError( sprintf( 'Payment error - Raw Message: %s', $error_info['rawMessage'] ?? $e->getMessage() ) );
			}

			// Show user-friendly error message to customer
			wc_add_notice( $error_info['message'], 'error' );

			return array(
				'result' => 'failure',
			);
		} catch ( Exception $e ) {
			do_action( 'wc_gateway_monei_process_payment_error', $e, $order );
			WC_Monei_Logger::logError( $e->getMessage() );
			wc_add_notice( $e->getMessage(), 'error' );
			return array(
				'result' => 'failure',
			);
		}
	}

	/**
	 * Payload creation.
	 *
	 * @param $order
	 * @param null|string $allowed_payment_method
	 *
	 * @return array
	 */
	public function create_payload( $order, $allowed_payment_method = null ) {
		$order_id    = $order->get_id();
		$amount      = monei_price_format( $order->get_total() );
		$currency    = get_woocommerce_currency();
		$user_email  = $order->get_billing_email();
		$description = $this->shop_name . ' - #' . $order_id;

		/** The URL to which a payment result should be sent asynchronously. */
		$callback_url = wp_sanitize_redirect( esc_url_raw( $this->notify_url ) );
		/** The URL the customer will be directed to if the payment failed. */
		$fail_url = esc_url_raw( $order->get_checkout_payment_url( false ) );
		/** The URL the customer will be directed to after transaction completed (successful or failed). */
		$complete_url = wp_sanitize_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'utm_nooverride' => '1',
						'orderId'        => $order_id,
					),
					$this->get_return_url( $order )
				)
			)
		);

		/** Create Payment Payload */
		$payload = array(
			'amount'                => $amount,
			'currency'              => $currency,
			'orderId'               => (string) $order_id,
			'description'           => $description,
			'customer'              => array(
				'email' => $user_email,
				'name'  => $order->get_formatted_billing_full_name(),
				'phone' => ( $order->get_billing_phone() ) ?: null,
			),
			'callbackUrl'           => $callback_url,
			'completeUrl'           => $complete_url,
			'cancelUrl'             => wc_get_checkout_url(),
			'failUrl'               => $fail_url,
			'transactionType'       => ( $this->pre_auth ) ? self::PRE_AUTH_TRANSACTION_TYPE : self::SALE_TRANSACTION_TYPE,
			'sessionDetails'        => array(
				'ip'        => WC_Geolocation::get_ip_address(),
				'userAgent' => wc_get_user_agent(),
			),
			'billingDetails'        => array(
				'name'    => ( $order->get_formatted_billing_full_name() ) ?: null,
				'email'   => ( $order->get_billing_email() ) ?: null,
				'phone'   => ( $order->get_billing_phone() ) ?: null,
				'company' => ( $order->get_billing_company() ) ?: null,
				'address' => array(
					'country' => ( $order->get_billing_country() ) ?: null,
					'city'    => ( $order->get_billing_city() ) ?: null,
					'line1'   => ( $order->get_billing_address_1() ) ?: null,
					'line2'   => ( $order->get_billing_address_2() ) ?: null,
					'zip'     => ( $order->get_billing_postcode() ) ?? null,
					'state'   => ( $order->get_billing_state() ) ?: null,
				),
			),
			'shippingDetails'       => array(
				'name'    => ( $order->get_formatted_shipping_full_name() ) ?: null,
				'email'   => $user_email,
				'phone'   => ( $order->get_shipping_phone() ) ?: null,
				'company' => ( $order->get_shipping_company() ) ?: null,
				'address' => array(
					'country' => ( $order->get_shipping_country() ) ?: null,
					'city'    => ( $order->get_shipping_city() ) ?: null,
					'line1'   => ( $order->get_shipping_address_1() ) ?: null,
					'line2'   => ( $order->get_shipping_address_2() ) ?: null,
					'zip'     => ( $order->get_shipping_postcode() ) ?: null,
					'state'   => ( $order->get_shipping_state() ) ?: null,
				),
			),
			'allowedPaymentMethods' => array( $allowed_payment_method ),
		);

		// If customer has selected a saved payment method, we get the token from $_POST and we add it to the payload.
		$token_id = $this->get_payment_token_id_if_selected();
		if ( $token_id ) {
			$wc_token                = WC_Payment_Tokens::get( $token_id );
			$payload['paymentToken'] = $wc_token->get_token();
		}

		// If user has paid using Apple or Google pay, we add paymentToken.
		// This will overwrite previous token, in case one preselected token was checked in checkout, but we should ignore it.
		$token_id = $this->get_frontend_generated_monei_apple_google_token();
		if ( $token_id ) {
			$payload['paymentToken'] = $token_id;
		}

		// If customer has checkboxed "Save payment information to my account for future purchases."
		$should_save = $this->get_save_payment_card_checkbox();
		if ( $this->tokenization && $should_save ) {
			$payload['generatePaymentToken'] = true;
		}
		$componentGateways = array( MONEI_GATEWAY_ID, self::APPLE_GOOGLE_ID );
		// If merchant is not using redirect flow (means component CC or apple/google pay), there is a generated frontend token paymentToken and we need to add session ID to the request.
		if ( in_array( $this->id, $componentGateways, true ) && ! $this->redirect_flow && ( $this->get_frontend_generated_monei_token() || $this->get_frontend_generated_monei_apple_google_token() ) ) {
			$payload['sessionId'] = (string) ( WC()->session !== null ? WC()->session->get_customer_id() : '' );
		}

		$payload = apply_filters( 'wc_gateway_monei_create_payload', $payload );
		return $payload;
	}

	/**
	 * Frontend MONEI generated token.
	 *
	 * @return false|string
	 */
	public function get_frontend_generated_monei_token() {
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return ( isset( $_POST['monei_payment_token'] ) ) ? wc_clean( wp_unslash( $_POST['monei_payment_token'] ) ) : false;  // WPCS: CSRF ok.
	}

	/**
	 * Frontend MONEI generated flag for block checkout processing.
	 *
	 * @return boolean
	 */
	public function isBlockCheckout() {
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return ( isset( $_POST['monei_is_block_checkout'] ) ) ? wc_clean( wp_unslash( $_POST['monei_is_block_checkout'] ) ) === 'yes' : false;  // WPCS: CSRF ok.
	}

	/**
	 * Frontend MONEI cardholderName.
	 *
	 * @return false|string
	 */
	public function get_frontend_generated_monei_cardholder( $order ) {
		$defaultName = $order->get_formatted_billing_full_name();
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return ( isset( $_POST['monei_cardholder_name'] ) ) ? wc_clean( wp_unslash( $_POST['monei_cardholder_name'] ) ) : $defaultName;  // WPCS: CSRF ok.
	}

	/**
	 * Frontend MONEI payment-request token generated when Apple or Google pay.
	 * https://docs.monei.com/docs/monei-js/payment-request/
	 *
	 * @return false|string
	 */
	protected function get_frontend_generated_monei_apple_google_token() {
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return ( isset( $_POST['monei_payment_request_token'] ) ) ? wc_clean( wp_unslash( $_POST['monei_payment_request_token'] ) ) : false;  // WPCS: CSRF ok.
	}
}
