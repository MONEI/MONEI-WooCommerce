<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class CreateSubscriptionRequest
{
    /**
     * Amount intended to be collected by this payment. A positive integer representing how much to charge in the smallest currency unit (e.g., 100 cents to charge 1.00 USD).
     * @DTA\Data(field="amount")
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $amount;

    /**
     * Three-letter [ISO currency code](https://en.wikipedia.org/wiki/ISO_4217), in uppercase. Must be a supported currency.
     * @DTA\Data(field="currency")
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $currency;

    /**
     * @DTA\Data(field="interval")
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\SubscriptionInterval::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\SubscriptionInterval::class})
     * @var \App\DTO\SubscriptionInterval|null
     */
    public $interval;

    /**
     * Number of intervals between subscription payments.
     * @DTA\Data(field="intervalCount")
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
     * Number of days the trial period lasts.
     * @DTA\Data(field="trialPeriodDays", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $trial_period_days;

    /**
     * The URL will be called each time subscription status changes.
     * @DTA\Data(field="callbackUrl")
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $callback_url;

    /**
     * The URL will be called each time subscription creates a new payments.
     * @DTA\Data(field="paymentCallbackUrl")
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $payment_callback_url;

}
