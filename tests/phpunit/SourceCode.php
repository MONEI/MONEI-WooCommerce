<?php
/**
 * Source of a plugin file for source-scan tests.
 *
 * @package Monei
 */

namespace Monei\Tests;

trait SourceCode {

	/**
	 * Source of a file with comments and whitespace removed, so a commented-out call does not count.
	 */
	private function code( string $relative_path ): string {
		$code = '';
		foreach ( token_get_all( file_get_contents( dirname( __DIR__, 2 ) . '/' . $relative_path ) ) as $token ) {
			if ( is_array( $token ) ) {
				if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT, T_WHITESPACE ), true ) ) {
					continue;
				}
				$token = $token[1];
			}
			$code .= $token;
		}
		return $code;
	}
}
