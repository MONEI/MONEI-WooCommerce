<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Details about the payment method at the time of the transaction.
 */
class SubscriptionPaymentMethod
{
    /**
     * Subscription method type.
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

}
