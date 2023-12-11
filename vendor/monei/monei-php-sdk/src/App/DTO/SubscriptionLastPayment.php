<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class SubscriptionLastPayment
{
    /**
     * Unique identifier for the payment.
     * @DTA\Data(field="id", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $id;

    /**
     * @DTA\Data(field="status", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentStatus::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentStatus::class})
     * @var \App\DTO\PaymentStatus|null
     */
    public $status;

    /**
     * Payment status code.
     * @DTA\Data(field="statusCode", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $status_code;

    /**
     * Human readable status message, can be displayed to a user.
     * @DTA\Data(field="statusMessage", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $status_message;

}
