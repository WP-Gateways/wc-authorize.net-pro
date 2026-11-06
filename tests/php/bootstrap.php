<?php
/**
 * PHPUnit bootstrap.
 *
 * These are true unit tests: WordPress, WooCommerce and Freemius are not loaded. The WooCommerce classes
 * the plugin uses are stubbed (tests/php/stubs), and gateways are created without their constructors, so
 * the real plugin methods run in isolation. Brain Monkey stubs WordPress functions per test, and
 * tests/php/Fakes/AuthnetApi.php stands in for Authorize.net behind wp_remote_post().
 *
 * Only the free plugin's classes are loaded: the card gateway, the API client, the logger and the block
 * checkout support. (The Legacy AIM gateway has the same class name as the card gateway, so it can't be
 * loaded alongside it.)
 *
 * gateway.php is not loaded: it does nothing when wc_authnet_fs() already exists (as it does here, from
 * the Freemius stub), so the plugin constants and class files are loaded directly.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );

define( 'WC_AUTHNET_VERSION', 'test' );
define( 'WC_AUTHNET_MIN_PHP_VER', '7.4' );
define( 'WC_AUTHNET_MIN_WC_VER', '3.3' );
define( 'WC_AUTHNET_PLUGIN_PATH', dirname( __DIR__, 2 ) );
define( 'WC_AUTHNET_PLUGIN_URL', 'https://example.com/wp-content/plugins/woo-authorize-net-gateway-aim' );
define( 'WC_AUTHNET_MAIN_FILE', WC_AUTHNET_PLUGIN_PATH . '/gateway.php' );

// WordPress functions with their WordPress behaviour, used by most tests.
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value );
	}
}

if ( ! function_exists( 'wc_clean' ) ) {
	function wc_clean( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wc_clean', $value );
		}
		return is_scalar( $value ) ? trim( strip_tags( (string) $value ) ) : $value;
	}
}

require_once __DIR__ . '/stubs/woocommerce.php';
require_once __DIR__ . '/stubs/freemius.php';
require_once __DIR__ . '/stubs/plugin.php';

require_once WC_AUTHNET_PLUGIN_PATH . '/includes/class-wc-gateway-authnet-logger.php';
require_once WC_AUTHNET_PLUGIN_PATH . '/includes/class-wc-authnet-api.php';
require_once WC_AUTHNET_PLUGIN_PATH . '/includes/class-wc-authnet-address.php';
require_once WC_AUTHNET_PLUGIN_PATH . '/includes/class-wc-gateway-authnet.php';
require_once WC_AUTHNET_PLUGIN_PATH . '/includes/class-wc-authnet-blocks-support.php';
