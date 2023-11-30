<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Parameters for payments_recurring
 */
class PaymentsRecurringParameterData
{
    /**
     * The sequence ID
     * @DTA\Data(subset="path", field="sequenceId")
     * @DTA\Strategy(subset="path", name="QueryStringScalar", options={"type":"string"})
     * @DTA\Validator(subset="path", name="QueryStringScalar", options={"type":"string"})
     * @var string|null
     */
    public $sequence_id;

}
