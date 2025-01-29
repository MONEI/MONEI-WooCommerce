<?php

namespace Monei\Services;

class BlockSupportService {
	private string $blocksPath;
	private string $namespacePrefix;

	public function __construct( string $blocksPath, string $namespacePrefix ) {
		$this->blocksPath      = $blocksPath;
		$this->namespacePrefix = $namespacePrefix;
	}

	/**
	 * Discover and return all block support classes.
	 *
	 * @return string[] List of fully qualified class names.
	 */
	public function getBlockSupportClasses(): array {
		$blockSupportClasses = array();

		foreach ( glob( $this->blocksPath . '/*BlocksSupport.php' ) as $file ) {
			$className = $this->namespacePrefix . pathinfo( $file, PATHINFO_FILENAME );
			if ( class_exists( $className ) ) {
				$blockSupportClasses[] = $className;
			}
		}

		return $blockSupportClasses;
	}
}
