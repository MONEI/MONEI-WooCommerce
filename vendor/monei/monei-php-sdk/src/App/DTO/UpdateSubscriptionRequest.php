<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class UpdateSubscriptionRequest
{
    /**
     * Amount intended to be collected by this payment. A positive integer representing how much to charge in the smallest currency unit (e.g., 100 cents to charge 1.00 USD).
     * @DTA\Data(field="amount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $amount;

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
     * An arbitrary string attached to the subscription. Often useful for displaying to users.
     * @DTA\Data(field="description", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $description;

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
     * The end date of the trial period. Measured in seconds since the Unix epoch.
     * @DTA\Data(field="trialPeriodEnd", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"float"})
     * @var float|null
     */
    public $trial_period_end;

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
     * If true, the subscription will be paused at the end of the current period.
     * @DTA\Data(field="pauseAtPeriodEnd", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $pause_at_period_end;

    /**
     * If true, the subscription will be canceled at the end of the current period.
     * @DTA\Data(field="cancelAtPeriodEnd", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $cancel_at_period_end;

    /**
     * Number of intervals when subscription will be paused before it activates again.
     * @DTA\Data(field="pauseIntervalCount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $pause_interval_count;

}
