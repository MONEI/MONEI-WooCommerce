<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Details about the Bizum account used for this payment. If provided phone number is valid and registered in Bizum, MONEI will try to confirm the payment directly.
 */
class PaymentPaymentMethodBizumInput
{
    /**
     * The phone number used to pay with &#x60;bizum&#x60;.
     * @DTA\Data(field="phoneNumber", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $phone_number;

}
