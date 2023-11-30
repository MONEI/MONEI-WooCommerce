<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class PauseSubscriptionRequest
{
    /**
     * If true, the subscription will be paused at the end of the current period.
     * @DTA\Data(field="pauseAtPeriodEnd", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $pause_at_period_end;

    /**
     * Number of intervals when subscription will be paused before it activates again.
     * @DTA\Data(field="pauseIntervalCount", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $pause_interval_count;

}
