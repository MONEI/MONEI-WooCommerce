<?php

namespace Monei\Core;

use DI\Container;
use DI\ContainerBuilder;

class ContainerProvider {
	private static ?Container $container = null;

	public static function getContainer(): Container {
		if ( self::$container === null ) {
			self::buildContainer();
		}
		return self::$container;
	}

	private static function buildContainer(): void {
		$containerBuilder = new ContainerBuilder();
		$containerBuilder->addDefinitions( __DIR__ . '/container-definitions.php' );
		self::$container = $containerBuilder->build();
	}
}
