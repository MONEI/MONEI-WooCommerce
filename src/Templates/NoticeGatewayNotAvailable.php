<?php

namespace Monei\Templates;

class NoticeGatewayNotAvailable implements TemplateInterface
{
    public function render( $data ): void
    {
        ?>

        <div class="inline error">
            <p>
                <strong><?php esc_html_e( 'Gateway Disabled', 'monei' ); ?></strong>: <?php esc_html_e( 'MONEI only support EUROS, USD & GBP currencies.', 'monei' ); ?>
            </p>
        </div>
        <?php
    }
}