<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Shipping information associated with the payment.
 */
class PaymentShippingDetails
{
    /**
     * The shipping customer’s full name.
     * @DTA\Data(field="name", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $name;

    /**
     * The shipping customer’s email address.
     * @DTA\Data(field="email", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $email;

    /**
     * The shipping customer’s phone number.
     * @DTA\Data(field="phone", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $phone;

    /**
     * Name of the company where the shipment is going.
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
