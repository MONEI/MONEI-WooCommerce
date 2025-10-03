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
use Monei\Services\PaymentMethodsService;
use Monei\Services\sdk\MoneiSdkClientFactory;
use Monei\Templates\NoticeAdminDependency;
use Monei\Templates\NoticeAdminNewInstall;
use Monei\Templates\NoticeGatewayNotAvailable;
use Monei\Templates\NoticeGatewayNotAvailableApi;
use Monei\Templates\NoticeGatewayNotEnabledMonei;
use Monei\Templates\SettingsHeader;
use Monei\Templates\TemplateManager;

$blocksPath             = dirname( __DIR__, 1 ) . '/Gateways/Blocks';
$gatewayPath            = dirname( __DIR__, 1 ) . '/Gateways/PaymentMethods';
$gatewayNamespacePrefix = 'Monei\\Gateways\\PaymentMethods\\';
$blockNamespacePrefix   = 'Monei\\Gateways\\Blocks\\';
$definitions            = array(
	// ========== TEMPLATES ==========
	// Register each template as an autowired service
	NoticeAdminNewInstall::class            => DI\autowire( NoticeAdminNewInstall::class ),
	SettingsHeader::class                   => DI\autowire( SettingsHeader::class ),
	NoticeAdminDependency::class            => DI\autowire( NoticeAdminDependency::class ),
	NoticeGatewayNotAvailable::class        => DI\autowire( NoticeGatewayNotAvailable::class ),
	NoticeGatewayNotAvailableApi::class     => DI\autowire( NoticeGatewayNotAvailableApi::class ),
	NoticeGatewayNotEnabledMonei::class     => DI\autowire( NoticeGatewayNotEnabledMonei::class ),

	// array of [ 'short-template-name' => <template-class-instance> ]
	TemplateManager::class                  => DI\create( TemplateManager::class )
		->constructor(
			array(
				'notice-admin-new-install'               => DI\get( NoticeAdminNewInstall::class ),
				'monei-settings-header'                  => DI\get( SettingsHeader::class ),
				'notice-admin-dependency'                => DI\get( NoticeAdminDependency::class ),
				'notice-admin-gateway-not-available'     => DI\get( NoticeGatewayNotAvailable::class ),
				'notice-admin-gateway-not-available-api' => DI\get( NoticeGatewayNotAvailableApi::class ),
				'notice-admin-gateway-not-enabled-monei' => DI\get( NoticeGatewayNotEnabledMonei::class ),
			)
		),
	ApiKeyService::class                    => DI\autowire( ApiKeyService::class ),
	MoneiSdkClientFactory::class            => DI\autowire( MoneiSdkClientFactory::class )
		->constructor( DI\get( ApiKeyService::class ) ),
	PaymentMethodsRepository::class         => DI\factory(
		function ( ApiKeyService $apiKeyService, MoneiSdkClientFactory $sdkClientFactory ) {
			return new Monei\Repositories\PaymentMethodsRepository( $apiKeyService->get_account_id(), $sdkClientFactory->get_client() );
		}
	),
	PaymentMethodsService::class            => DI\create( PaymentMethodsService::class )
		->constructor( DI\get( PaymentMethodsRepository::class ) ),
	CardBrandHelper::class                  => DI\create( CardBrandHelper::class )
		->constructor( DI\get( PaymentMethodsService::class ) ),
	MoneiPaymentServices::class             => DI\autowire( MoneiPaymentServices::class ),
	MoneiStatusCodeHandler::class           => DI\autowire( MoneiStatusCodeHandler::class ),
	BlockSupportService::class              => DI\create( BlockSupportService::class )
		->constructor( $blocksPath, $blockNamespacePrefix ),
	MoneiApplePayVerificationService::class => DI\autowire( MoneiApplePayVerificationService::class )
		->constructor( DI\get( MoneiPaymentServices::class ) ),
	WooCommerceSubscriptionsHandler::class  => \DI\create(
		WooCommerceSubscriptionsHandler::class,
	)->constructor(
		DI\get( MoneiSdkClientFactory::class )
	),
	YithSubscriptionPluginHandler::class    => \DI\autowire( YithSubscriptionPluginHandler::class ),

	SubscriptionService::class              => \DI\autowire( SubscriptionService::class )
	->constructorParameter( 'wooHandler', \DI\get( WooCommerceSubscriptionsHandler::class ) )
	->constructorParameter( 'yithHandler', \DI\get( YithSubscriptionPluginHandler::class ) ),
);

// Dynamically load all gateway classes in the folder
foreach ( glob( $gatewayPath . '/*.php' ) as $file ) {
	$className = $gatewayNamespacePrefix . pathinfo( $file, PATHINFO_FILENAME );

	if ( class_exists( $className ) ) {
		$definitions[ $className ] = DI\autowire();
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
		$definitions[ $blockClassName ] = DI\autowire()
			->constructorParameter( 'gateway', DI\get( $gatewayClassName ) );

		// Inject CardBrandHelper only for CC blocks support
		if ( $blockClassName === 'Monei\\Gateways\\Blocks\\MoneiCCBlocksSupport' ) {
			$definitions[ $blockClassName ] = $definitions[ $blockClassName ]
				->constructorParameter( 'cardBrandHelper', DI\get( CardBrandHelper::class ) );
		}
	}
}

return $definitions;
