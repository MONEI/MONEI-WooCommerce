<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class RefundPaymentRequest
{
    /**
     * The amount to refund, which must be less than or equal to the original amount.
     * @DTA\Data(field="amount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $amount;

    /**
     * @DTA\Data(field="refundReason", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentRefundReason::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentRefundReason::class})
     * @var \App\DTO\PaymentRefundReason|null
     */
    public $refund_reason;

}
