<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use WC_Authnet_Address;
use WC_Authnet_Test_Order;

/**
 * Bill To and Ship To addresses for Authorize.net requests, cut to its field lengths.
 */
class AddressTest extends TestCase {

	const BILLING = array(
		'first_name' => 'Jane',
		'last_name'  => 'Doe',
		'company'    => 'A company name that is much longer than Authorize.net allows',
		'address_1'  => '1 Main St',
		'address_2'  => 'Suite 2',
		'city'       => 'Springfield',
		'state'      => 'IL',
		'postcode'   => '62701',
		'country'    => 'US',
		'phone'      => '555-0100',
	);

	const BILL_TO = array(
		'firstName'   => 'Jane',
		'lastName'    => 'Doe',
		'company'     => 'A company name that is much longer than Authorize.',
		'address'     => '1 Main St Suite 2',
		'city'        => 'Springfield',
		'state'       => 'IL',
		'zip'         => '62701',
		'country'     => 'US',
		'phoneNumber' => '555-0100',
	);

	private function getters( $prefix ) {
		$getters = array();
		foreach ( self::BILLING as $field => $value ) {
			$getters[ 'get_' . $prefix . '_' . $field ] = $value;
		}
		return $getters;
	}

	public function test_bill_to_from_an_order() {
		$order = new WC_Authnet_Test_Order( 1, array( 'props' => $this->getters( 'billing' ) ) );

		$this->assertSame( self::BILL_TO, WC_Authnet_Address::bill_to_from_order( $order ) );
	}

	public function test_ship_to_from_an_order_has_no_phone_number() {
		$order   = new WC_Authnet_Test_Order( 1, array( 'props' => $this->getters( 'shipping' ) ) );
		$ship_to = self::BILL_TO;
		unset( $ship_to['phoneNumber'] );

		$this->assertSame( $ship_to, WC_Authnet_Address::ship_to_from_order( $order ) );
	}

	public function test_bill_to_from_a_users_billing_details() {
		Functions\when( 'get_user_meta' )->alias(
			function ( $user_id, $key, $single ) {
				return 5 === $user_id && $single ? self::BILLING[ substr( $key, strlen( 'billing_' ) ) ] : '';
			}
		);

		$this->assertSame( self::BILL_TO, WC_Authnet_Address::bill_to_from_user( 5 ) );
	}

	public function test_bill_to_from_the_session_customer() {
		$customer = new WC_Authnet_Test_Order( 0, array( 'props' => $this->getters( 'billing' ) ) );

		$this->assertSame( self::BILL_TO, WC_Authnet_Address::bill_to_from_customer( $customer ) );
	}

	public function test_long_values_are_cut_to_authorize_net_field_lengths() {
		$long  = str_repeat( 'x', 100 );
		$order = new WC_Authnet_Test_Order( 1, array( 'props' => array( 'get_billing_address_1' => $long, 'get_billing_city' => $long, 'get_billing_postcode' => $long, 'get_billing_phone' => $long ) ) );

		$bill_to = WC_Authnet_Address::bill_to_from_order( $order );

		$this->assertSame( 60, strlen( $bill_to['address'] ) );
		$this->assertSame( 40, strlen( $bill_to['city'] ) );
		$this->assertSame( 20, strlen( $bill_to['zip'] ) );
		$this->assertSame( 25, strlen( $bill_to['phoneNumber'] ) );
	}
}
