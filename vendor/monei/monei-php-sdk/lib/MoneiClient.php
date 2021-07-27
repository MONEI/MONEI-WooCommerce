<?php
/**
 * MONEI Payment Gateway API v1
 * MoneiClient
 * PHP version 5
 *
 * @category Class
 * @package  Monei\MoneiClient
 * @author   MONEI
 * @link     https://monei.com
 */

namespace Monei;

use OpenAPI\Client\ApiException;
use OpenAPI\Client\Configuration;
use OpenAPI\Client\Api\PaymentsApi;

/**
 * PaymentsApi Class Doc Comment
 *
 * @category Class
 * @package  OpenAPI\Client
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class MoneiClient
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var PaymentsApi
     */
    public $payments;

    /**
     * @param string          $apiKey
     * @param Configuration   $config
     */
    public function __construct(
        string $apiKey,
        Configuration $config = null
    ) {
        $this->config = $config ?: Configuration::getDefaultConfiguration();
        $this->config->setApiKey('Authorization', $apiKey);
        $this->config->setUserAgent('MONEI/PHP/0.1.9');

        $this->payments = new PaymentsApi(null, $this->config);
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string    $body
     * @param string    $signature
     * @return object
     */
    public function verifySignature($body, $signature)
    {
        $parts = array_reduce(explode(',', $signature), function ($result, $part) {
            [$key, $value] = explode('=', $part);
            $result[$key] = $value;
            return $result;
        }, []);

        $hmac = hash_hmac('SHA256', $parts['t'] . '.' . $body, $this->config->getApiKey('Authorization'));

        if ($hmac !== $parts['v1']) {
            throw new ApiException('[401] Signature verification failed', 401);
        }

        return json_decode($body);
    }
}
