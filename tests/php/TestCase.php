<?php
namespace WCAuthnet\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;
use WC_Authnet_API;
use WC_Payment_Tokens;
use WCAuthnet\Tests\Fakes\AuthnetApi;

/**
 * Base test case: Brain Monkey, a clean request, a fake Authorize.net and helpers to build gateways.
 */
abstract class TestCase extends PHPUnitTestCase {

	/**
	 * @var AuthnetApi
	 */
	protected $api;

	/**
	 * Cached customer profiles, keyed by transient name, as WC_Authnet_Customer::get_payment_methods() reads them.
	 *
	 * @var array
	 */
	protected $transients = array();

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();

		$_POST = array();
		$_GET  = array();
		WC_Payment_Tokens::reset();
		\WC_Authnet_Test_Freemius::$enterprise = false;

		WC_Authnet_API::set_login_id( 'login' );
		WC_Authnet_API::set_transaction_key( 'key' );
		WC_Authnet_API::set_testmode( true );
		WC_Authnet_API::set_logging( false );
		WC_Authnet_API::set_debugging( false );

		// A fresh main plugin instance for each test.
		$instance = new \ReflectionProperty( \WC_Authnet::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		$this->api        = new AuthnetApi();
		$this->transients = array();

		Functions\when( 'get_transient' )->alias(
			function ( $name ) {
				return $this->transients[ $name ] ?? false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			function ( $name, $value ) {
				$this->transients[ $name ] = $value;
				return true;
			}
		);
		// Clearing the cache is a no-op, so tests can give a customer's saved methods through cards().
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'get_user_meta' )->justReturn( '' );
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'wc_get_credit_card_type_label' )->returnArg();
		Functions\when( 'WC' )->justReturn(
			(object) array(
				'session'  => new class() {
					public function get( $key ) {
						return null;
					}
				},
				'customer' => null,
			)
		);
	}

	/**
	 * Turn on the plugin's logging and collect the messages it logs.
	 *
	 * @return \ArrayObject Logged messages, filled in as they are logged.
	 */
	protected function capture_log() {
		$messages = new \ArrayObject();
		WC_Authnet_API::set_logging( true );
		\WC_Authnet_Logger::$logger = new class( $messages ) {
			private $messages;

			public function __construct( $messages ) {
				$this->messages = $messages;
			}

			public function debug( $message, $context = array() ) {
				$this->messages[] = $message;
			}
		};
		return $messages;
	}

	protected function tearDown(): void {
		\WC_Authnet_Logger::$logger = null;
		$_POST = array();
		$_GET  = array();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * A gateway without running its constructor (which needs WooCommerce), with the given properties.
	 *
	 * @param string $class      Gateway class.
	 * @param array  $properties Property values.
	 * @return object
	 */
	protected function gateway( string $class, array $properties = array() ) {
		$gateway = ( new ReflectionClass( $class ) )->newInstanceWithoutConstructor();

		foreach ( $properties as $name => $value ) {
			$gateway->$name = $value;
		}

		return $gateway;
	}

	/**
	 * Give an Authorize.net customer profile its saved cards and eCheck accounts.
	 *
	 * @param string $customer_id Customer profile ID.
	 * @param array  $cards       Cards keyed by payment profile ID.
	 * @param array  $checks      eCheck accounts keyed by payment profile ID.
	 */
	protected function saved_methods( $customer_id, array $cards = array(), array $checks = array() ) {
		$this->transients[ 'authnet_payment_methods_' . $customer_id ] = array(
			'cards'  => $cards,
			'checks' => $checks,
		);
	}

	/**
	 * A saved card as WC_Authnet_Customer::get_payment_methods() returns it.
	 *
	 * @param string $last4      Last four digits.
	 * @param bool   $is_default Whether it is the profile's default.
	 * @return array
	 */
	protected function card( $last4 = '1111', $is_default = false ) {
		return array(
			'card_type'    => 'visa',
			'last4'        => $last4,
			'expiry_month' => '12',
			'expiry_year'  => '2030',
			'is_default'   => $is_default,
		);
	}

	/**
	 * A saved eCheck account as WC_Authnet_Customer::get_payment_methods() returns it.
	 *
	 * @param string $last4      Last four digits.
	 * @param bool   $is_default Whether it is the profile's default.
	 * @return array
	 */
	protected function account( $last4 = '6789', $is_default = false ) {
		return array(
			'last4'          => $last4,
			'is_default'     => $is_default,
			'account_name'   => 'Jane Doe',
			'account_type'   => 'checking',
			'account_number' => 'XXXX' . $last4,
			'routing_number' => 'XXXX0021',
		);
	}
}
