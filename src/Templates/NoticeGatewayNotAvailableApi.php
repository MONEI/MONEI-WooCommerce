<?php

namespace Monei\Templates;

class NoticeGatewayNotAvailableApi implements TemplateInterface
{
    public function render( $data ): void
    {
        $settings_link = esc_url(
            admin_url(
                add_query_arg(
                    array(
                        'page' => 'wc-settings',
                        'tab'  => 'monei_settings',
                    ),
                    'admin.php'
                )
            )
        );
        ?>

        <div class="inline error">
            <p>
                <strong><?php esc_html_e( 'Gateway Disabled', 'monei' ); ?></strong>: <?php esc_html_e( 'MONEI API key or Account ID is missing.', 'monei' ); ?>
                <a href="<?php echo esc_url( $settings_link ); ?>"><?php esc_html_e( 'Go to MONEI API Key Settings', 'monei' ); ?></a>
            </p>
        </div>
        <?php
    }
}