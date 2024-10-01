<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<div class="monei-settings-header-logo">
    <img src="<?php echo esc_url( $moneiIconUrl ); ?>" alt="" />
</div>
<div class="monei-settings-header-welcome">
    <p><?php echo esc_html( $welcomeString ); ?></p>
</div>
<div class="monei-settings-header-buttons">
    <a href="https://dashboard.monei.com" class="button button-primary" target="_blank"><?php echo esc_html( $dashboardString ); ?></a>
    <a href="https://support.monei.com/" class="button" target="_blank"><?php echo esc_html( $supportString ); ?></a>
</div>
