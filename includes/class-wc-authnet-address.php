<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Authnet_Address class.
 *
 * Builds the Bill To and Ship To addresses sent to Authorize.net, cut to its field lengths.
 */
class WC_Authnet_Address {

	/**
	 * Bill To address from an order's billing details.
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	public static function bill_to_from_order( $order ) {
		return self::format( function( $field ) use ( $order ) {
			return $order->{'get_billing_' . $field}();
		} );
	}

	/**
	 * Ship To address from an order's shipping details. Authorize.net's Ship To address has no phone number.
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	public static function ship_to_from_order( $order ) {
		$address = self::format( function( $field ) use ( $order ) {
			return 'phone' === $field ? '' : $order->{'get_shipping_' . $field}();
		} );
		unset( $address['phoneNumber'] );

		return $address;
	}

	/**
	 * Bill To address from a user's saved billing details.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function bill_to_from_user( $user_id ) {
		return self::format( function( $field ) use ( $user_id ) {
			return get_user_meta( $user_id, 'billing_' . $field, true );
		} );
	}

	/**
	 * Bill To address from a customer's billing details, e.g. WC()->customer.
	 *
	 * @param WC_Customer $customer
	 * @return array
	 */
	public static function bill_to_from_customer( $customer ) {
		return self::format( function( $field ) use ( $customer ) {
			return $customer->{'get_billing_' . $field}();
		} );
	}

	/**
	 * An address in Authorize.net's format.
	 *
	 * @param callable $field Returns the value of a WooCommerce address field, e.g. $field( 'first_name' ).
	 * @return array
	 */
	private static function format( $field ) {
		return array(
			'firstName'   => substr( $field( 'first_name' ), 0, 50 ),
			'lastName'    => substr( $field( 'last_name' ), 0, 50 ),
			'company'     => substr( $field( 'company' ), 0, 50 ),
			'address'     => substr( trim( $field( 'address_1' ) . ' ' . $field( 'address_2' ) ), 0, 60 ),
			'city'        => substr( $field( 'city' ), 0, 40 ),
			'state'       => substr( $field( 'state' ), 0, 40 ),
			'zip'         => substr( $field( 'postcode' ), 0, 20 ),
			'country'     => substr( $field( 'country' ), 0, 60 ),
			'phoneNumber' => substr( $field( 'phone' ), 0, 25 ),
		);
	}
}
