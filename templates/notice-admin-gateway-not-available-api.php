<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$settings_link = esc_url( admin_url( add_query_arg( array(
    'page' => 'wc-settings',
    'tab'  => 'monei_settings',
), 'admin.php' ) ) );
?>
<div class="inline error">
    <p>
        <strong><?php esc_html_e( 'Gateway Disabled', 'monei' ); ?></strong>: <?php esc_html_e( 'MONEI API key or Account ID is missing.', 'monei' ); ?>
        <a href="<?php echo $settings_link; ?>"><?php esc_html_e( 'Go to MONEI API Key Settings', 'monei' ); ?></a>
    </p>
</div>