<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 */
class RegisterDomainRequest
{
    /**
     * The domain name to register for Apple Pay.
     * @DTA\Data(field="domainName")
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $domain_name;

}
