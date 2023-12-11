<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * An information about a payment method used for this payment. We recommend using &#x60;paymentToken&#x60; instead, as it is more secure way to pass sensitive payment information. Processing credit card information on your server requires [PCI DSS compliance](https://www.investopedia.com/terms/p/pci-compliance.asp).
 */
class PaymentPaymentMethodInput
{
    /**
     * @DTA\Data(field="card", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentPaymentMethodCardInput::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentPaymentMethodCardInput::class})
     * @var \App\DTO\PaymentPaymentMethodCardInput|null
     */
    public $card;

    /**
     * @DTA\Data(field="bizum", nullable=true)
     * @DTA\Strategy(name="Object", options={"type":\App\DTO\PaymentPaymentMethodBizumInput::class})
     * @DTA\Validator(name="TypeCompliant", options={"type":\App\DTO\PaymentPaymentMethodBizumInput::class})
     * @var \App\DTO\PaymentPaymentMethodBizumInput|null
     */
    public $bizum;

}
