<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Logger Helper Class
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_Logger {

	public static $logger;
	const WC_LOG_FILENAME = 'monei-logs';

	/**
	 * Utilize WC logger class
	 * Always log errors, debug only when on settings.
	 *
	 * @param string|array $message
	 * @param string $error_level
	 *
	 * @since 5.0
	 * @version 5.0
	 */
	public static function log( $message, $error_level = 'debug' ) {

		if ( empty( self::$logger ) ) {
			self::$logger = wc_get_logger();
		}

		switch ( $message ) {
			case is_object( $message ):
				$message = print_r( (array) $message, true );
				break;
			case is_array( $message ):
				$message = print_r( $message, true );
				break;
			default:
				break;
		}

		$log_entry  = "\n" . '==== MONEI Version: ' . WC_Monei()->version . '====' . "\n";
		$log_entry .= '====Start Log====' . "\n" . $message . "\n" . '====End Log====' . "\n";

		self::$logger->log( $error_level, $log_entry, [ 'source' => self::WC_LOG_FILENAME ] );
	}

}

