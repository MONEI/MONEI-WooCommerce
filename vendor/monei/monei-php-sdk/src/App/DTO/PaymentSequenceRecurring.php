<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Specific configurations for recurring payments. Will only be used when &#x60;sequence&#x60;.&#x60;type&#x60; is &#x60;recurring&#x60;.
 */
class PaymentSequenceRecurring
{
    /**
     * Date after which no further recurring payments will be performed. Must be formatted as &#x60;YYYYMMDD&#x60;.
     * @DTA\Data(field="expiry", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $expiry;

    /**
     * The minimum number of **days** between the different recurring payments.
     * @DTA\Data(field="frequency", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $frequency;

}
