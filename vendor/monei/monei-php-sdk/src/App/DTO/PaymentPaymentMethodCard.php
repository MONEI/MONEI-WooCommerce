<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Details about the card used as payment method at the time of the transaction.
 */
class PaymentPaymentMethodCard
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
     * Whether this transaction used 3D Secure authentication.
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
     * The flow used for 3DS authentication. - &#x60;CHALLENGE&#x60; - In a challenge flow, the issuer requires additional shopper interaction, either through biometrics, two-factor authentication, or similar methods based on [Strong Customer Authentication (SCA)](https://en.wikipedia.org/wiki/Strong_customer_authentication) factors. - &#x60;FRICTIONLESS&#x60; - In a frictionless flow, the acquirer, issuer, and card scheme exchange all necessary     information in the background through passive authentication using the shopper&#39;s device     fingerprint. The transaction is completed without further shopper interaction. - &#x60;FRICTIONLESS_CHALLENGE&#x60; - This flow is the complete 3DS flow. It is similar to the 3DS frictionless flow but     includes an additional authentication step (challenge) that will be invoked if the     information provided in the data collection step does not suffice to determine the     risk-level of the transaction. - &#x60;DIRECT&#x60; - This transaction did not require [Strong Customer Authentication (SCA)](https://en.wikipedia.org/wiki/Strong_customer_authentication) due to the low risk
     * @DTA\Data(field="threeDSecureFlow", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $three_d_secure_flow;

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

    /**
     * The digital wallet used to tokenize the card.
     * @DTA\Data(field="tokenizationMethod", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $tokenization_method;

    /**
     * The name of the cardholder.
     * @DTA\Data(field="cardholderName", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $cardholder_name;

    /**
     * The email of the cardholder.
     * @DTA\Data(field="cardholderEmail", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $cardholder_email;

}
