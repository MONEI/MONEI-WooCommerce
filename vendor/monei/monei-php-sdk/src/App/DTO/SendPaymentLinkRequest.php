<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class SendPaymentLinkRequest
{
    /**
     * The customer will receive payment link on this email address.
     * @DTA\Data(field="customerEmail", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $customer_email;

    /**
     * Phone number in E.164 format. The customer will receive payment link on this phone number.
     * @DTA\Data(field="customerPhone", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $customer_phone;

    /**
     * @DTA\Data(field="channel", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentMessageChannel::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentMessageChannel::class})
     * @var \App\DTO\PaymentMessageChannel|null
     */
    public $channel;

    /**
     * @DTA\Data(field="language", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentMessageLanguage::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentMessageLanguage::class})
     * @var \App\DTO\PaymentMessageLanguage|null
     */
    public $language;

}
