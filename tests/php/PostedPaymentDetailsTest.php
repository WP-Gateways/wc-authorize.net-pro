<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use WC_Gateway_Authnet;

/**
 * Reading the payment details posted at checkout (get_source()): an Accept.js nonce, card fields, or an
 * Apple Pay / Google Pay token.
 */
class PostedPaymentDetailsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'get_current_user_id' )->justReturn( 5 );
	}

	private function card_gateway() {
		return $this->gateway(
			WC_Gateway_Authnet::class,
			array(
				'id'                   => 'authnet',
				'allowed_card_types'   => array( 'visa', 'mastercard' ),
			)
		);
	}

	private function source( $user_id = 0 ) {
		$method = new \ReflectionMethod( WC_Gateway_Authnet::class, 'get_source' );
		$method->setAccessible( true );
		return $method->invoke( $this->card_gateway(), $user_id );
	}

	private function post_card( $number = '4111 1111 1111 1111', $cvc = '123' ) {
		$_POST['authnet-card-number'] = $number;
		$_POST['authnet-card-expiry'] = '12 / 30';
		$_POST['authnet-card-cvc']    = $cvc;
	}

	public function plans() {
		return array(
			'free' => array( false ),
		);
	}

	/**
	 * @dataProvider plans
	 */
	public function test_an_accept_js_nonce_is_used_first( $enterprise ) {
		\WC_Authnet_Test_Freemius::$enterprise = $enterprise;
		$_POST['authnet_nonce']           = 'nonce';
		$_POST['authnet_data_descriptor'] = 'COMMON.ACCEPT.INAPP.PAYMENT';
		$this->post_card();
		$_POST['wcapgp_google_pay_payment_data'] = 'google-token';

		$this->assertEquals(
			array( 'nonce' => 'nonce', 'descriptor' => 'COMMON.ACCEPT.INAPP.PAYMENT', 'source_type' => 'nonce' ),
			$this->source()->source
		);
	}

	/**
	 * @dataProvider plans
	 */
	public function test_card_fields_are_used_without_a_nonce( $enterprise ) {
		\WC_Authnet_Test_Freemius::$enterprise = $enterprise;
		$this->post_card();

		$this->assertEquals(
			array( 'card_number' => '4111111111111111', 'expiry' => '12 / 30', 'cvc' => '123', 'source_type' => 'card' ),
			$this->source()->source
		);
	}

	/**
	 * @dataProvider plans
	 */
	public function test_a_card_type_that_is_not_allowed_is_refused( $enterprise ) {
		\WC_Authnet_Test_Freemius::$enterprise = $enterprise;
		$this->post_card( '378282246310005' );

		$this->expectExceptionMessage( 'Card Type Not Accepted.' );
		$this->source();
	}

	/**
	 * @dataProvider plans
	 */
	public function test_a_card_without_cvc_is_refused( $enterprise ) {
		\WC_Authnet_Test_Freemius::$enterprise = $enterprise;
		$this->post_card( '4111111111111111', '' );

		$this->expectExceptionMessage( 'CVC code is required.' );
		$this->source();
	}

	public function wallets() {
		return array(
			'Google Pay' => array( false, 'wcapgp_google_pay_payment_data', 'COMMON.GOOGLE.INAPP.PAYMENT' ),
			'Apple Pay'  => array( false, 'wcapgp_apple_pay_payment_data', 'COMMON.APPLE.INAPP.PAYMENT' ),
		);
	}

	/**
	 * @dataProvider wallets
	 */
	public function test_wallet_tokens_are_sent_as_opaque_data( $enterprise, $field, $descriptor ) {
		\WC_Authnet_Test_Freemius::$enterprise = $enterprise;
		$_POST[ $field ] = 'wallet-token';

		$this->assertEquals(
			array( 'nonce' => 'wallet-token', 'descriptor' => $descriptor, 'source_type' => 'nonce' ),
			$this->source()->source
		);
	}

	/**
	 * Wallet extension versions without the processor integration post the whole checkout form, card form
	 * included, with the wallet as the payment method.
	 *
	 * @dataProvider plans
	 */
	public function test_the_wallet_token_wins_when_the_wallet_is_the_payment_method( $enterprise ) {
		\WC_Authnet_Test_Freemius::$enterprise = $enterprise;
		$_POST['payment_method']                 = 'wcapgp_google_pay';
		$_POST['authnet_nonce']                  = 'nonce';
		$_POST['authnet_data_descriptor']        = 'COMMON.ACCEPT.INAPP.PAYMENT';
		$_POST['wcapgp_google_pay_payment_data'] = 'google-token';
		$this->post_card();

		$this->assertEquals(
			array( 'nonce' => 'google-token', 'descriptor' => 'COMMON.GOOGLE.INAPP.PAYMENT', 'source_type' => 'nonce' ),
			$this->source()->source
		);
	}

	/**
	 * @dataProvider plans
	 */
	public function test_nothing_posted_gives_no_source( $enterprise ) {
		\WC_Authnet_Test_Freemius::$enterprise = $enterprise;

		$source = $this->source();

		$this->assertFalse( $source->source );
		$this->assertFalse( $source->token_id );
	}

}
