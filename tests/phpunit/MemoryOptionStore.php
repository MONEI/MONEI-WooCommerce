<?php
/**
 * WordPress options in memory, for ConnectService and CallbackVerifier.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Services\OptionStore;

class MemoryOptionStore implements OptionStore {

	/** @var array<string, mixed> */
	public $options = array();
	/** @var array<string, bool|null> */
	public $autoload = array();
	/** @var string[] Options that another request deletes first: this delete removes nothing. */
	public $lost_deletes = array();

	public function __construct( array $options = array() ) {
		$this->options = $options;
	}

	public function get( string $name, $default = '' ) {
		return array_key_exists( $name, $this->options ) ? $this->options[ $name ] : $default;
	}

	public function set( string $name, $value, ?bool $autoload = null ): void {
		$this->options[ $name ]  = $value;
		$this->autoload[ $name ] = $autoload;
	}

	public function delete( string $name ): bool {
		$existed = array_key_exists( $name, $this->options );
		unset( $this->options[ $name ] );
		return $existed && ! in_array( $name, $this->lost_deletes, true );
	}

	public function names_with_prefix( string $prefix ): array {
		return array_values(
			array_filter(
				array_keys( $this->options ),
				function ( $name ) use ( $prefix ) {
					return 0 === strpos( $name, $prefix );
				}
			)
		);
	}
}
