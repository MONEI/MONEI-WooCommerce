<?php

use Monei\Features\Subscriptions\SubscriptionService;
use Monei\Features\Subscriptions\WooCommerceSubscriptionsHandler;
use Monei\Features\Subscriptions\YithSubscriptionPluginHandler;
use Monei\Helpers\CardBrandHelper;
use Monei\Repositories\PaymentMethodsRepository;
use Monei\Services\ApiKeyService;
use Monei\Services\BlockSupportService;
use Monei\Services\MoneiApplePayVerificationService;
use Monei\Services\MoneiStatusCodeHandler;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\PaymentMethodFormatter;
use Monei\Services\PaymentMethodsService;
use Monei\Services\sdk\MoneiSdkClientFactory;
use Monei\Templates\NoticeAdminDependency;
use Monei\Templates\NoticeAdminNewInstall;
use Monei\Templates\NoticeGatewayNotAvailable;
use Monei\Templates\NoticeGatewayNotAvailableApi;
use Monei\Templates\NoticeGatewayNotEnabledMonei;
use Monei\Templates\SettingsHeader;
use Monei\Templates\TemplateManager;
use function DI\autowire;
use function DI\create;
use function DI\get;
use function DI\factory;

$blocksPath             = dirname( __DIR__, 1 ) . '/Gateways/Blocks';
$gatewayPath            = dirname( __DIR__, 1 ) . '/Gateways/PaymentMethods';
$gatewayNamespacePrefix = 'Monei\\Gateways\\PaymentMethods\\';
$blockNamespacePrefix   = 'Monei\\Gateways\\Blocks\\';
$definitions            = array(
	// ========== TEMPLATES ==========
	// Register each template as an autowired service
	NoticeAdminNewInstall::class            => autowire( NoticeAdminNewInstall::class ),
	SettingsHeader::class                   => autowire( SettingsHeader::class ),
	NoticeAdminDependency::class            => autowire( NoticeAdminDependency::class ),
	NoticeGatewayNotAvailable::class        => autowire( NoticeGatewayNotAvailable::class ),
	NoticeGatewayNotAvailableApi::class     => autowire( NoticeGatewayNotAvailableApi::class ),
	NoticeGatewayNotEnabledMonei::class     => autowire( NoticeGatewayNotEnabledMonei::class ),

	// array of [ 'short-template-name' => <template-class-instance> ]
	TemplateManager::class                  => create( TemplateManager::class )
		->constructor(
			array(
				'notice-admin-new-install'               => get( NoticeAdminNewInstall::class ),
				'monei-settings-header'                  => get( SettingsHeader::class ),
				'notice-admin-dependency'                => get( NoticeAdminDependency::class ),
				'notice-admin-gateway-not-available'     => get( NoticeGatewayNotAvailable::class ),
				'notice-admin-gateway-not-available-api' => get( NoticeGatewayNotAvailableApi::class ),
				'notice-admin-gateway-not-enabled-monei' => get( NoticeGatewayNotEnabledMonei::class ),
			)
		),
	ApiKeyService::class                    => autowire( ApiKeyService::class ),
	MoneiSdkClientFactory::class            => autowire( MoneiSdkClientFactory::class )
		->constructor( get( ApiKeyService::class ) ),
	PaymentMethodsRepository::class         => factory(
		function ( ApiKeyService $apiKeyService, MoneiSdkClientFactory $sdkClientFactory ) {
			return new PaymentMethodsRepository( $apiKeyService->get_account_id(), $sdkClientFactory->get_client() );
		}
	),
	PaymentMethodsService::class            => create( PaymentMethodsService::class )
		->constructor( get( PaymentMethodsRepository::class ) ),
	CardBrandHelper::class                  => create( CardBrandHelper::class )
		->constructor( get( PaymentMethodsService::class ) ),
	MoneiPaymentServices::class             => autowire( MoneiPaymentServices::class ),
	MoneiStatusCodeHandler::class           => autowire( MoneiStatusCodeHandler::class ),
	PaymentMethodFormatter::class           => autowire( PaymentMethodFormatter::class ),
	BlockSupportService::class              => create( BlockSupportService::class )
		->constructor( $blocksPath, $blockNamespacePrefix ),
	MoneiApplePayVerificationService::class => autowire( MoneiApplePayVerificationService::class )
		->constructor( get( MoneiPaymentServices::class ) ),
	WooCommerceSubscriptionsHandler::class  => create(
		WooCommerceSubscriptionsHandler::class,
	)->constructor(
		get( MoneiSdkClientFactory::class )
	),
	YithSubscriptionPluginHandler::class    => autowire( YithSubscriptionPluginHandler::class ),

	SubscriptionService::class              => autowire( SubscriptionService::class )
	->constructorParameter( 'wooHandler', get( WooCommerceSubscriptionsHandler::class ) )
	->constructorParameter( 'yithHandler', get( YithSubscriptionPluginHandler::class ) ),
);

// Dynamically load all gateway classes in the folder
foreach ( glob( $gatewayPath . '/*.php' ) as $file ) {
	$className = $gatewayNamespacePrefix . pathinfo( $file, PATHINFO_FILENAME );

	if ( class_exists( $className ) ) {
		$definitions[ $className ] = autowire();
	}
}

// Dynamically register block support classes
foreach ( glob( $blocksPath . '/*BlocksSupport.php' ) as $file ) {
	$blockClassName    = $blockNamespacePrefix . pathinfo( $file, PATHINFO_FILENAME );
	$gatewayNamePrefix = 'WCGateway';
	if ( class_exists( $blockClassName ) ) {
		// Derive the corresponding gateway class name
		$gatewayClassName = $gatewayNamespacePrefix . $gatewayNamePrefix . str_replace( 'BlocksSupport', '', pathinfo( $file, PATHINFO_FILENAME ) );

		// Register the block support class with the gateway as a dependency
		$definitions[ $blockClassName ] = autowire()
			->constructorParameter( 'gateway', get( $gatewayClassName ) );

		// Inject CardBrandHelper only for CC blocks support
		if ( $blockClassName === 'Monei\\Gateways\\Blocks\\MoneiCCBlocksSupport' ) {
			$definitions[ $blockClassName ] = $definitions[ $blockClassName ]
				->constructorParameter( 'cardBrandHelper', get( CardBrandHelper::class ) );
		}
	}
}

return $definitions;
