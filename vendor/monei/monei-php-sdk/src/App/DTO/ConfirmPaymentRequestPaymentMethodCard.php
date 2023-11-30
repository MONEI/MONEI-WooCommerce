<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Additional information about the card used for this payment.
 */
class ConfirmPaymentRequestPaymentMethodCard
{
    /**
     * The cardholder&#39;s name, as stated in the credit card.
     * @DTA\Data(field="cardholderName", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $cardholder_name;

    /**
     * The cardholder&#39;s email address.
     * @DTA\Data(field="cardholderEmail", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $cardholder_email;

}
