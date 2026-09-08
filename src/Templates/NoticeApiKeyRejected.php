<?php

namespace Monei\Templates;

class NoticeApiKeyRejected implements TemplateInterface {

	/**
	 * @param array $data Carries 'until': Unix timestamp of the next automatic retry.
	 */
	public function render( $data ): void {
		$settings_link = admin_url(
			add_query_arg(
				array(
					'page' => 'wc-settings',
					'tab'  => 'monei_settings',
				),
				'admin.php'
			)
		);
		$retry_at      = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $data['until'] );
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'MONEI rejected the API key.', 'monei' ); ?></strong>
				<?php esc_html_e( 'MONEI payment methods may be missing from the checkout until the key is corrected.', 'monei' ); ?>
				<?php
				printf(
					/* translators: %s: date and time of the next automatic retry. */
					esc_html__( 'The plugin will ask MONEI again at %s, or as soon as the MONEI settings are saved.', 'monei' ),
					esc_html( $retry_at )
				);
				?>
				<a href="<?php echo esc_url( $settings_link ); ?>"><?php esc_html_e( 'Go to MONEI API Key Settings', 'monei' ); ?></a>
			</p>
		</div>
		<?php
	}
}
