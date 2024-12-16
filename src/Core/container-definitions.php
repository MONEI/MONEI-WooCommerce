<?php

use Monei\Repositories\PaymentMethodsRepository;
use Monei\Services\BlockSupportService;
use Monei\Services\PaymentMethodsService;
$blocksPath = dirname(__DIR__, 1) . '/Gateways/Blocks';
$gatewayPath = dirname(__DIR__, 1) . '/Gateways/PaymentMethods';
$gatewayNamespacePrefix = 'Monei\\Gateways\\PaymentMethods\\';
$blockNamespacePrefix = 'Monei\\Gateways\\Blocks\\';
$definitions = [
    // Register services
    PaymentMethodsRepository::class => DI\factory(function () {
        $accountId = get_option('monei_accountid');
        return new Monei\Repositories\PaymentMethodsRepository($accountId);
    }),
    PaymentMethodsService::class => DI\create(PaymentMethodsService::class)
        ->constructor(DI\get(PaymentMethodsRepository::class)),
    BlockSupportService::class => DI\create(BlockSupportService::class)
        ->constructor($blocksPath, $blockNamespacePrefix),
];

// Dynamically load all gateway classes in the folder
foreach (glob($gatewayPath . '/*.php') as $file) {
    $className = $gatewayNamespacePrefix . pathinfo($file, PATHINFO_FILENAME);

    if (class_exists($className)) {
        $definitions[$className] = DI\autowire()
            ->constructorParameter('paymentMethodsService', DI\get(PaymentMethodsService::class));
    }
}

// Dynamically register block support classes
foreach (glob($blocksPath . '/*BlocksSupport.php') as $file) {
    $blockClassName = $blockNamespacePrefix . pathinfo($file, PATHINFO_FILENAME);
    $gatewayNamePrefix = 'WCGateway';
    if (class_exists($blockClassName)) {
        // Derive the corresponding gateway class name
        $gatewayClassName = $gatewayNamespacePrefix. $gatewayNamePrefix . str_replace('BlocksSupport', '', pathinfo($file, PATHINFO_FILENAME));

        // Register the block support class with the gateway as a dependency
        $definitions[$blockClassName] = DI\autowire()
            ->constructorParameter('gateway', DI\get($gatewayClassName));
    }
}

return $definitions;
