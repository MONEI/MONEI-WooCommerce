<?php

namespace Monei\Templates;

class SettingsHeader implements TemplateInterface {


	public function render( $data ): void {
		$moneiIconUrl    = $data['moneiIconUrl'] ?? '';
		$welcomeString   = $data['welcomeString'] ?? '';
		$dashboardString = $data['dashboardString'] ?? '';
		$supportString   = $data['supportString'] ?? '';
		$reviewString    = $data['reviewString'] ?? '';
		?>

		<div class="monei-settings-header-logo">
			<img src="<?php echo esc_url( $moneiIconUrl ); ?>" alt="" />
		</div>
		<div class="monei-settings-header-welcome">
			<p><?php echo esc_html( $welcomeString ); ?></p>
		</div>
		<div class="monei-settings-header-buttons">
			<a href="<?php echo esc_url( MONEI_SIGNUP ); ?>" class="button button-primary" target="_blank"><?php echo esc_html( $dashboardString ); ?></a>
			<a href="<?php echo esc_url( MONEI_SUPPORT ); ?>" class="button" target="_blank"><?php echo esc_html( $supportString ); ?></a>
			<a href="<?php echo esc_url( MONEI_REVIEW ); ?>" class="button" target="_blank"><?php echo esc_html( $reviewString ); ?></a>
		</div>
		<?php
	}
}