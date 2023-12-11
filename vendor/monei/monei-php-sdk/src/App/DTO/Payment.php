<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class Payment
{
    /**
     * Unique identifier for the payment.
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
     * An order ID from your system. A unique identifier that can be used to reconcile the payment with your internal system.
     * @DTA\Data(field="orderId", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $order_id;

    /**
     * An arbitrary string attached to the payment. Often useful for displaying to users.
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
     * Unique identifier provided by the bank performing transaction.
     * @DTA\Data(field="authorizationCode", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $authorization_code;

    /**
     * Has the value &#x60;true&#x60; if the resource exists in live mode or the value &#x60;false&#x60; if the resource exists in test mode.
     * @DTA\Data(field="livemode", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $livemode;

    /**
     * @DTA\Data(field="status", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentStatus::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentStatus::class})
     * @var \App\DTO\PaymentStatus|null
     */
    public $status;

    /**
     * Payment status code.
     * @DTA\Data(field="statusCode", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $status_code;

    /**
     * Human readable status message, can be displayed to a user.
     * @DTA\Data(field="statusMessage", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $status_message;

    /**
     * @DTA\Data(field="customer", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentCustomer::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentCustomer::class})
     * @var \App\DTO\PaymentCustomer|null
     */
    public $customer;

    /**
     * @DTA\Data(field="shop", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentShop::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentShop::class})
     * @var \App\DTO\PaymentShop|null
     */
    public $shop;

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
     * Amount in cents refunded (can be less than the amount attribute on the payment if a partial refund was issued).
     * @DTA\Data(field="refundedAmount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $refunded_amount;

    /**
     * Amount in cents refunded in the last transaction.
     * @DTA\Data(field="lastRefundAmount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $last_refund_amount;

    /**
     * @DTA\Data(field="lastRefundReason", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentLastRefundReason::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentLastRefundReason::class})
     * @var \App\DTO\PaymentLastRefundReason|null
     */
    public $last_refund_reason;

    /**
     * @DTA\Data(field="cancellationReason", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentCancellationReason::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentCancellationReason::class})
     * @var \App\DTO\PaymentCancellationReason|null
     */
    public $cancellation_reason;

    /**
     * @DTA\Data(field="sessionDetails", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentSessionDetails::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentSessionDetails::class})
     * @var \App\DTO\PaymentSessionDetails|null
     */
    public $session_details;

    /**
     * @DTA\Data(field="traceDetails", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentTraceDetails::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentTraceDetails::class})
     * @var \App\DTO\PaymentTraceDetails|null
     */
    public $trace_details;

    /**
     * A permanent token represents a payment method used in the payment. Pass &#x60;generatePaymentToken: true&#x60; when you creating a payment to generate it. You can pass it as &#x60;paymentToken&#x60; parameter to create other payments with the same payment method. This token does not expire, and should only be used server-side.
     * @DTA\Data(field="paymentToken", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $payment_token;

    /**
     * @DTA\Data(field="paymentMethod", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentPaymentMethod::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentPaymentMethod::class})
     * @var \App\DTO\PaymentPaymentMethod|null
     */
    public $payment_method;

    /**
     * @DTA\Data(field="sequence", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentSequence::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentSequence::class})
     * @var \App\DTO\PaymentSequence|null
     */
    public $sequence;

    /**
     * A permanent identifier that refers to the initial payment of a sequence of payments. This value needs to be sent in the path for &#x60;RECURRING&#x60; payments.
     * @DTA\Data(field="sequenceId", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $sequence_id;

    /**
     * A unique identifier of the Point of Sale. If specified the payment is attached to this Point of Sale. If there is a QR code attached to the same Point of Sale, this payment will be available by scanning the QR code.
     * @DTA\Data(field="pointOfSaleId", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $point_of_sale_id;

    /**
     * @DTA\Data(field="nextAction", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentNextAction::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentNextAction::class})
     * @var \App\DTO\PaymentNextAction|null
     */
    public $next_action;

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
