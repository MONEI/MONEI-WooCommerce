<?php

namespace Monei\Services;

class WpOptionStore implements OptionStore {

	public function get( string $name, $default = '' ) {
		return get_option( $name, $default );
	}

	public function set( string $name, $value, ?bool $autoload = null ): void {
		update_option( $name, $value, $autoload );
	}

	public function delete( string $name ): bool {
		// False when another request deleted the row first: the DELETE then affects no row.
		return delete_option( $name );
	}

	public function names_with_prefix( string $prefix ): array {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $prefix ) . '%' )
		);
	}
}
