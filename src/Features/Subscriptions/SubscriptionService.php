<?php

namespace Monei\Features\Subscriptions;

use WC_Order;

class SubscriptionService
{
    private $wooHandler;
    private $yithHandler;

    public function __construct(WooCommerceSubscriptionsHandler $wooHandler, YithSubscriptionPluginHandler $yithHandler)
    {
        $this->wooHandler = $wooHandler;
        $this->yithHandler = $yithHandler;
    }

    public function getHandler(): ?SubscriptionHandlerInterface
    {
        if ($this->wooHandler->is_subscriptions_addon_enabled()) {
            return $this->wooHandler;
        }

        if ($this->yithHandler->is_subscriptions_addon_enabled()) {
            return $this->yithHandler;
        }

        return null;
    }
}