<?php

namespace Monei\Gateways\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Monei\Gateways\Abstracts\WCMoneiPaymentGateway;

final class MoneiAppleGoogleBlocksSupport extends AbstractPaymentMethodType
{

    protected $name = 'monei_apple_google';

    public function __construct(WCMoneiPaymentGateway $gateway) {
        $this->gateway = $gateway;
    }

    public function initialize()
    {


    }


    public function is_active()
    {


    }


    public function get_payment_method_script_handles()
    {

    }


    public function get_payment_method_data()
    {


    }
}
