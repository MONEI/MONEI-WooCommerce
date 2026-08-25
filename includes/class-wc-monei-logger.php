<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Logger Helper Class
 *
 * Log levels (following PrestaShop MONEI plugin pattern):
 * 1 = INFO (debug/all messages)
 * 2 = WARNING (warnings and errors)
 * 3 = ERROR (errors only)
 * 4 = NONE (logging disabled)
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_Logger {

	public static $logger;
	const WC_LOG_FILENAME = 'monei-logs';

	// Log level constants (matching PrestaShop)
	const LEVEL_INFO    = 1;
	const LEVEL_WARNING = 2;
	const LEVEL_ERROR   = 3;
	const LEVEL_NONE    = 4;

	/**
	 * Main logging method with level-based filtering
	 *
	 * @param string|array|callable $message Message to log, or callable that returns message (for lazy evaluation).
	 * @param int                   $severity Log level (1=INFO, 2=WARNING, 3=ERROR, 4=NONE).
	 *
	 * @since 5.0
	 * @version 5.0
	 */
	public static function log( $message, $severity = self::LEVEL_INFO ) {
		$severity      = self::normalize_severity( $severity );
		$min_log_level = (int) get_option( 'monei_log_level', self::LEVEL_ERROR ); // Default to ERROR only

		// Treat 4 (NONE) as disabled
		if ( $min_log_level === self::LEVEL_NONE ) {
			return;
		}

		// Only log if the severity is at or above the configured minimum level
		if ( $severity < $min_log_level ) {
			return;
		}

		// Lazy evaluation: if message is callable, invoke it only when logging is enabled
		if ( is_callable( $message ) ) {
			$message = $message();
		}

		if ( empty( self::$logger ) ) {
			self::$logger = wc_get_logger();
		}

		// Convert message to string if needed
		switch ( $message ) {
			case is_object( $message ):
				$message = print_r( (array) $message, true );//phpcs:ignore
				break;
			case is_array( $message ):
				$message = print_r( $message, true );//phpcs:ignore
				break;
			default:
				break;
		}

		// Map severity to WC log level
		$wc_log_level = self::map_severity_to_wc_level( $severity );

		$log_entry  = "\n" . '==== MONEI Version: ' . WC_Monei()->version . '====' . "\n";
		$log_entry .= '====Start Log====' . "\n" . $message . "\n" . '====End Log====' . "\n";

		self::$logger->log( $wc_log_level, $log_entry, array( 'source' => self::WC_LOG_FILENAME ) );
	}

	/**
	 * Convenience method for debug/info logging
	 *
	 * @param string|array|callable $message Message to log.
	 */
	public static function logDebug( $message ) {
		self::log( $message, self::LEVEL_INFO );
	}

	/**
	 * Convenience method for warning logging
	 *
	 * @param string|array|callable $message Message to log.
	 */
	public static function logWarning( $message ) {
		self::log( $message, self::LEVEL_WARNING );
	}

	/**
	 * Convenience method for error logging
	 *
	 * @param string|array|callable $message Message to log.
	 */
	public static function logError( $message ) {
		self::log( $message, self::LEVEL_ERROR );
	}

	/**
	 * Coerce a severity to one of the integer levels.
	 *
	 * ⚠️ Callers used to pass the WooCommerce level *name* — `'debug'`, `'error'` —
	 * where an int belongs, and both effects were silent. The filter below compares
	 * `$severity < $min_log_level`, so PHP cast the int to string and compared
	 * `'debug' < '3'` character by character: every name sorted above every level and
	 * logged unconditionally, ignoring the merchant's `monei_log_level`. The name then
	 * fell through `map_severity_to_wc_level()`'s default and was written as ERROR, so
	 * routine successes filled the error log.
	 *
	 * Names are accepted rather than rejected because a third-party integration may
	 * still pass one, and dropping its message would be worse than filing it.
	 *
	 * @param mixed $severity Integer level, or a WooCommerce level name.
	 *
	 * @return int One of the LEVEL_* constants.
	 */
	private static function normalize_severity( $severity ) {
		if ( is_int( $severity ) || ctype_digit( (string) $severity ) ) {
			return (int) $severity;
		}

		switch ( strtolower( (string) $severity ) ) {
			case 'debug':
			case 'info':
			case 'notice':
				return self::LEVEL_INFO;
			case 'warning':
				return self::LEVEL_WARNING;
			default:
				return self::LEVEL_ERROR;
		}
	}

	/**
	 * Map internal severity levels to WooCommerce log levels
	 *
	 * @param int $severity Internal severity level.
	 * @return string WooCommerce log level.
	 */
	private static function map_severity_to_wc_level( $severity ) {
		switch ( $severity ) {
			case self::LEVEL_INFO:
				return 'debug';
			case self::LEVEL_WARNING:
				return 'warning';
			case self::LEVEL_ERROR:
			default:
				return 'error';
		}
	}
}
