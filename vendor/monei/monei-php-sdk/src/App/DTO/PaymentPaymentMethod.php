<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Details about the payment method at the time of the transaction.
 */
class PaymentPaymentMethod
{
    /**
     * Payment method type.
     * @DTA\Data(field="method", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $method;

    /**
     * @DTA\Data(field="card", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentPaymentMethodCard::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentPaymentMethodCard::class})
     * @var \App\DTO\PaymentPaymentMethodCard|null
     */
    public $card;

    /**
     * @DTA\Data(field="bizum", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentPaymentMethodBizum::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentPaymentMethodBizum::class})
     * @var \App\DTO\PaymentPaymentMethodBizum|null
     */
    public $bizum;

    /**
     * @DTA\Data(field="paypal", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentPaymentMethodPaypal::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentPaymentMethodPaypal::class})
     * @var \App\DTO\PaymentPaymentMethodPaypal|null
     */
    public $paypal;

    /**
     * @DTA\Data(field="cofidis", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentPaymentMethodCofidis::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentPaymentMethodCofidis::class})
     * @var \App\DTO\PaymentPaymentMethodCofidis|null
     */
    public $cofidis;

}
