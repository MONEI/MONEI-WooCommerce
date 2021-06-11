<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Redirect Helper Class
 * This class will handle different redirect urls from monei.
 * failUrl : The URL the customer will be directed to after transaction has failed, instead of completeUrl (used in hosted payment page). This allows to provide two different URLs for successful and failed payments.
 * cancelUrl : The URL the customer will be directed to if they decide to cancel payment and return to your website (used in hosted payment page).
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_Redirect {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_cancelled_order', array( $this, 'add_notice_monei_order_cancelled' ) );
	}

	/**
	 * When MONEI send us back to get_cancel_order_url_raw()
	 * We need to show message to the customer + save it into the order.
	 *
	 * @param $order_id
	 * @return void
	 */
	public function add_notice_monei_order_cancelled( $order_id ) {
		if ( isset( $_GET['status'] ) && isset( $_GET['message'] ) && 'FAILED' === $_GET['status'] ) {
			$order_id         = absint( $_GET['order_id'] );
			$order            = wc_get_order( $order_id );

			$order->add_order_note( __( 'MONEI Status: ', 'monei' ) . esc_html( $_GET['status'] ) );
			$order->add_order_note( __( 'MONEI message: ', 'monei' ) . esc_html( $_GET['message'] ) );

			wc_add_notice( esc_html( $_GET['message'] ), 'error' );

			WC_Monei_Logger::log( __( 'Order Cancelled: ', 'monei' ) . $order_id );
			WC_Monei_Logger::log( __( 'MONEI Status: ', 'monei' ) . esc_html( $_GET['status'] ) );
			WC_Monei_Logger::log( __( 'MONEI message: ', 'monei' ) . esc_html( $_GET['message'] ) );
		}
	}
}

new WC_Monei_Redirect();

