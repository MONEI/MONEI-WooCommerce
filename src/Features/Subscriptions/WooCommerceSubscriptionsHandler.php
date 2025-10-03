<?php

namespace Monei\Features\Subscriptions;

use Monei\Services\ApiKeyService;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\sdk\MoneiSdkClientFactory;
use WC_Order;

class WooCommerceSubscriptionsHandler implements SubscriptionHandlerInterface {

	private $moneiPaymentServices;

	public function __construct( MoneiSdkClientFactory $sdkClient ) {
		$this->moneiPaymentServices = new MoneiPaymentServices( $sdkClient );
	}

	/**
	 * Checks if subscription plugin is enabled.
	 *
	 * @since 5.0
	 *
	 * @return bool
	 */
	public function is_subscriptions_addon_enabled(): bool {
		return class_exists( 'WC_Subscriptions' );
	}

	public function is_subscription_order( int $order_id ): bool {
		if ( ! $this->is_subscriptions_addon_enabled() ) {
			return false;
		}
		return ( function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription(
			$order_id
		) || wcs_is_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) ) );    }

	/**
	 * Checks if page is pay for order and change subs payment page.
	 *
	 * @return bool
	 */
	public function is_subscription_change_payment_page(): bool {
        return ( isset( $_GET['pay_for_order'] ) && isset( $_GET['change_payment_method'] ) ); // phpcs:ignore
	}

	public function get_subscriptions_for_order( int $order_id ): array {
        //new WC_Subscription( $order_id );
		return wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => array( 'any' ) ) );
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
	 * @throws \Monei\ApiException
	 */
	public function subscription_after_payment_success( $confirm_payload, $confirm_payment, $order ): void {
		/**
		 * If order is not subscription, bail.
		 */
		if ( ! $this->is_subscription_order( $order->get_id() ) ) {
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
		$this->moneiPaymentServices->refund_payment( $confirm_payment->getId(), 1 );
	}

	/**
	 * It adds subscription configuration to the payload.
	 *
	 * @param $order
	 * @param $payment_method
	 *
	 * @return array
	 */
	public function create_subscription_payload( WC_Order $order, $payment_method, $payload ): array {
		$payload['sequence'] = array(
			'type'      => 'recurring',
			'recurring' => array(
				'frequency' => 1, // Testing with 1 to know if we can modify subscription dates.
			),
		);

		/**
		 * If there is a free trial, (first payment for free) and user has selected a tokenized card,
		 * We hit a monei limitation, so we need to charge the customer 1 cent, that will be refunded afterwards.
		 */
		if ( 0 === monei_price_format( $order->get_total() ) && isset( $payload['paymentToken'] ) ) {
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

	public function add_extra_info_to_subscriptions_payment_method_title( $payment_method_to_display, $subscription ): string {
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
	 * @param WC_Order         $renewal_order
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ): void {
		$sequence_id = $this->get_sequence_id_from_renewal_order( $renewal_order );
		$description = get_bloginfo( 'name' ) . ' - #' . (string) $renewal_order->get_id() . ' - Subscription Renewal';

		$payload = array(
			'orderId'     => (string) $renewal_order->get_id(),
			'amount'      => monei_price_format( $amount_to_charge ),
			'description' => $description,
		);

		try {
			$payment = $this->moneiPaymentServices->recurring_payment( $sequence_id, $payload );

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

		} catch ( \Exception $e ) {
			do_action( 'wc_gateway_monei_scheduled_subscription_payment_error', $e, $renewal_order, $amount_to_charge );
			\WC_Monei_Logger::log( $e, 'error' );
			$renewal_order->update_status( 'failed' );
			$renewal_order->add_order_note( __( 'Error Renewal scheduled_subscription_payment. Reason: ', 'monei' ) . $e->getMessage() );
			$renewal_order->save();
			if ( isset( $_REQUEST['process_early_renewal'] ) && ! wp_doing_cron() ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}
	}

    public function update_subscription_meta_data($subscriptions, $payment): void
    {
        /**
         * Iterate all subscriptions contained in the order, and add sequence id and cc data individually.
         */
        foreach ( $subscriptions as $subscription_id => $subscription ) {
            $subscription->update_meta_data( '_monei_sequence_id', $payment->getSequenceId() );
            $subscription->update_meta_data( '_monei_payment_method_brand', $payment->getPaymentMethod()->getCard()->getBrand() );
            $subscription->update_meta_data( '_monei_payment_method_4_last_digits', $payment->getPaymentMethod()->getCard()->getLast4() );
            $subscription->save_meta_data();
        }
    }


    public function init_subscriptions( array $supports, string $gateway_id ): array {
		add_action( 'wc_gateway_monei_create_payment_success', array( $this, 'subscription_after_payment_success' ), 1, 3 );
		add_action( 'woocommerce_scheduled_subscription_payment_' . $gateway_id, array( $this, 'scheduled_subscription_payment' ), 1, 2 );

		// Add Payment information to Payment method name in "Subscription" Tab.
		add_filter( 'woocommerce_my_subscriptions_payment_method', array( $this, 'add_extra_info_to_subscriptions_payment_method_title' ), 10, 2 );
		return array_merge(
			$supports,
			array(
				'subscriptions',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change',
				'subscription_payment_method_change_customer',
				'multiple_subscriptions',
			)
		);
	}
	public function get_cart_subscription_interval_in_days() {
		foreach ( WC()->cart->cart_contents as $cart_item ) {
			if ( \WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) ) {
				$interval = \WC_Subscriptions_Product::get_interval( $cart_item['data'] );
				$period   = \WC_Subscriptions_Product::get_period( $cart_item['data'] );
				break;
			}
		}

		switch ( $period ) {
			case 'year':
				$interval_in_days = $interval * 365;
				break;
			case 'month':
				$interval_in_days = $interval * 28; // Monei Needs minimun days, to be safe, 28.
				break;
			case 'week':
				$interval_in_days = $interval * 7;
				break;
			default:
				$interval_in_days = $interval;
				break;
		}

		return $interval_in_days;
	}

	/**
	 * Retrieves parent order id from a renewal order.
	 *
	 * @param $renewal_order
	 *
	 * @return false|WC_Order
	 */
	public function get_parent_for_renewal_order_id( $renewal_order ) {
		$subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order );
		$subscription  = array_pop( $subscriptions );

		if ( 0 === $subscription->get_parent_id() ) {
			$parent_order = null;
		} else {
			$parent_order = $subscription->get_parent();
		}
		return $parent_order;
	}
	/**
	 * Retrieves parent order from a subscription order.
	 *
	 * @param \WCS_Subscription $subscription_order
	 *
	 * @return \WC_Order|false
	 */
	public function get_parent_for_subscription_id( $subscription_order ) {
		return $subscription_order->get_parent();
	}

	/**
	 * From renewal order, get monei sequence id.
	 *
	 * @param $renewal_order
	 *
	 * @return string|false
	 */
	public function get_sequence_id_from_renewal_order( $renewal_order ) {
		return $renewal_order->get_meta( '_monei_sequence_id', true );
	}

	/**
	 * Gets a readable string to present in subscription frontend.
	 *
	 * @param $subscription
	 *
	 * @return string
	 */
	public function get_subscription_payment_method_friendly_name( $subscription ) {
		$brand       = $subscription->get_meta( '_monei_payment_method_brand', true );
		$last_digits = $subscription->get_meta( '_monei_payment_method_4_last_digits', true );
		/* translators: 1) card brand 2) last 4 digits */
		return sprintf( __( '%1$s card ending in %2$s', 'monei' ), $brand, $last_digits );
	}

    /**
     * Check if a product is a subscription using WooCommerce Subscription logic
     *
     * @return bool
     */
    public function cart_has_subscription() {
        if (!$this->is_subscriptions_addon_enabled()) {
            return false;
        }
        return is_array( WC()->cart->recurring_carts ) && count( WC()->cart->recurring_carts ) > 0;
    }
}
