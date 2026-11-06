<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use WC_Authnet;
use WC_Authnet_Test_Order;
use WC_Gateway_Authnet;
use WCAuthnet\Tests\Fakes\AuthnetApi;

/**
 * Full refunds first try to void the charge through the plugin's main class. They must use its existing
 * instance: constructing WC_Authnet again registers all the plugin's hooks a second time.
 */
class RefundTest extends TestCase {

	public function gateways() {
		return array(
			'card' => array( WC_Gateway_Authnet::class, 'authnet' ),
		);
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_a_full_refund_voids_the_charge_through_the_existing_plugin_instance( $class, $id ) {
		$order = new WC_Authnet_Test_Order(
			30,
			array(
				'payment_method' => $id,
				'total'          => '10.00',
				'meta'           => array( '_authnet_charge_id' => 'T1', '_authnet_charge_captured' => 'yes' ),
			)
		);
		Functions\when( 'wc_get_order' )->justReturn( $order );
		$this->api->respond( 'createTransactionRequest', AuthnetApi::ok( array( 'transactionResponse' => array( 'transId' => 'T1' ) ) ) );
		WC_Authnet::get_instance();

		// The plugin's constructor adds its hooks; a second instance would add them again.
		Actions\expectAdded( 'plugins_loaded' )->never();

		$this->assertTrue( $this->gateway( $class, array( 'id' => $id ) )->process_refund( 30, '10.00' ) );

		$this->assertSame( array( 'createTransactionRequest' ), $this->api->methods() );
		$this->assertSame( 'voidTransaction', $this->api->sent( 'createTransactionRequest' )['transactionRequest']['transactionType'] );
		$this->assertSame( 'yes', $order->get_meta( '_authnet_charge_captured' ) );
	}
}
