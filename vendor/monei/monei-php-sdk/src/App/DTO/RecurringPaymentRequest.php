<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class RecurringPaymentRequest
{
    /**
     * An order ID from your system. A unique identifier that can be used to reconcile the payment with your internal system.
     * @DTA\Data(field="orderId")
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $order_id;

    /**
     * The amount to collected by this subsequent payment. A positive integer representing how much to charge in the smallest currency unit (e.g., 100 cents to charge 1.00 USD).
     * @DTA\Data(field="amount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $amount;

    /**
     * Same as the &#x60;transactionType&#x60; parameter from [create payment](https://docs.monei.com/api/#operation/payments_create). If not sent, it will default in the same transaction type used in the initial payment.
     * @DTA\Data(field="transactionType", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":PaymentTransactionType::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":PaymentTransactionType::class})
     * @var PaymentTransactionType|null
     */
    public $transaction_type;

    /**
     * An arbitrary string attached to the payment. Often useful for displaying to users.
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
     * The URL to which a payment result should be sent asynchronously.
     * @DTA\Data(field="callbackUrl", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $callback_url;

}
