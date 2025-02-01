<?php

namespace Monei\Templates;

class NoticeAdminDependency implements TemplateInterface
{

    public function render( $data ): void
    {
        ?>

        <div class="notice notice-error">
            <p>
                <strong><?php echo esc_html_e( 'MONEI Gateway is inactive because WooCommerce is not installed and active.', 'monei' ); ?></strong>
            </p>
        </div>
        <?php
    }
}