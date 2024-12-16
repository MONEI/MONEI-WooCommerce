<?php

namespace Monei\Repositories;

interface PaymentMethodsRepositoryInterface {
    public function getPaymentMethods(): array;
}