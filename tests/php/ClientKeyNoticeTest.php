<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use WC_Gateway_Authnet;

/**
 * Without a Public Client Key, Accept.js can't tokenize at checkout and card numbers are posted to the
 * store's server. The admin is warned while the gateway is enabled.
 */
class ClientKeyNoticeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'admin_url' )->alias(
			function ( $path ) {
				return 'https://shop.example/wp-admin/' . $path;
			}
		);
		Functions\when( 'wc_checkout_is_https' )->justReturn( true );
	}

	private function notices( $class, array $properties ) {
		$gateway = $this->gateway(
			$class,
			array_merge(
				array(
					'enabled'         => 'yes',
					'login_id'        => 'login',
					'transaction_key' => 'key',
					'client_key'      => '',
				),
				$properties
			)
		);

		ob_start();
		$gateway->admin_notices();
		return ob_get_clean();
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_enabled_gateway_without_a_client_key_warns( $class, $section, $what ) {
		$notices = $this->notices( $class, array() );

		$this->assertStringContainsString( 'Public Client Key', $notices );
		$this->assertStringContainsString( $what, $notices );
		$this->assertStringContainsString( 'section=authnet"', $notices, 'Links to the card settings, where the key is set.' );
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_no_warning_with_a_client_key( $class ) {
		$this->assertStringNotContainsString( 'Public Client Key', $this->notices( $class, array( 'client_key' => 'public' ) ) );
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_no_warning_while_disabled( $class ) {
		$this->assertSame( '', $this->notices( $class, array( 'enabled' => 'no' ) ) );
	}

	public function gateways() {
		return array(
			'card' => array( WC_Gateway_Authnet::class, 'authnet', 'card numbers' ),
		);
	}
}
