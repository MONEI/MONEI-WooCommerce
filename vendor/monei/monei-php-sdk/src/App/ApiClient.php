<?php
declare(strict_types=1);

namespace App;

use Articus\DataTransfer as DT;
use OpenAPIGenerator\APIClient as OAGAC;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * MONEI API v1
 * <p>The MONEI API is organized around <a href=\"https://en.wikipedia.org/wiki/Representational_State_Transfer\">REST</a>. Our API has predictable resource-oriented URLs, accepts JSON-encoded request bodies, returns JSON-encoded responses, and uses standard HTTP response codes, authentication, and verbs.</p> <h4 id=\"base-url\">Base URL:</h4> <p><a href=\"https://api.monei.com/v1\">https://api.monei.com/v1</a></p> <h4 id=\"client-libraries\">Client libraries:</h4> <ul> <li><a href=\"https://github.com/MONEI/monei-php-sdk\">PHP SDK</a></li> <li><a href=\"https://github.com/MONEI/monei-python-sdk\">Python SDK</a></li> <li><a href=\"https://github.com/MONEI/monei-node-sdk\">Node.js SDK</a></li> <li><a href=\"https://postman.monei.com/\">Postman Collection</a></li> </ul> <h4 id=\"important\">Important:</h4> <p><strong>If you are not using our official SDKs, you need to provide a valid <code>User-Agent</code> header in each request, otherwise your requests will be rejected.</strong></p>
 * The version of the OpenAPI document: 1.3.1
 */
class ApiClient extends OAGAC\AbstractApiClient
{
    //region activate
    /**
     * Activate Subscription
     * @param \App\DTO\SubscriptionsActivateParameterData $parameters
     * @param \App\DTO\ActivateSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function activateRaw(
        \App\DTO\SubscriptionsActivateParameterData $parameters,
        \App\DTO\ActivateSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/subscriptions/{id}/activate', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Activate Subscription
     * @param \App\DTO\SubscriptionsActivateParameterData $parameters
     * @param \App\DTO\ActivateSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function activate(
        \App\DTO\SubscriptionsActivateParameterData $parameters,
        \App\DTO\ActivateSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->activateRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Activate Subscription
     * @param \App\DTO\SubscriptionsActivateParameterData $parameters
     * @param \App\DTO\ActivateSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function activateResult(
        \App\DTO\SubscriptionsActivateParameterData $parameters,
        \App\DTO\ActivateSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->activate($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region cancel
    /**
     * Cancel Payment
     * @param \App\DTO\PaymentsCancelParameterData $parameters
     * @param \App\DTO\CancelPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function cancelRaw(
        \App\DTO\PaymentsCancelParameterData $parameters,
        \App\DTO\CancelPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/payments/{id}/cancel', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Cancel Payment
     * @param \App\DTO\PaymentsCancelParameterData $parameters
     * @param \App\DTO\CancelPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function cancel(
        \App\DTO\PaymentsCancelParameterData $parameters,
        \App\DTO\CancelPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->cancelRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Cancel Payment
     * @param \App\DTO\PaymentsCancelParameterData $parameters
     * @param \App\DTO\CancelPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function cancelResult(
        \App\DTO\PaymentsCancelParameterData $parameters,
        \App\DTO\CancelPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->cancel($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region cancel_0
    /**
     * Cancel Subscription
     * @param \App\DTO\SubscriptionsCancelParameterData $parameters
     * @param \App\DTO\CancelSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function cancel_0Raw(
        \App\DTO\SubscriptionsCancelParameterData $parameters,
        \App\DTO\CancelSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/subscriptions/{id}/cancel', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Cancel Subscription
     * @param \App\DTO\SubscriptionsCancelParameterData $parameters
     * @param \App\DTO\CancelSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function cancel_0(
        \App\DTO\SubscriptionsCancelParameterData $parameters,
        \App\DTO\CancelSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->cancel_0Raw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A subscription object */
                $responseContent = new \App\DTO\Subscription();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Cancel Subscription
     * @param \App\DTO\SubscriptionsCancelParameterData $parameters
     * @param \App\DTO\CancelSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Subscription
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function cancel_0Result(
        \App\DTO\SubscriptionsCancelParameterData $parameters,
        \App\DTO\CancelSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Subscription
    {
        return $this->getSuccessfulContent(...$this->cancel_0($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region capture
    /**
     * Capture Payment
     * @param \App\DTO\PaymentsCaptureParameterData $parameters
     * @param \App\DTO\CapturePaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function captureRaw(
        \App\DTO\PaymentsCaptureParameterData $parameters,
        \App\DTO\CapturePaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/payments/{id}/capture', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Capture Payment
     * @param \App\DTO\PaymentsCaptureParameterData $parameters
     * @param \App\DTO\CapturePaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function capture(
        \App\DTO\PaymentsCaptureParameterData $parameters,
        \App\DTO\CapturePaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->captureRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Capture Payment
     * @param \App\DTO\PaymentsCaptureParameterData $parameters
     * @param \App\DTO\CapturePaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function captureResult(
        \App\DTO\PaymentsCaptureParameterData $parameters,
        \App\DTO\CapturePaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->capture($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region confirm
    /**
     * Confirm Payment
     * @param \App\DTO\PaymentsConfirmParameterData $parameters
     * @param \App\DTO\ConfirmPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function confirmRaw(
        \App\DTO\PaymentsConfirmParameterData $parameters,
        \App\DTO\ConfirmPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/payments/{id}/confirm', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Confirm Payment
     * @param \App\DTO\PaymentsConfirmParameterData $parameters
     * @param \App\DTO\ConfirmPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function confirm(
        \App\DTO\PaymentsConfirmParameterData $parameters,
        \App\DTO\ConfirmPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->confirmRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Confirm Payment
     * @param \App\DTO\PaymentsConfirmParameterData $parameters
     * @param \App\DTO\ConfirmPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function confirmResult(
        \App\DTO\PaymentsConfirmParameterData $parameters,
        \App\DTO\ConfirmPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->confirm($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region create
    /**
     * Create Payment
     * @param \App\DTO\CreatePaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function createRaw(
        \App\DTO\CreatePaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/payments', [], []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Create Payment
     * @param \App\DTO\CreatePaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function create(
        \App\DTO\CreatePaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->createRaw($requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Create Payment
     * @param \App\DTO\CreatePaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function createResult(
        \App\DTO\CreatePaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->create($requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region create_0
    /**
     * Create Subscription
     * @param \App\DTO\CreateSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function create_0Raw(
        \App\DTO\CreateSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/subscriptions', [], []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Create Subscription
     * @param \App\DTO\CreateSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function create_0(
        \App\DTO\CreateSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->create_0Raw($requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A subscription object */
                $responseContent = new \App\DTO\Subscription();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Create Subscription
     * @param \App\DTO\CreateSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Subscription
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function create_0Result(
        \App\DTO\CreateSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Subscription
    {
        return $this->getSuccessfulContent(...$this->create_0($requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region get
    /**
     * Get Payment
     * @param \App\DTO\PaymentsGetParameterData $parameters
     * @param iterable|string[][] $security
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function getRaw(
        \App\DTO\PaymentsGetParameterData $parameters,
        iterable $security = ['APIKey' => []],
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('GET', '/payments/{id}', $this->getPathParameters($parameters), []);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Get Payment
     * @param \App\DTO\PaymentsGetParameterData $parameters
     * @param iterable|string[][] $security
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function get(
        \App\DTO\PaymentsGetParameterData $parameters,
        iterable $security = ['APIKey' => []],
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->getRaw($parameters, $security, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Get Payment
     * @param \App\DTO\PaymentsGetParameterData $parameters
     * @param iterable|string[][] $security
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function getResult(
        \App\DTO\PaymentsGetParameterData $parameters,
        iterable $security = ['APIKey' => []],
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->get($parameters, $security, $responseMediaType));
    }
    //endregion

    //region get_0
    /**
     * Get Subscription
     * @param \App\DTO\SubscriptionsGetParameterData $parameters
     * @param iterable|string[][] $security
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function get_0Raw(
        \App\DTO\SubscriptionsGetParameterData $parameters,
        iterable $security = ['APIKey' => []],
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('GET', '/subscriptions/{id}', $this->getPathParameters($parameters), []);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Get Subscription
     * @param \App\DTO\SubscriptionsGetParameterData $parameters
     * @param iterable|string[][] $security
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function get_0(
        \App\DTO\SubscriptionsGetParameterData $parameters,
        iterable $security = ['APIKey' => []],
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->get_0Raw($parameters, $security, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A subscription object */
                $responseContent = new \App\DTO\Subscription();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Get Subscription
     * @param \App\DTO\SubscriptionsGetParameterData $parameters
     * @param iterable|string[][] $security
     * @param string $responseMediaType
     * @return \App\DTO\Subscription
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function get_0Result(
        \App\DTO\SubscriptionsGetParameterData $parameters,
        iterable $security = ['APIKey' => []],
        string $responseMediaType = 'application/json'
    ): \App\DTO\Subscription
    {
        return $this->getSuccessfulContent(...$this->get_0($parameters, $security, $responseMediaType));
    }
    //endregion

    //region pause
    /**
     * Pause Subscription
     * @param \App\DTO\SubscriptionsPauseParameterData $parameters
     * @param \App\DTO\PauseSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function pauseRaw(
        \App\DTO\SubscriptionsPauseParameterData $parameters,
        \App\DTO\PauseSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/subscriptions/{id}/pause', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Pause Subscription
     * @param \App\DTO\SubscriptionsPauseParameterData $parameters
     * @param \App\DTO\PauseSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function pause(
        \App\DTO\SubscriptionsPauseParameterData $parameters,
        \App\DTO\PauseSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->pauseRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A subscription object */
                $responseContent = new \App\DTO\Subscription();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Pause Subscription
     * @param \App\DTO\SubscriptionsPauseParameterData $parameters
     * @param \App\DTO\PauseSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Subscription
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function pauseResult(
        \App\DTO\SubscriptionsPauseParameterData $parameters,
        \App\DTO\PauseSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Subscription
    {
        return $this->getSuccessfulContent(...$this->pause($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region recurring
    /**
     * Recurring Payment
     * @param \App\DTO\PaymentsRecurringParameterData $parameters
     * @param \App\DTO\RecurringPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function recurringRaw(
        \App\DTO\PaymentsRecurringParameterData $parameters,
        \App\DTO\RecurringPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/payments/{sequenceId}/recurring', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Recurring Payment
     * @param \App\DTO\PaymentsRecurringParameterData $parameters
     * @param \App\DTO\RecurringPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function recurring(
        \App\DTO\PaymentsRecurringParameterData $parameters,
        \App\DTO\RecurringPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->recurringRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Recurring Payment
     * @param \App\DTO\PaymentsRecurringParameterData $parameters
     * @param \App\DTO\RecurringPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function recurringResult(
        \App\DTO\PaymentsRecurringParameterData $parameters,
        \App\DTO\RecurringPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->recurring($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region refund
    /**
     * Refund Payment
     * @param \App\DTO\PaymentsRefundParameterData $parameters
     * @param \App\DTO\RefundPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function refundRaw(
        \App\DTO\PaymentsRefundParameterData $parameters,
        \App\DTO\RefundPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/payments/{id}/refund', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Refund Payment
     * @param \App\DTO\PaymentsRefundParameterData $parameters
     * @param \App\DTO\RefundPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function refund(
        \App\DTO\PaymentsRefundParameterData $parameters,
        \App\DTO\RefundPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->refundRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Refund Payment
     * @param \App\DTO\PaymentsRefundParameterData $parameters
     * @param \App\DTO\RefundPaymentRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function refundResult(
        \App\DTO\PaymentsRefundParameterData $parameters,
        \App\DTO\RefundPaymentRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->refund($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region register
    /**
     * Register
     * @param \App\DTO\RegisterDomainRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function registerRaw(
        \App\DTO\RegisterDomainRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/apple-pay/domains', [], []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Register
     * @param \App\DTO\RegisterDomainRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function register(
        \App\DTO\RegisterDomainRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->registerRaw($requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A successful response */
                $responseContent = new \App\DTO\DomainRegister200Response();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Register
     * @param \App\DTO\RegisterDomainRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\DomainRegister200Response
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function registerResult(
        \App\DTO\RegisterDomainRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\DomainRegister200Response
    {
        return $this->getSuccessfulContent(...$this->register($requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region resume
    /**
     * Resume Subscription
     * @param \App\DTO\SubscriptionsResumeParameterData $parameters
     * @param iterable|string[][] $security
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function resumeRaw(
        \App\DTO\SubscriptionsResumeParameterData $parameters,
        iterable $security = ['APIKey' => []],
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/subscriptions/{id}/resume', $this->getPathParameters($parameters), []);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Resume Subscription
     * @param \App\DTO\SubscriptionsResumeParameterData $parameters
     * @param iterable|string[][] $security
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function resume(
        \App\DTO\SubscriptionsResumeParameterData $parameters,
        iterable $security = ['APIKey' => []],
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->resumeRaw($parameters, $security, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A subscription object */
                $responseContent = new \App\DTO\Subscription();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Resume Subscription
     * @param \App\DTO\SubscriptionsResumeParameterData $parameters
     * @param iterable|string[][] $security
     * @param string $responseMediaType
     * @return \App\DTO\Subscription
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function resumeResult(
        \App\DTO\SubscriptionsResumeParameterData $parameters,
        iterable $security = ['APIKey' => []],
        string $responseMediaType = 'application/json'
    ): \App\DTO\Subscription
    {
        return $this->getSuccessfulContent(...$this->resume($parameters, $security, $responseMediaType));
    }
    //endregion

    //region sendLink
    /**
     * Send Payment Link
     * @param \App\DTO\PaymentsSendLinkParameterData $parameters
     * @param \App\DTO\SendPaymentLinkRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function sendLinkRaw(
        \App\DTO\PaymentsSendLinkParameterData $parameters,
        \App\DTO\SendPaymentLinkRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/payments/{id}/link', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Send Payment Link
     * @param \App\DTO\PaymentsSendLinkParameterData $parameters
     * @param \App\DTO\SendPaymentLinkRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function sendLink(
        \App\DTO\PaymentsSendLinkParameterData $parameters,
        \App\DTO\SendPaymentLinkRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->sendLinkRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Send Payment Link
     * @param \App\DTO\PaymentsSendLinkParameterData $parameters
     * @param \App\DTO\SendPaymentLinkRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function sendLinkResult(
        \App\DTO\PaymentsSendLinkParameterData $parameters,
        \App\DTO\SendPaymentLinkRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->sendLink($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region sendReceipt
    /**
     * Send Payment Receipt
     * @param \App\DTO\PaymentsSendReceiptParameterData $parameters
     * @param \App\DTO\SendPaymentReceiptRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function sendReceiptRaw(
        \App\DTO\PaymentsSendReceiptParameterData $parameters,
        \App\DTO\SendPaymentReceiptRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('POST', '/payments/{id}/receipt', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Send Payment Receipt
     * @param \App\DTO\PaymentsSendReceiptParameterData $parameters
     * @param \App\DTO\SendPaymentReceiptRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function sendReceipt(
        \App\DTO\PaymentsSendReceiptParameterData $parameters,
        \App\DTO\SendPaymentReceiptRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->sendReceiptRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A payment object */
                $responseContent = new \App\DTO\Payment();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Send Payment Receipt
     * @param \App\DTO\PaymentsSendReceiptParameterData $parameters
     * @param \App\DTO\SendPaymentReceiptRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Payment
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function sendReceiptResult(
        \App\DTO\PaymentsSendReceiptParameterData $parameters,
        \App\DTO\SendPaymentReceiptRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Payment
    {
        return $this->getSuccessfulContent(...$this->sendReceipt($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion

    //region update
    /**
     * Update Subscription
     * @param \App\DTO\SubscriptionsUpdateParameterData $parameters
     * @param \App\DTO\UpdateSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     */
    public function updateRaw(
        \App\DTO\SubscriptionsUpdateParameterData $parameters,
        \App\DTO\UpdateSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): ResponseInterface
    {
        $request = $this->createRequest('PUT', '/subscriptions/{id}', $this->getPathParameters($parameters), []);
        $request = $this->addBody($request, $requestMediaType, $requestContent);
        $request = $this->addAcceptHeader($request, $responseMediaType);
        $request = $this->addSecurity($request, $security);
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Update Subscription
     * @param \App\DTO\SubscriptionsUpdateParameterData $parameters
     * @param \App\DTO\UpdateSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return array
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     */
    public function update(
        \App\DTO\SubscriptionsUpdateParameterData $parameters,
        \App\DTO\UpdateSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): array
    {
        $response = $this->updateRaw($parameters, $requestContent, $security, $requestMediaType, $responseMediaType);
        $responseContent = null;
        switch ($response->getStatusCode())
        {
            case 200:
                /* A subscription object */
                $responseContent = new \App\DTO\Subscription();
                break;
        }
        $this->parseBody($response, $responseContent);
        return [$responseContent, $response->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    /**
     * Update Subscription
     * @param \App\DTO\SubscriptionsUpdateParameterData $parameters
     * @param \App\DTO\UpdateSubscriptionRequest $requestContent
     * @param iterable|string[][] $security
     * @param string $requestMediaType
     * @param string $responseMediaType
     * @return \App\DTO\Subscription
     * @throws ClientExceptionInterface
     * @throws DT\Exception\InvalidData
     * @throws OAGAC\Exception\InvalidResponseBodySchema
     * @throws OAGAC\Exception\UnsuccessfulResponse
     */
    public function updateResult(
        \App\DTO\SubscriptionsUpdateParameterData $parameters,
        \App\DTO\UpdateSubscriptionRequest $requestContent,
        iterable $security = ['APIKey' => []],
        string $requestMediaType = 'application/json',
        string $responseMediaType = 'application/json'
    ): \App\DTO\Subscription
    {
        return $this->getSuccessfulContent(...$this->update($parameters, $requestContent, $security, $requestMediaType, $responseMediaType));
    }
    //endregion
}

