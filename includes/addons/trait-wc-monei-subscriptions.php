<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Trait for Subscriptions compatibility.
 *
 * @since 5.0
 */
trait WC_Monei_Subscriptions_Trait {

	use WC_Monei_Addons_Helper_Trait;

	/**
	 * Add support to subscription.
	 * Add all related hooks.
	 */
	public function init_subscriptions() {
		$this->supports = array_merge(
			$this->supports,
			[
				'subscriptions',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change',
				'subscription_payment_method_change_customer',
				'multiple_subscriptions',
			]
		);

		add_action( 'wc_gateway_monei_create_payment_success', [ $this, 'subscription_after_payment_success' ], 1, 3 );
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, [ $this, 'scheduled_subscription_payment' ], 1, 3 );

		// Add Payment information to Payment method name in "Subscription" Tab.
		add_filter( 'woocommerce_my_subscriptions_payment_method', [ $this, 'add_extra_info_to_subscriptions_payment_method_title' ], 10, 2 );

	}

	/**
	 * Enrich Payment method name on "Subscriptions" Tab.
	 *
	 * @param string $payment_method_to_display
	 * @param WC_Subscription $subscription
	 *
	 * @return string
	 */
	public function add_extra_info_to_subscriptions_payment_method_title( $payment_method_to_display, $subscription ) {
		// We only will modify Monei subscriptions titles.
		if ( $subscription->get_payment_method() !== $this->id ) {
			return $payment_method_to_display;
		}
		return $payment_method_to_display . ' - ' . $this->get_subscription_payment_method_friendly_name( $subscription );
	}

	/**
	 * Process payment on renewal. Woo automatically triggers this hooks once subscription needs to be renewed.
	 *
	 * @param $amount_to_charge
	 * @param WC_Order $renewal_order
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
		$sequence_id = $this->get_sequence_id_from_renewal_order( $renewal_order );
		$description = $this->shop_name . ' - #' . (string) $renewal_order->get_id() . ' - Subscription Renewal';

		$payload = [
			'orderId'     => (string) $renewal_order->get_id(),
			'amount'      => monei_price_format( $amount_to_charge ),
			'description' => $description,
		];

		try {
			$payment = WC_Monei_API::recurring_payment( $sequence_id, $payload );

			if ( 'SUCCEEDED' === $payment->getStatus() ) {
				$renewal_order->payment_complete( $payment->getId() );

				$order_note  = __( 'Success Renewal scheduled_subscription_payment.', 'monei' ) . '<br>';
				$order_note .= __( 'MONEI Transaction id: ', 'monei' ) . $payment->getId() . '. <br><br>';
				$order_note .= __( 'MONEI Status Message: ', 'monei' ) . $payment->getStatusMessage();
				$renewal_order->add_order_note( $order_note );

				do_action( 'wc_gateway_monei_scheduled_subscription_payment_success', $renewal_order, $amount_to_charge );
			} else {
				$order_note  = __( 'Error Renewal scheduled_subscription_payment. Reason: ', 'monei' ) . '<br>';
				$order_note .= __( 'MONEI Transaction id: ', 'monei' ) . $payment->getId() . '. <br><br>';
				$order_note .= __( 'MONEI Status Message: ', 'monei' ) . $payment->getStatusMessage();
				$renewal_order->update_status( 'failed' );
				$renewal_order->add_order_note( $order_note );

				do_action( 'wc_gateway_monei_scheduled_subscription_payment_not_succeeded', $renewal_order, $amount_to_charge );
			}
			$renewal_order->save();

		} catch ( Exception $e ) {
			do_action( 'wc_gateway_monei_scheduled_subscription_payment_error', $e, $renewal_order, $amount_to_charge );
			WC_Monei_Logger::log( $e, 'error' );
			$renewal_order->update_status( 'failed' );
			$renewal_order->add_order_note( __( 'Error Renewal scheduled_subscription_payment. Reason: ', 'monei' ) . $e->getMessage() );
			$renewal_order->save();
			if ( isset( $_REQUEST['process_early_renewal'] ) && ! wp_doing_cron() ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}
	}

	/**
	 * If Subscription has a free trial, first payment 0 euros.
	 * We will charge customer 1 cent ( create_subscription_payload ):
	 * 1. Free Trial
	 * 2. Payment made with tokenized card ( paymentToken is set )
	 *
	 * Therefore, after the 1 cent payment, we need to refund it automatically.
	 * This hooks will trigger after successful (1 cent) payment.
	 *
	 * @param $confirm_payload
	 * @param $confirm_payment
	 * @param $order
	 *
	 * @throws \OpenAPI\Client\ApiException
	 */
	public function subscription_after_payment_success( $confirm_payload, $confirm_payment, $order ) {
		/**
		 * If order is not subscription, bail.
		 */
		if ( ! $this->is_order_subscription( $order->get_id() ) ) {
			return;
		}

		/**
		 * If payment wasn't 1 cent, bail.
		 */
		if ( 1 !== $confirm_payload['amount'] ) {
			return;
		}

		/**
		 * If payment is not done with a tokenized card, bail.
		 */
		if ( ! isset( $confirm_payload['paymentToken'] ) ) {
			return;
		}

		/**
		 * Refund that cent.
		 */
		WC_Monei_API::refund_payment( $confirm_payment->getId(), 1 );
	}

	/**
	 * It adds subscription configurtion to the payload.
	 *
	 * @param $order_id
	 * @param $payment_method
	 *
	 * @return array
	 */
	public function create_subscription_payload( WC_Order $order_id, $payment_method ) {
		$order               = new WC_Order( $order_id );
		$payload             = parent::create_payload( $order, $payment_method );
		$payload['sequence'] = [
			'type' => 'recurring',
			'recurring' => [
				//'frequency' => $this->get_cart_subscription_interval_in_days() // The minimum number of days between the different recurring payments.
				'frequency' => 1 // Testing with 1 to know if we can modify subscription dates.
			]
		];

		/**
		 * If there is a free trial, (first payment for free) and user has selected a tokenized card,
		 * We hit a monei limitation, so we need to charge the customer 1 cent, that will be refunded afterwards.
		 */
		if ( 0 === monei_price_format( $order->get_total() ) && $this->get_payment_token_id_if_selected() ) {
			$payload['amount'] = 1;
		}

		/**
		 * Supporting Subscriber Payment Method Changes
		 * https://docs.woocommerce.com/document/subscriptions/develop/payment-gateway-integration/#section-18
		 *
		 * We need to charge 0, in order to get new sequence id.
		 * If customer has selected a tokenized card, because of monei restrictions
		 * we need to charge one cent, to be refunded afterwards in order to get new sequence_id.
		 */
		if ( $this->is_subscription_change_payment_page() ) {
			$payload['amount'] = 0;
			if ( isset( $payload['paymentToken'] ) ) {
				$payload['amount'] = 1;
			}

			$payload['orderId']     = $order->get_id() . '_verification' . time();
			$payload['description'] = $payload['description'] . ' ' . __( 'Payment Method Subscription Change', 'monei' );
		}

		$payload = apply_filters( 'wc_gateway_monei_create_subscription_payload', $payload );
		return $payload;
	}
}

