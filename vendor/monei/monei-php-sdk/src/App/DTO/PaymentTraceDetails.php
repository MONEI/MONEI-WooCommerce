<?php
declare(strict_types=1);

namespace App\DTO;

use Articus\DataTransfer\Annotation as DTA;

/**
 * Information related to the browsing session of the user who initiated the payment.
 */
class PaymentTraceDetails
{
    /**
     * The IP address where the operation originated.
     * @DTA\Data(field="ip", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $ip;

    /**
     * Two-letter country code ([ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2)).
     * @DTA\Data(field="countryCode", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $country_code;

    /**
     * Two-letter language code ([ISO 639-1](https://en.wikipedia.org/wiki/ISO_639-1)).
     * @DTA\Data(field="lang", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $lang;

    /**
     * Device type, could be &#x60;desktop&#x60;, &#x60;mobile&#x60;, &#x60;smartTV&#x60;, &#x60;tablet&#x60;.
     * @DTA\Data(field="deviceType", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $device_type;

    /**
     * Information about the device used for the browser session (e.g., &#x60;iPhone&#x60;).
     * @DTA\Data(field="deviceModel", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $device_model;

    /**
     * The browser used in this browser session (e.g., &#x60;Mobile Safari&#x60;).
     * @DTA\Data(field="browser", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $browser;

    /**
     * The version for the browser session (e.g., &#x60;13.1.1&#x60;).
     * @DTA\Data(field="browserVersion", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $browser_version;

    /**
     * Operation system (e.g., &#x60;iOS&#x60;).
     * @DTA\Data(field="os", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $os;

    /**
     * Operation system version (e.g., &#x60;13.5.1&#x60;).
     * @DTA\Data(field="osVersion", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $os_version;

    /**
     * The source component from where the operation was generated (mostly for our SDK&#39;s).
     * @DTA\Data(field="source", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $source;

    /**
     * The source component version from where the operation was generated (mostly for our SDK&#39;s).
     * @DTA\Data(field="sourceVersion", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $source_version;

    /**
     * Full user agent string of the browser session.
     * @DTA\Data(field="userAgent", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $user_agent;

    /**
     * Browser accept header.
     * @DTA\Data(field="browserAccept", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $browser_accept;

    /**
     * The color depth of the browser session (e.g., &#x60;24&#x60;).
     * @DTA\Data(field="browserColorDepth", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $browser_color_depth;

    /**
     * The screen height of the browser session (e.g., &#x60;1152&#x60;).
     * @DTA\Data(field="browserScreenHeight", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $browser_screen_height;

    /**
     * The screen width of the browser session (e.g., &#x60;2048&#x60;).
     * @DTA\Data(field="browserScreenWidth", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"int"})
     * @var int|null
     */
    public $browser_screen_width;

    /**
     * The timezone offset of the browser session (e.g., &#x60;-120&#x60;).
     * @DTA\Data(field="browserTimezoneOffset", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $browser_timezone_offset;

    /**
     * The ID of the user that started the operation.
     * @DTA\Data(field="userId", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $user_id;

    /**
     * The email of the user that started the operation.
     * @DTA\Data(field="userEmail", nullable=true)
     * @DTA\Validator(name="Scalar", options={"type":"string"})
     * @var string|null
     */
    public $user_email;

}
