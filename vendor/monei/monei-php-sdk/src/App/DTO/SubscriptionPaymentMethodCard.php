<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Details about the card used as payment method at the time of the transaction.
 */
class SubscriptionPaymentMethodCard
{
    /**
     * Two-letter country code ([ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2)).
     * @DTA\Data(field="country", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $country;

    /**
     * Card brand.
     * @DTA\Data(field="brand", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $brand;

    /**
     * Card type &#x60;debit&#x60; or &#x60;credit&#x60;.
     * @DTA\Data(field="type", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $type;

    /**
     * Wether this transaction used 3D Secure authentication.
     * @DTA\Data(field="threeDSecure", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $three_d_secure;

    /**
     * The protocol version of the 3DS challenge.
     * @DTA\Data(field="threeDSecureVersion", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $three_d_secure_version;

    /**
     * Time at which the card will expire. Measured in seconds since the Unix epoch.
     * @DTA\Data(field="expiration", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $expiration;

    /**
     * The last four digits of the card.
     * @DTA\Data(field="last4", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $last4;

}
