<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Details about the card used as payment method. If provided, MONEI will try to confirm the payment directly.
 */
class PaymentPaymentMethodCardInput
{
    /**
     * The card number, as a string without any separators.
     * @DTA\Data(field="number", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $number;

    /**
     * Card security code.
     * @DTA\Data(field="cvc", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $cvc;

    /**
     * Two-digit number representing the card’s expiration month.
     * @DTA\Data(field="expMonth", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $exp_month;

    /**
     * Two-digit number representing the card’s expiration year.
     * @DTA\Data(field="expYear", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $exp_year;

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
