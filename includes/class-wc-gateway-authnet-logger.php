<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AuthNet logging class which saves important data to the log
 *
 * @since 2.6.10
 */
class WC_AuthNet_Logger {

	public static $logger;

	/**
	 * What rolls down stairs
	 * alone or in pairs,
	 * and over your neighbor's dog?
	 * What's great for a snack,
	 * And fits on your back?
	 * It's log, log, log
	 *
	 * @since 2.6.10
	 */
	public static function log( $message ) {

		if ( empty( self::$logger ) ) {
			self::$logger = new WC_Logger();
		}

		self::$logger->add( 'woocommerce-gateway-authnet', $message );

	}
}

new WC_AuthNet_Logger();
