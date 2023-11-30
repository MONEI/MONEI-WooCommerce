<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class CancelSubscriptionRequest
{
    /**
     * If true, the subscription will be canceled at the end of the current period.
     * @DTA\Data(field="cancelAtPeriodEnd", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $cancel_at_period_end;

}
