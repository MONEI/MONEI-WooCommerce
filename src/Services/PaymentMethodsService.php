<?php

namespace Monei\Services;

use Monei\Repositories\PaymentMethodsRepositoryInterface;

class PaymentMethodsService {
    private $repository;

    public function __construct(PaymentMethodsRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    /**
     * Parse and return enabled payment methods.
     */
    public function getEnabledPaymentMethods(): array {
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

    /**
     * Get availability of a specific payment method.
     *
     * @param string $methodId Payment method ID (e.g., 'bizum', 'card').
     * @param string $accountId
     * @return array|null Availability details or null if the method is not enabled.
     */
    public function getMethodAvailability(string $methodId): ?array {
        $data = $this->repository->getPaymentMethods();
        //todo rename methods as in api
        if($methodId === 'monei'){
            $methodId = 'card';
        } else {
            $methodId = explode('_', $methodId)[1];
        }
        if (in_array($methodId, $data['paymentMethods'] ?? [], true)) {
            $metadata = $data['metadata'][$methodId] ?? [];
            return [
                'enabled' => true,
                'countries' => $metadata['countries'] ?? null,
                'details' => $metadata
            ];
        }

        return null; // Method not available
    }
}
