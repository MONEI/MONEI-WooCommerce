<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class Subscription
{
    /**
     * Unique identifier for the subscription.
     * @DTA\Data(field="id", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $id;

    /**
     * Amount intended to be collected by this payment. A positive integer representing how much to charge in the smallest currency unit (e.g., 100 cents to charge 1.00 USD).
     * @DTA\Data(field="amount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $amount;

    /**
     * Three-letter [ISO currency code](https://en.wikipedia.org/wiki/ISO_4217), in uppercase. Must be a supported currency.
     * @DTA\Data(field="currency", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $currency;

    /**
     * An arbitrary string attached to the subscription. Often useful for displaying to users.
     * @DTA\Data(field="description", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $description;

    /**
     * MONEI Account identifier.
     * @DTA\Data(field="accountId", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $account_id;

    /**
     * Has the value &#x60;true&#x60; if the resource exists in live mode or the value &#x60;false&#x60; if the resource exists in test mode.
     * @DTA\Data(field="livemode", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $livemode;

    /**
     * @DTA\Data(field="status", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\SubscriptionStatus::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\SubscriptionStatus::class})
     * @var \App\DTO\SubscriptionStatus|null
     */
    public $status;

    /**
     * @DTA\Data(field="customer", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentCustomer::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentCustomer::class})
     * @var \App\DTO\PaymentCustomer|null
     */
    public $customer;

    /**
     * @DTA\Data(field="billingDetails", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentBillingDetails::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentBillingDetails::class})
     * @var \App\DTO\PaymentBillingDetails|null
     */
    public $billing_details;

    /**
     * @DTA\Data(field="shippingDetails", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentShippingDetails::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentShippingDetails::class})
     * @var \App\DTO\PaymentShippingDetails|null
     */
    public $shipping_details;

    /**
     * @DTA\Data(field="interval", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\SubscriptionInterval::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\SubscriptionInterval::class})
     * @var \App\DTO\SubscriptionInterval|null
     */
    public $interval;

    /**
     * Number of intervals between subscription payments.
     * @DTA\Data(field="intervalCount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $interval_count;

    /**
     * Number of intervals when subscription will be paused before it activates again.
     * @DTA\Data(field="pauseIntervalCount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $pause_interval_count;

    /**
     * An order ID from your system. A unique identifier that can be used to reconcile the payment with your internal system.
     * @DTA\Data(field="lastOrderId", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $last_order_id;

    /**
     * @DTA\Data(field="lastPayment", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\SubscriptionLastPayment::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\SubscriptionLastPayment::class})
     * @var \App\DTO\SubscriptionLastPayment|null
     */
    public $last_payment;

    /**
     * @DTA\Data(field="paymentMethod", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\SubscriptionPaymentMethod::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\SubscriptionPaymentMethod::class})
     * @var \App\DTO\SubscriptionPaymentMethod|null
     */
    public $payment_method;

    /**
     * The start date of the current subscription period. Measured in seconds since the Unix epoch.
     * @DTA\Data(field="currentPeriodStart", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"float"})
     * @var float|null
     */
    public $current_period_start;

    /**
     * The end date of the current subscription period. Measured in seconds since the Unix epoch.
     * @DTA\Data(field="currentPeriodEnd", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"float"})
     * @var float|null
     */
    public $current_period_end;

    /**
     * The end date of the trial period. Measured in seconds since the Unix epoch.
     * @DTA\Data(field="trialPeriodEnd", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"float"})
     * @var float|null
     */
    public $trial_period_end;

    /**
     * The date when the next payment will be made.
     * @DTA\Data(field="nextPaymentAt", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $next_payment_at;

    /**
     * Number of retries left for the subscription.
     * @DTA\Data(field="retryCount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $retry_count;

    /**
     * If true, the subscription will be canceled at the end of the current period.
     * @DTA\Data(field="cancelAtPeriodEnd", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $cancel_at_period_end;

    /**
     * If true, the subscription will be paused at the end of the current period.
     * @DTA\Data(field="pauseAtPeriodEnd", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $pause_at_period_end;

    /**
     * @DTA\Data(field="traceDetails", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentTraceDetails::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentTraceDetails::class})
     * @var \App\DTO\PaymentTraceDetails|null
     */
    public $trace_details;

    /**
     * A permanent identifier that refers to the initial payment of a sequence of payments. This value needs to be sent in the path for &#x60;RECURRING&#x60; payments.
     * @DTA\Data(field="sequenceId", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $sequence_id;

    /**
     * The URL will be called each time subscription status changes.
     * @DTA\Data(field="callbackUrl", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $callback_url;

    /**
     * The URL will be called each time subscription creates a new payments.
     * @DTA\Data(field="paymentCallbackUrl", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $payment_callback_url;

    /**
     * Time at which the resource was created. Measured in seconds since the Unix epoch.
     * @DTA\Data(field="createdAt", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $created_at;

    /**
     * Time at which the resource updated last time. Measured in seconds since the Unix epoch.
     * @DTA\Data(field="updatedAt", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $updated_at;

}
