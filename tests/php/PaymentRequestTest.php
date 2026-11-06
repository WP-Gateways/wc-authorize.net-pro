<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use WC_Authnet_Test_Order;
use WC_Gateway_Authnet;

/**
 * The charge request built for each kind of payment (generate_payment_request_args()).
 *
 * Authorize.net's JSON API needs the request fields in its schema's order, so each request is compared,
 * fields and order, with a snapshot in tests/php/snapshots/payment-requests/. To record the snapshots
 * again after an intended change, run the tests with UPDATE_SNAPSHOTS=1 and review the diff.
 */
class PaymentRequestTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );
		Functions\when( 'get_woocommerce_currency' )->justReturn( 'USD' );
		Functions\when( 'sanitize_text_field' )->alias( 'trim' );
		Functions\when( 'sanitize_email' )->alias( 'trim' );
		Functions\when( 'wc_get_order' )->alias(
			function () {
				return $this->order();
			}
		);
	}

	private function order() {
		return new WC_Authnet_Test_Order(
			60,
			array(
				'total' => '25.00',
				'props' => array(
					'get_currency'            => 'USD',
					'get_order_number'        => '60',
					'get_total_tax'           => '2.00',
					'get_shipping_total'      => '5.00',
					'get_billing_first_name'  => 'Jane',
					'get_billing_last_name'   => 'Doe',
					'get_billing_company'     => 'Doe & Co',
					'get_billing_address_1'   => '1 Main St',
					'get_billing_address_2'   => 'Suite 2',
					'get_billing_city'        => 'Springfield',
					'get_billing_state'       => 'IL',
					'get_billing_postcode'    => '62701',
					'get_billing_country'     => 'US',
					'get_billing_phone'       => '555-0100',
					'get_billing_email'       => 'jane@example.com',
					'get_shipping_first_name' => 'John',
					'get_shipping_last_name'  => 'Doe',
					'get_shipping_company'    => '',
					'get_shipping_address_1'  => '2 Oak Ave',
					'get_shipping_address_2'  => '',
					'get_shipping_city'       => 'Chicago',
					'get_shipping_state'      => 'IL',
					'get_shipping_postcode'   => '60601',
					'get_shipping_country'    => 'US',
				),
			)
		);
	}

	private function request( $class, $source, $recurring_description = '' ) {
		$gateway = $this->gateway(
			$class,
			array(
				'capture'              => true,
				'line_items'           => false,
				'customer_receipt'     => false,
				'statement_descriptor' => 'Shop',
			)
		);

		$method = new \ReflectionMethod( $gateway, 'generate_payment_request_args' );
		$method->setAccessible( true );
		return $method->invoke( $gateway, $this->order(), $source, $recurring_description );
	}

	private function assertMatchesSnapshot( $name, array $request ) {
		$file = __DIR__ . '/snapshots/payment-requests/' . $name . '.json';
		$json = json_encode( $request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";

		if ( getenv( 'UPDATE_SNAPSHOTS' ) ) {
			if ( ! is_dir( dirname( $file ) ) ) {
				mkdir( dirname( $file ), 0777, true );
			}
			file_put_contents( $file, $json );
		}

		$this->assertFileExists( $file );
		// Git may check the snapshots out with CRLF line endings.
		$this->assertSame( str_replace( "\r\n", "\n", file_get_contents( $file ) ), $json );
	}

	public function card_sources() {
		$card  = array( 'source_type' => 'card', 'card_number' => '4111111111111111', 'expiry' => '12 / 30', 'cvc' => '123' );
		$nonce = array( 'source_type' => 'nonce', 'nonce' => 'nonce-value', 'descriptor' => 'COMMON.ACCEPT.INAPP.PAYMENT' );
		$apple = array( 'source_type' => 'nonce', 'nonce' => 'apple-token', 'descriptor' => 'COMMON.APPLE.INAPP.PAYMENT' );

		return array(
			'card-new-card'  => array( false, (object) array( 'customer' => false, 'source' => $card ) ),
			'card-nonce'     => array( false, (object) array( 'customer' => false, 'source' => $nonce ) ),
			'card-apple-pay' => array( false, (object) array( 'customer' => false, 'source' => $apple ) ),
		);
	}

	/**
	 * @dataProvider card_sources
	 */
	public function test_card_requests( $enterprise, $source ) {
		\WC_Authnet_Test_Freemius::$enterprise = $enterprise;

		$this->assertMatchesSnapshot( $this->dataName(), $this->request( WC_Gateway_Authnet::class, $source ) );
	}

	public function test_card_partial_refund_request() {
		Functions\when( 'wc_price' )->returnArg();
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		$this->api->respond( 'createTransactionRequest', \WCAuthnet\Tests\Fakes\AuthnetApi::ok( array( 'transactionResponse' => array( 'transId' => '7000' ) ) ) );

		$this->gateway( WC_Gateway_Authnet::class )->process_refund( 60, '5.00', 'Damaged' );

		$this->assertMatchesSnapshot( 'card-partial-refund', $this->api->sent( 'createTransactionRequest' ) );
	}
}
