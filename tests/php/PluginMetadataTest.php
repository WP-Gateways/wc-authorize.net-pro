<?php
namespace WCAuthnet\Tests;

/**
 * The plugin's versions and minimum requirements agree everywhere they are stated: the main plugin
 * file's headers, description and constants, readme.txt and package.json.
 *
 * Minimums (checked before each release; see the changelog):
 * - WooCommerce 7.6: the block scripts use the onPaymentSetup / onCheckoutFail events (WooCommerce
 *   Blocks 9.7, first in WooCommerce 7.6).
 * - WordPress 6.6: the block scripts depend on the react-jsx-runtime script, added in WordPress 6.6.
 * - PHP 7.2: the code needs 7.1 (a void return type); WooCommerce 7.6 needs 7.2.
 */
class PluginMetadataTest extends TestCase {

	const MIN_WC  = '7.6';
	const MIN_WP  = '6.6';
	const MIN_PHP = '7.2';

	private static function file( $name ) {
		return file_get_contents( WC_AUTHNET_PLUGIN_PATH . '/' . $name );
	}

	private static function header( $contents, $name ) {
		preg_match( '/^[ \t\/*#@]*' . preg_quote( $name, '/' ) . ':\s*(.+)$/mi', $contents, $match );
		return isset( $match[1] ) ? trim( $match[1] ) : null;
	}

	private static function constant( $name ) {
		preg_match( "/define\(\s*'" . $name . "',\s*'([^']+)'\s*\)/", self::file( 'gateway.php' ), $match );
		return isset( $match[1] ) ? $match[1] : null;
	}

	public function test_the_main_file_states_the_minimums() {
		$main = self::file( 'gateway.php' );

		$this->assertSame( self::MIN_WC, self::header( $main, 'WC requires at least' ) );
		$this->assertSame( self::MIN_WP, self::header( $main, 'Requires at least' ) );
		$this->assertSame( self::MIN_PHP, self::header( $main, 'Requires PHP' ) );
		$this->assertStringContainsString( 'Requires WC ' . self::MIN_WC . '+', self::header( $main, 'Description' ) );
	}

	public function test_the_environment_check_uses_the_minimums() {
		$this->assertSame( self::MIN_WC, self::constant( 'WC_AUTHNET_MIN_WC_VER' ) );
		$this->assertSame( self::MIN_PHP, self::constant( 'WC_AUTHNET_MIN_PHP_VER' ) );
	}

	public function test_the_readme_states_the_minimums() {
		$readme = self::file( 'readme.txt' );

		$this->assertSame( self::MIN_WP, self::header( $readme, 'Requires at least' ) );
		$this->assertSame( self::MIN_PHP, self::header( $readme, 'Requires PHP' ) );
		$this->assertStringContainsString( 'version ' . self::MIN_WC . ' or later', $readme );
	}

	public function test_the_version_agrees_everywhere() {
		$version = self::header( self::file( 'gateway.php' ), 'Version' );

		$this->assertSame( $version, self::constant( 'WC_AUTHNET_VERSION' ) );
		$this->assertSame( $version, self::header( self::file( 'readme.txt' ), 'Stable tag' ) );
		$this->assertSame( $version, json_decode( self::file( 'package.json' ), true )['version'] );
	}
}
