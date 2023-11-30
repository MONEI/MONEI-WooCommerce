<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * This field needs to be sent in order to mark the beginning of a sequence of payments (recurring/subscriptions, installments, and so). Specific configurations can be set in the inside properties (&#x60;recurring&#x60;).
 */
class PaymentSequence
{
    /**
     * @DTA\Data(field="type")
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $type;

    /**
     * @DTA\Data(field="recurring", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentSequenceRecurring::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentSequenceRecurring::class})
     * @var \App\DTO\PaymentSequenceRecurring|null
     */
    public $recurring;

}
