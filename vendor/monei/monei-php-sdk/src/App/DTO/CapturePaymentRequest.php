<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class CapturePaymentRequest
{
    /**
     * The amount to capture, which must be less than or equal to the original amount. Any additional amount will be automatically refunded.
     * @DTA\Data(field="amount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $amount;

}
