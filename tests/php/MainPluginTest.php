<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use WC_Authnet;
use WC_Authnet_Test_Order;
use WC_Gateway_Authnet;
use WCAuthnet\Tests\Fakes\AuthnetApi;

/**
 * The main plugin class (WC_Authnet, in gateway.php): capturing authorized charges when an order is
 * processed or completed, and voiding them when it's cancelled or refunded.
 *
 * The capture and void tests use the Authorize.net API handlers; the Legacy AIM ones are only checked
 * to be hooked, since the AIM gateway can't be loaded alongside the card gateway.
 */
class MainPluginTest extends TestCase {

	/**
	 * @var WC_Gateway_Authnet The card gateway WooCommerce has loaded.
	 */
	private $card_gateway;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wc_price' )->returnArg();
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'get_woocommerce_currency' )->justReturn( 'USD' );

		$this->card_gateway = $this->gateway( WC_Gateway_Authnet::class, array( 'id' => 'authnet', 'capture' => false, 'capture_on_status_change' => true ) );
		$gateways           = array( 'authnet' => $this->card_gateway );
		$payment_gateways = new class( $gateways ) {
			private $gateways;

			public function __construct( $gateways ) {
				$this->gateways = $gateways;
			}

			public function payment_gateways() {
				return $this->gateways;
			}
		};
		Functions\when( 'WC' )->justReturn(
			new class( $payment_gateways ) {
				private $payment_gateways;

				public function __construct( $payment_gateways ) {
					$this->payment_gateways = $payment_gateways;
				}

				public function payment_gateways() {
					return $this->payment_gateways;
				}
			}
		);
	}

	private function order( array $values = array() ) {
		$order = new WC_Authnet_Test_Order(
			100,
			array_replace_recursive(
				array(
					'payment_method' => 'authnet',
					'total'          => '25.00',
					'meta'           => array( '_authnet_charge_id' => 'T1', '_authnet_charge_captured' => 'no' ),
					'props'          => array( 'get_total_refunded' => 0, 'get_currency' => 'USD' ),
				),
				$values
			)
		);
		Functions\when( 'wc_get_order' )->justReturn( $order );
		return $order;
	}

	private function respond( $trans_id = 'T2' ) {
		$this->api->respond( 'createTransactionRequest', AuthnetApi::ok( array( 'transactionResponse' => array( 'transId' => $trans_id ) ) ) );
	}

	public function test_processing_an_authorized_order_captures_the_rest_after_refunds() {
		$order = $this->order( array( 'props' => array( 'get_total_refunded' => 5 ) ) );
		$this->respond();

		WC_Authnet::get_instance()->capture_payment( 100, $order );

		$request = $this->api->sent( 'createTransactionRequest' )['transactionRequest'];
		$this->assertSame( 'priorAuthCaptureTransaction', $request['transactionType'] );
		$this->assertEquals( 20, $request['amount'] );
		$this->assertSame( 'T1', $request['refTransId'] );
		$this->assertSame( 'yes', $order->get_meta( '_authnet_charge_captured' ) );
		$this->assertSame( 'T2', $order->get_meta( 'Authorize.net Payment ID' ) );
	}

	public function test_nothing_is_captured_when_capture_on_status_change_is_off() {
		$order                                        = $this->order();
		$this->card_gateway->capture_on_status_change = false;

		WC_Authnet::get_instance()->capture_payment( 100, $order );

		$this->assertSame( array(), $this->api->methods() );
	}

	public function test_captured_orders_and_other_payment_methods_are_left_alone() {
		WC_Authnet::get_instance()->capture_payment( 100, $this->order( array( 'meta' => array( '_authnet_charge_captured' => 'yes' ) ) ) );
		WC_Authnet::get_instance()->capture_payment( 100, $this->order( array( 'payment_method' => 'other' ) ) );

		$this->assertSame( array(), $this->api->methods() );
	}

	public function test_cancelling_an_authorized_order_voids_the_charge() {
		$order = $this->order();
		$this->respond( 'T1' );

		WC_Authnet::get_instance()->cancel_payment( 100 );

		$this->assertSame( 'voidTransaction', $this->api->sent( 'createTransactionRequest' )['transactionRequest']['transactionType'] );
		$this->assertSame( '', $order->get_meta( '_authnet_charge_id' ) );
		$this->assertSame( '', $order->get_meta( '_authnet_charge_captured' ) );
	}

	public function test_a_failed_void_is_noted_on_the_order() {
		$order = $this->order();
		$this->api->respond( 'createTransactionRequest', AuthnetApi::error( 'E00027', 'The transaction cannot be found.' ) );

		WC_Authnet::get_instance()->cancel_payment( 100 );

		$this->assertSame( 'failed', $order->get_meta( '_authnet_void' ) );
		$this->assertSame( array( 'Unable to refund charge! The transaction cannot be found.' ), $order->notes );
	}

	public function test_captured_orders_are_not_voided() {
		$this->order( array( 'meta' => array( '_authnet_charge_captured' => 'yes' ) ) );

		WC_Authnet::get_instance()->cancel_payment( 100 );

		$this->assertSame( array(), $this->api->methods() );
	}

	public function apis() {
		return array(
			'Authorize.net API' => array( false, array( 'free_api_method' => 'api' ), 'capture_payment', 'cancel_payment' ),
			'Legacy AIM'        => array( false, array( 'free_api_method' => 'aim' ), 'capture_payment_aim', 'cancel_payment_aim' ),
		);
	}

	/**
	 * @dataProvider apis
	 */
	public function test_order_status_changes_capture_and_void( $enterprise, $settings, $capture, $cancel ) {
		\WC_Authnet_Test_Freemius::$enterprise = $enterprise;
		Functions\when( 'get_option' )->justReturn( $settings );
		$plugin = WC_Authnet::get_instance();

		// Captures get the order and the status transition (WooCommerce 8.4+ passes three arguments).
		Actions\expectAdded( 'woocommerce_order_status_processing' )->once()->with( array( $plugin, $capture ), 10, 3 );
		Actions\expectAdded( 'woocommerce_order_status_completed' )->once()->with( array( $plugin, $capture ), 10, 3 );
		Actions\expectAdded( 'woocommerce_order_status_cancelled' )->once()->with( array( $plugin, $cancel ) );
		Actions\expectAdded( 'woocommerce_order_status_refunded' )->once()->with( array( $plugin, $cancel ) );

		$plugin->add_order_status_hooks();

		$this->assertTrue( method_exists( $plugin, $capture ) );
	}
}
