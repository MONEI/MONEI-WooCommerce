<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class CancelPaymentRequest
{
    /**
     * @DTA\Data(field="cancellationReason", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentCancellationReason::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentCancellationReason::class})
     * @var \App\DTO\PaymentCancellationReason|null
     */
    public $cancellation_reason;

}
