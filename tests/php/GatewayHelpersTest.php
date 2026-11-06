<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use WC_Authnet_Test_Order;
use WC_Gateway_Authnet;

/**
 * Helpers the card and Legacy AIM gateways share: line item names, the payment currency, the
 * pay-for-order page, AVS / CVV messages and the log URL.
 */
class GatewayHelpersTest extends TestCase {

	public function gateways() {
		return array(
			'card' => array( WC_Gateway_Authnet::class ),
		);
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_line_item_names_are_made_plain_ascii( $class ) {
		$name = "Tom\u{2019}s \u{201C}Best\u{201D} \u{2013} 50% & more\u{2026} caf\u{00E9}";

		$this->assertSame( "Tom's \"Best\" -- 50pc - more... caf", $this->gateway( $class )->format_line_item( $name ) );
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_the_payment_currency_is_the_orders( $class ) {
		Functions\when( 'get_woocommerce_currency' )->justReturn( 'USD' );
		Functions\when( 'wc_get_order' )->justReturn( new WC_Authnet_Test_Order( 90, array( 'props' => array( 'get_currency' => 'CAD' ) ) ) );

		$this->assertSame( 'CAD', $this->gateway( $class )->get_payment_currency( 90 ) );
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_without_an_order_the_payment_currency_is_the_stores( $class ) {
		Functions\when( 'get_woocommerce_currency' )->justReturn( 'USD' );
		$GLOBALS['wp'] = (object) array( 'query_vars' => array() );

		$this->assertSame( 'USD', $this->gateway( $class )->get_payment_currency() );
		$this->assertSame( 0, $this->gateway( $class )->get_checkout_pay_page_order_id() );
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_on_the_pay_page_the_payment_currency_is_that_orders( $class ) {
		Functions\when( 'get_woocommerce_currency' )->justReturn( 'USD' );
		Functions\when( 'wc_get_order' )->justReturn( new WC_Authnet_Test_Order( 91, array( 'props' => array( 'get_currency' => 'EUR' ) ) ) );
		$GLOBALS['wp'] = (object) array( 'query_vars' => array( 'order-pay' => '91' ) );

		$this->assertSame( 91, $this->gateway( $class )->get_checkout_pay_page_order_id() );
		$this->assertSame( 'EUR', $this->gateway( $class )->get_payment_currency() );
	}

	/**
	 * @dataProvider gateways
	 */
	public function test_the_pay_for_order_page_needs_the_orders_key_and_an_unpaid_order( $class ) {
		$order = new WC_Authnet_Test_Order( 92, array( 'props' => array( 'get_order_key' => 'wc_order_abc' ) ) );
		Functions\when( 'is_wc_endpoint_url' )->justReturn( true );
		Functions\when( 'get_query_var' )->justReturn( '92' );
		Functions\when( 'wc_get_order' )->justReturn( $order );
		Functions\when( 'current_user_can' )->justReturn( true );
		$gateway = $this->gateway( $class );

		$_GET['key'] = 'wc_order_abc';
		$this->assertTrue( $gateway->is_valid_pay_for_order_endpoint() );

		$_GET['key'] = 'wrong';
		$this->assertFalse( $gateway->is_valid_pay_for_order_endpoint() );

		$_GET['key']          = 'wc_order_abc';
		$order->needs_payment = false;
		$this->assertFalse( $gateway->is_valid_pay_for_order_endpoint() );
	}

	public function test_avs_and_cvv_codes_are_explained() {
		$gateway = $this->gateway( WC_Gateway_Authnet::class );

		$this->assertSame( 'Y - Street Address: Match - First 5 Digits of ZIP: Match', $gateway->get_avs_message( 'Y' ) );
		$this->assertSame( 'Q', $gateway->get_avs_message( 'Q' ) );
		$this->assertSame( 'M - CVV2/CVC2 Match', $gateway->get_cvv_message( 'M' ) );
		$this->assertSame( 'Q', $gateway->get_cvv_message( 'Q' ) );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp'] );
		parent::tearDown();
	}
}
