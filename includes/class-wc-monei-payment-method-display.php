<?php
/**
 * Payment Method Display Handler
 *
 * Handles displaying formatted payment method information in admin and customer-facing areas.
 *
 * @package Monei
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Monei_Payment_Method_Display
 */
class WC_Monei_Payment_Method_Display {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Display payment method in admin order list
		add_filter( 'woocommerce_order_get_payment_method_title', array( $this, 'get_payment_method_title' ), 10, 2 );

		// Display payment method in order details (admin and customer)
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_payment_method_details_admin' ), 10, 1 );
	}

	/**
	 * Override payment method title to show formatted payment details
	 *
	 * @param string   $title Payment method title.
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	public function get_payment_method_title( $title, $order ) {
		// Only modify MONEI payment methods
		$payment_method = $order->get_payment_method();
		if ( strpos( $payment_method, 'monei' ) !== 0 ) {
			return $title;
		}

		// Get stored payment method display
		$payment_method_display = $order->get_meta( '_monei_payment_method_display', true );

		if ( $payment_method_display ) {
			return $payment_method_display;
		}

		return $title;
	}

	/**
	 * Display payment method details in admin order page
	 *
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	public function display_payment_method_details_admin( $order ) {
		// Only show for MONEI payment methods
		$payment_method = $order->get_payment_method();
		if ( strpos( $payment_method, 'monei' ) !== 0 ) {
			return;
		}

		$payment_method_display = $order->get_meta( '_monei_payment_method_display', true );
		if ( ! $payment_method_display ) {
			return;
		}

		$payment_id     = $order->get_meta( '_payment_order_number_monei', true );
		$payment_status = $order->get_meta( '_payment_order_status_monei', true );

		?>
		<div class="order_data_column">
			<h3><?php esc_html_e( 'MONEI Payment Details', 'monei' ); ?></h3>
			<p>
				<strong><?php esc_html_e( 'Payment Method:', 'monei' ); ?></strong><br/>
				<?php echo esc_html( $payment_method_display ); ?>
			</p>
			<?php if ( $payment_id ) : ?>
				<p>
					<strong><?php esc_html_e( 'Transaction ID:', 'monei' ); ?></strong><br/>
					<?php echo esc_html( $payment_id ); ?>
				</p>
			<?php endif; ?>
			<?php if ( $payment_status ) : ?>
				<p>
					<strong><?php esc_html_e( 'Payment Status:', 'monei' ); ?></strong><br/>
					<?php echo esc_html( $payment_status ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}

new WC_Monei_Payment_Method_Display();
