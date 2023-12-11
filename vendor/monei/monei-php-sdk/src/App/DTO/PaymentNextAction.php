<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * If present, this property tells you what actions you need to take in order for your customer to fulfill a payment using the provided source.
 */
class PaymentNextAction
{
    /**
     * - &#x60;CONFIRM&#x60; - Your customer needs to be redirected to a   [hosted payment page](https://docs.monei.com/docs/use-prebuilt-payment-page)   or confirm payment using   [payment token](https://docs.monei.com/docs/accept-card-payment#3-submitting-the-payment-to-monei-client-side).   The **redirectUrl** will point to the hosted payment page. - &#x60;FRICTIONLESS_CHALLENGE&#x60; - Your customer needs to be redirected to the frictionless    3d secure challenge page provided by the bank. The **redirectUrl**    will point to the frictionless 3d secure challenge page provided by the bank. - &#x60;CHALLENGE&#x60; - Your customer needs to be redirected to the   3d secure challenge page provided by the bank. The **redirectUrl**   will point to the 3d secure challenge page provided by the bank. - &#x60;COMPLETE&#x60; - The payment is completed. The **redirectUrl** will be   the **completeUrl** if it was provided when the payment was created. - &#x60;BIZUM_CHALLENGE&#x60; - Your customer will be redirected to the Bizum hosted payment page.
     * @DTA\Data(field="type", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $type;

    /**
     * If &#x60;true&#x60; you have to redirect your customer to the **redirectUrl** to continue payment process.
     * @DTA\Data(field="mustRedirect", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"bool"})
     * @var bool|null
     */
    public $must_redirect;

    /**
     * Redirect your customer to this url to continue payment process.
     * @DTA\Data(field="redirectUrl", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $redirect_url;

}
