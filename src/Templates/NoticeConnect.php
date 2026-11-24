<?php

namespace Monei\Templates;

class NoticeConnect implements TemplateInterface {

	public function render( $data ): void {
		$settings_link = admin_url( 'admin.php?page=wc-settings&tab=monei_settings' );
		$dismiss_link  = wp_nonce_url( add_query_arg( 'monei-hide-connect-notice', '1' ), 'monei_hide_connect_notice', '_monei_hide_connect_notice_nonce' );
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Connect with MONEI to stop storing your account API key.', 'monei' ); ?></strong>
				<a href="<?php echo esc_url( $settings_link ); ?>"><?php esc_html_e( 'Go to MONEI Settings', 'monei' ); ?></a>
				|
				<a href="<?php echo esc_url( $dismiss_link ); ?>"><?php esc_html_e( 'Dismiss', 'monei' ); ?></a>
			</p>
		</div>
		<?php
	}
}
