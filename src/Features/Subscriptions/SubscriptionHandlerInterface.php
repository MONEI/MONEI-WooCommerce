<?php

namespace Monei\Features\Subscriptions;

use WC_Order;

interface SubscriptionHandlerInterface
{
    public function is_subscriptions_addon_enabled():bool;
    public function is_subscription_order(int $order_id): bool;
    public function create_subscription_payload(WC_Order $order, $payment_method, array $payload): array;
    public function scheduled_subscription_payment($amount_to_charge, WC_Order $renewal_order): void;
    public function init_subscriptions(array $suports, string $gateway_id): array;
    public function add_extra_info_to_subscriptions_payment_method_title(string $payment_method_to_display, $subscription): string;
    public function subscription_after_payment_success($confirm_payload, $confirm_payment, WC_Order $order): void;
    public function get_subscriptions_for_order(int $order_id):array;
    public function update_subscription_meta_data( $subscriptions, $payment ): void;
}