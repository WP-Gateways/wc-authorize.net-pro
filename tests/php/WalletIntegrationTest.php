<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Filters;
use WC_Authnet;
use WC_Authnet_Wallet_Integration;
use WC_Gateway_Authnet;
use WCAPGP_Processor_Integration;
use WCAPGP_Wallet_Payment;
use WCAuthnet\Tests\Fakes\AuthnetApi;

/**
 * Authorize.net as the processor for the WooCommerce Apple Pay and Google Pay extension.
 *
 * The card gateway supplies its own WCAPGP_Processor_Integration through the extension's
 * wcapgp_processor_integration filter, instead of the extension's legacy one. The free plugin takes
 * one-time wallet payments only.
 */
class WalletIntegrationTest extends TestCase {

	private const GOOGLE_TOKEN = '{"signature":"TEST","protocolVersion":"ECv2"}';

	protected function tearDown(): void {
		// Count Brain Monkey's hook expectations as assertions.
		$this->addToAssertionCount( \Mockery::getContainer()->mockery_getExpectationCount() );
		parent::tearDown();
	}

	private function card_gateway( array $properties = array() ) {
		return $this->gateway(
			WC_Gateway_Authnet::class,
			array_merge(
				array(
					'id'                   => 'authnet',
					'enabled'              => 'yes',
					'login_id'             => 'login',
					'transaction_key'      => 'key',
					'testmode'             => true,
					'allowed_card_types'   => array( 'visa', 'mastercard' ),
					'supports'             => array( 'products', 'wcapgp_apple_pay', 'wcapgp_google_pay' ),
				),
				$properties
			)
		);
	}

	private function integration( $gateway ) {
		$integration = WC_Authnet::wallet_integration( null, $gateway, 'wcapgp_google_pay' );
		$this->assertInstanceOf( WC_Authnet_Wallet_Integration::class, $integration );

		return $integration;
	}

	private function google_pay() {
		return new WCAPGP_Wallet_Payment( 'wcapgp_google_pay', base64_encode( self::GOOGLE_TOKEN ) );
	}

	private function merchant_details( $gateway_id = '123456' ) {
		$this->api->respond( 'getMerchantDetailsRequest', AuthnetApi::ok( array( 'gatewayId' => $gateway_id, 'merchantName' => 'Test Store' ) ) );
	}

	public function test_authnet_supplies_its_own_integration() {
		$gateway     = $this->card_gateway();
		$integration = $this->integration( $gateway );

		$this->assertInstanceOf( WCAPGP_Processor_Integration::class, $integration );
		$this->assertSame( $gateway, $integration->get_gateway() );
	}

	public function test_the_plugin_registers_the_integration() {
		Filters\expectAdded( 'wcapgp_processor_integration' )->once()->with( array( WC_Authnet::class, 'wallet_integration' ), 10, 3 );

		WC_Authnet::get_instance();
	}

	public function test_it_is_ready_with_api_credentials_even_when_the_card_form_is_off() {
		$this->assertTrue( $this->integration( $this->card_gateway() )->is_ready( 'wcapgp_google_pay' ) );
		$this->assertTrue( $this->integration( $this->card_gateway( array( 'enabled' => 'no' ) ) )->is_ready( 'wcapgp_apple_pay' ) );
	}

	public function test_it_is_not_ready_without_api_credentials() {
		$this->assertFalse( $this->integration( $this->card_gateway( array( 'login_id' => '' ) ) )->is_ready( 'wcapgp_google_pay' ) );
		$this->assertFalse( $this->integration( $this->card_gateway( array( 'transaction_key' => '' ) ) )->is_ready( 'wcapgp_google_pay' ) );
	}

	public function test_the_free_plugin_doesnt_save_wallet_cards_for_subscriptions() {
		$this->assertFalse( $this->integration( $this->card_gateway() )->supports_subscriptions() );
	}

	public function test_google_pay_tokenizes_for_authorize_net_with_the_accounts_gateway_id() {
		$this->merchant_details( '123456' );

		$this->assertSame(
			array( 'gateway' => 'authorizenet', 'gateway_merchant_id' => '123456' ),
			$this->integration( $this->card_gateway() )->get_google_pay_params()
		);
	}

	public function test_the_gateway_id_is_fetched_once_and_cached() {
		$this->merchant_details( '123456' );
		$gateway = $this->card_gateway();

		$gateway->get_params_google_pay();
		$gateway->get_params_google_pay();

		$this->assertSame( array( 'getMerchantDetailsRequest' ), $this->api->methods() );
	}

	public function test_the_cached_gateway_id_belongs_to_the_api_login() {
		$this->merchant_details( '111' );
		$this->merchant_details( '222' );

		$this->assertSame( '111', $this->card_gateway()->get_params_google_pay()['gateway_merchant_id'] );
		$this->assertSame( '222', $this->card_gateway( array( 'login_id' => 'other' ) )->get_params_google_pay()['gateway_merchant_id'] );
	}

	public function test_without_a_gateway_id_the_wallets_setting_is_left_to_supply_it() {
		$this->api->respond( 'getMerchantDetailsRequest', AuthnetApi::error( 'E00007', 'User authentication failed due to invalid authentication values.' ) );
		$gateway = $this->card_gateway();

		$this->assertSame( array( 'gateway' => 'authorizenet' ), $gateway->get_params_google_pay() );

		// The failure is cached too, so checkout doesn't ask Authorize.net on every page.
		$gateway->get_params_google_pay();
		$this->assertSame( array( 'getMerchantDetailsRequest' ), $this->api->methods() );
	}

	public function test_no_gateway_id_is_fetched_without_api_credentials() {
		$this->assertSame( array( 'gateway' => 'authorizenet' ), $this->card_gateway( array( 'login_id' => '' ) )->get_params_google_pay() );
		$this->assertSame( array(), $this->api->methods() );
	}

	public function test_apple_merchant_validation_uses_the_extensions_certificate() {
		$this->assertNull( $this->integration( $this->card_gateway() )->validate_apple_merchant( 'https://apple-pay-gateway.apple.com/paymentservices/startSession' ) );
	}

	public function test_the_wallet_payment_reaches_authorize_net_without_post_data() {
		$gateway = new class() extends WC_Gateway_Authnet {
			public $seen = array();

			public function __construct() {}

			public function process_payment( $order_id, $retry = true, $force_customer = false ) {
				$this->seen[] = array( $order_id, $this->get_posted_source_args() );
				return array( 'result' => 'success' );
			}

			public function get_posted_source_args( $allow_wallets = true ) {
				return parent::get_posted_source_args( $allow_wallets );
			}
		};
		$gateway->id = 'authnet';

		$result = $this->integration( $gateway )->process_payment( $this->google_pay(), 15 );

		$this->assertSame( array( 'result' => 'success' ), $result );
		$this->assertSame(
			array( array( 15, array( 'nonce' => base64_encode( self::GOOGLE_TOKEN ), 'descriptor' => 'COMMON.GOOGLE.INAPP.PAYMENT', 'source_type' => 'nonce' ) ) ),
			$gateway->seen
		);
		// Only for that payment.
		$this->assertFalse( $gateway->get_posted_source_args() );
	}

	public function test_the_wallet_payment_is_forgotten_when_the_payment_throws() {
		$gateway = new class() extends WC_Gateway_Authnet {
			public function __construct() {}

			public function process_payment( $order_id, $retry = true, $force_customer = false ) {
				throw new \Exception( 'Declined' );
			}

			public function get_posted_source_args( $allow_wallets = true ) {
				return parent::get_posted_source_args( $allow_wallets );
			}
		};
		$gateway->id = 'authnet';

		try {
			$this->integration( $gateway )->process_payment( $this->google_pay(), 15 );
			$this->fail( 'The exception should reach the extension.' );
		} catch ( \Exception $e ) {
			$this->assertSame( 'Declined', $e->getMessage() );
		}

		$this->assertFalse( $gateway->get_posted_source_args() );
	}

	private function source( $gateway, $user_id, $force_customer = false ) {
		$method = new \ReflectionMethod( WC_Gateway_Authnet::class, 'get_source' );
		$method->setAccessible( true );
		return $method->invoke( $gateway, $user_id, $force_customer );
	}

	private function in_wallet_payment( $gateway, callable $callback ) {
		$property = new \ReflectionProperty( WC_Gateway_Authnet::class, 'wallet_payment' );
		$property->setAccessible( true );
		$property->setValue( $gateway, $this->google_pay() );

		try {
			return $callback();
		} finally {
			$property->setValue( $gateway, null );
		}
	}

	public function test_the_wallet_token_wins_over_card_fields_left_in_the_card_form() {
		$gateway                           = $this->card_gateway();
		$_POST['authnet-card-number']      = '4111 1111 1111 1111';
		$_POST['authnet-card-expiry']      = '12 / 30';
		$_POST['authnet-card-cvc']         = '123';
		$_POST['wc-authnet-payment-token'] = '7';

		$source = $this->in_wallet_payment( $gateway, function () use ( $gateway ) {
			return $this->source( $gateway, 0 );
		} );

		$this->assertSame( 'COMMON.GOOGLE.INAPP.PAYMENT', $source->source['descriptor'] );
	}

}

