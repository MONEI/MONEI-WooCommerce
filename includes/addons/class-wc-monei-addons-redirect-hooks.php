<?php

use Monei\Features\Subscriptions\SubscriptionService;
use Monei\Features\Subscriptions\WooCommerceSubscriptionsHandler;
use Monei\Features\Subscriptions\YithSubscriptionPluginHandler;
use Monei\Services\ApiKeyService;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\sdk\MoneiSdkClientFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * For readability's sake, we will create addons redirect logic here instead of using class-wc-monei-redirect-hooks.php.
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_Addons_Redirect_Hooks {

	private MoneiPaymentServices $moneiPaymentServices;

	/**
	 * Hooks on redirects.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'subscriptions_save_sequence_id' ) );
		add_action( 'template_redirect', array( $this, 'subscriptions_save_sequence_id_on_payment_method_change' ) );
		//TODO use the container
		$apiKeyService              = new ApiKeyService();
		$sdkClient                  = new MoneiSdkClientFactory( $apiKeyService );
		$wooHandler                 = new WooCommerceSubscriptionsHandler( $sdkClient );
		$yithHandler                = new YithSubscriptionPluginHandler( $sdkClient );
		$this->moneiPaymentServices = new MoneiPaymentServices( $sdkClient );
		$this->subscriptionService  = new SubscriptionService( $wooHandler, $yithHandler );
	}

	/**
	 * When subscribers, changes manually payment method from my account.
	 * We need to update the subscription sequence_id and cc information.
	 * On success, we are sent to "My account"
	 */
	public function subscriptions_save_sequence_id_on_payment_method_change() {
		if ( ! is_account_page() ) {
			return;
		}
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_GET['id'] ) ) {
			return;
		}
        WC_Monei_Logger::log( 'Changing the method, updating the sequence id for subscriptions' );

		$payment_id = filter_input( INPUT_GET, 'id', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );
		$order_id   = filter_input( INPUT_GET, 'orderId', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );

		$verification_order_id = explode( '_', $order_id );
		// Order ID will have a format like follows.
		// orderId=453_verification1635257618
		if ( ! isset( $verification_order_id[1] ) || false === strpos( $verification_order_id[1], 'verification' ) ) {
			return;
		}

		$order_id = $verification_order_id[0];
		$handler  = $this->subscriptionService->getHandler();
		if ( ! $handler || ! $handler->is_subscription_order( $order_id ) ) {
			return;
		}

		try {
			/**
			 * We need to update parent from subscription, where sequence id is stored.
			 */
			$payment      = $this->moneiPaymentServices->get_payment( $payment_id );
			$subscriptions = $handler->get_subscriptions_for_order( $order_id);
            $handler->update_subscription_meta_data($subscriptions, $payment);

		} catch ( Exception $e ) {
			wc_add_notice( __( 'Error while saving sequence id. Please contact admin. Payment ID: ', 'monei' ) . $payment_id, 'error' );
			WC_Monei_Logger::log( $e->getMessage(), 'error' );
		}
	}

	/**
	 * When a payment is done on a subscription order, in order_received_page we need to save its sequence id.
	 * This sequence id will be used afterwards on recurring payments.
	 */
	public function subscriptions_save_sequence_id() {
		if ( ! is_order_received_page() ) {
			return;
		}
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_GET['id'] ) ) {
			return;
		}

		$payment_id = filter_input( INPUT_GET, 'id', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );
		$order_id   = filter_input( INPUT_GET, 'orderId', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );

		/**
		 * Bail when not subscription.
		 */
		$handler = $this->subscriptionService->getHandler();
		if ( ! $handler || ! $handler->is_subscription_order( $order_id ) ) {
			return;
		}

		try {
			$subscriptions = $handler->get_subscriptions_for_order( $order_id );
			if ( ! $subscriptions ) {
				return;
			}

			$payment = $this->moneiPaymentServices->get_payment( $payment_id );
			$handler->update_subscription_meta_data( $subscriptions, $payment );
		} catch ( Exception $e ) {
			wc_add_notice( __( 'Error while saving sequence id. Please contact admin. Payment ID: ', 'monei' ) . $payment_id, 'error' );
			WC_Monei_Logger::log( $e->getMessage(), 'error' );
		}
	}
}

new WC_Monei_Addons_Redirect_Hooks();
