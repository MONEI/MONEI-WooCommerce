<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Billing information associated with the payment method at the time of the transaction.
 */
class PaymentBillingDetails
{
    /**
     * The customer’s billing full name.
     * @DTA\Data(field="name", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $name;

    /**
     * The customer’s billing email address.
     * @DTA\Data(field="email", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $email;

    /**
     * The customer’s billing phone number.
     * @DTA\Data(field="phone", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $phone;

    /**
     * Billing company name.
     * @DTA\Data(field="company", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $company;

    /**
     * @DTA\Data(field="address", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\Address::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\Address::class})
     * @var \App\DTO\Address|null
     */
    public $address;

}
