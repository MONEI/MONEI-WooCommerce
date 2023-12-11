<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Additional information about the payment method used for this payment.
 */
class ConfirmPaymentRequestPaymentMethod
{
    /**
     * @DTA\Data(field="card", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\ConfirmPaymentRequestPaymentMethodCard::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\ConfirmPaymentRequestPaymentMethodCard::class})
     * @var \App\DTO\ConfirmPaymentRequestPaymentMethodCard|null
     */
    public $card;

}
