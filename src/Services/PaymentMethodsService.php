<?php

namespace Monei\Services;

use Monei\Repositories\PaymentMethodsRepositoryInterface;

class PaymentMethodsService
{
    public const GOOGLE_API = 'googlePay';
    public const APPLE_API = 'applePay';
    private $repository;

    public function __construct(PaymentMethodsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Parse and return enabled payment methods.
     */
    public function getEnabledPaymentMethods(): array
    {
        $data = $this->repository->getPaymentMethods();

        $enabledMethods = [];
        foreach ($data['paymentMethods'] ?? [] as $method) {
            $metadata = $data['metadata'][$method] ?? [];
            $enabledMethods[$method] = [
                'countries' => $metadata['countries'] ?? null,
                'details' => $metadata,
            ];
        }

        return $enabledMethods;
    }

    public function isGoogleEnabled():bool
    {
        $enabledMethods = $this->getEnabledPaymentMethods();
        return isset($enabledMethods[self::GOOGLE_API]);
    }

    public function isAppleEnabled():bool
    {
        $enabledMethods = $this->getEnabledPaymentMethods();
        return isset($enabledMethods[self::APPLE_API]);
    }

    /**
     * Get availability of a specific payment method.
     *
     * @param string $methodId Payment method ID (e.g., 'bizum', 'card').
     * @param string $accountId
     * @return array|null Availability details or null if the method is not enabled.
     */
    public function getMethodAvailability(string $methodId): ?array {
        $paymentData = $this->repository->getPaymentMethods();
        $methodIdToApiMap = [
            'monei'               => 'card',
            'monei_apple_google'  => 'applePay',
            'monei_google'        => 'googlePay',
            'monei_bizum'         => 'bizum',
            'monei_mbway'         => 'mbway',
            'monei_multibanco'    => 'multibanco',
            'monei_paypal'        => 'paypal'
        ];

        if (isset($methodIdToApiMap[$methodId])) {
            $apiName = $methodIdToApiMap[$methodId];
            if (in_array($apiName, $paymentData['paymentMethods'] ?? [], true)) {
                $metadata = $paymentData['metadata'][$apiName] ?? [];
                $data = [
                    'enabled'   => true,
                    'countries' => $metadata['countries'] ?? null,
                    'details'   => $metadata,
                ];
                if($methodId === 'monei_apple_google') {
                    $data['googlePay'] = in_array('googlePay', $paymentData['paymentMethods']);
                    $data['applePay'] = in_array('applePay', $paymentData['paymentMethods']);
                }
                return $data;
            }
        }
        return null;
    }
}
