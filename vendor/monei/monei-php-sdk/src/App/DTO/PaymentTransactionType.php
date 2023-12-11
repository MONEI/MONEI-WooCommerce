<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Controls when the funds will be captured.   - &#x60;SALE&#x60; - **Default**. MONEI automatically captures funds     when the customer authorizes the payment.   - &#x60;AUTH&#x60; - Place a hold on the funds when the customer authorizes     the payment, but don’t capture the funds until later.
 */
class PaymentTransactionType
{
}
