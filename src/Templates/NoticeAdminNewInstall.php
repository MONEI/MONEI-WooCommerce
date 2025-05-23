<?php

namespace Monei\Templates;

class NoticeAdminNewInstall implements TemplateInterface {


	public function render( $data ): void {
		?>

		<div id="message" class="updated woocommerce-message woocommerce-monei-messages">
			<div class="contenido-monei-notice">
				<a class="woocommerce-message-close notice-dismiss" style="top:0;"
					href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'monei-hide-new-version', 'hide-new-version-monei' ), 'monei_hide_new_version_nonce', '_monei_hide_new_version_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'monei' ); ?></a>
				<p>
				<h3>
					<?php echo esc_html__( 'Thank you for install MONEI for WooCommerce. Version: ', 'monei' ) . ' ' . esc_html( MONEI_VERSION ); ?>
				</h3>
				</p>
				<p>
					<?php esc_html_e( 'The best payment gateway rates. The perfect solution to manage your digital payments.', 'monei' ); ?>
				</p>
				<p class="submit">
					<a href="<?php echo esc_url( MONEI_SIGNUP ); ?>" class="button-primary"
						target="_blank"><?php esc_html_e( 'Signup', 'monei' ); ?></a>
					<a href="<?php echo esc_url( MONEI_WEB ); ?>" class="button-primary"
						target="_blank"><?php esc_html_e( 'MONEI website', 'monei' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}
}