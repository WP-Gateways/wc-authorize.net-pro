<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use WC_Authnet;
use WC_Authnet_Test_Order;
use WC_Gateway_Authnet;

/**
 * Full refunds first try to void the charge through the plugin's main class. They must use its existing
 * instance: constructing WC_Authnet again registers all the plugin's hooks a second time.
 */
class RefundTest extends TestCase {

	/**
	 * @var WC_Authnet_Test_Order
	 */
	private $order;

	protected function setUp(): void {
		parent::setUp();
		WC_Authnet::reset();

		// The void succeeded: cancel_payment() leaves no '_authnet_void' = 'failed' meta, so no refund is sent.
		$this->order = new WC_Authnet_Test_Order( 30, array( 'total' => '10.00' ) );
		Functions\when( 'wc_get_order' )->justReturn( $this->order );
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_full_refunds_use_the_existing_plugin_instance( $class, $id ) {
		WC_Authnet::get_instance();
		$gateway = $this->gateway( $class, array( 'id' => $id ) );

		$this->assertTrue( $gateway->process_refund( 30, '10.00' ) );
		$this->assertTrue( $gateway->process_refund( 30, '10.00' ) );

		$this->assertSame( 1, WC_Authnet::$constructed );
		$this->assertSame( array( 30, 30 ), WC_Authnet::$cancelled );
		$this->assertSame( array(), $this->api->methods() );
	}

	public function gateways() {
		return array(
			'card' => array( WC_Gateway_Authnet::class, 'authnet' ),
		);
	}
}
