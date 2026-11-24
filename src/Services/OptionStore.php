<?php

namespace Monei\Services;

/**
 * The WordPress options that ConnectService and CallbackVerifier read and write.
 *
 * An interface, so the PHPUnit suite runs both classes without WordPress.
 */
interface OptionStore {

	/**
	 * @param string $name    Option name.
	 * @param mixed  $default Value when the option is not set.
	 * @return mixed
	 */
	public function get( string $name, $default = '' );

	/**
	 * @param string    $name     Option name.
	 * @param mixed     $value    Value.
	 * @param bool|null $autoload Null keeps the autoload of an existing option.
	 */
	public function set( string $name, $value, ?bool $autoload = null ): void;

	/**
	 * @return bool True only when this call removed the option.
	 */
	public function delete( string $name ): bool;

	/**
	 * @return string[] Names of the options that start with the prefix.
	 */
	public function names_with_prefix( string $prefix ): array;
}
