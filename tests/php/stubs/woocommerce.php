<?php
/**
 * Stand-ins for the WooCommerce classes the gateways extend or call.
 *
 * The order fakes are at the end of this file.
 *
 * They mirror the parts of the real API the gateways use (woocommerce/includes/abstracts and
 * includes/payment-tokens), including the behaviours tests depend on:
 *
 *   - get_meta() returns '' for missing meta, like WC_Data::get_meta( $key, true ).
 *   - WC_Payment_Tokens::get_customer_tokens() filters by user and gateway.
 */

// ---------------------------------------------------------------------------
// WordPress.
// ---------------------------------------------------------------------------

class WP_Error {

	private $code;

	private $message;

	private $data;

	public function __construct( $code = '', $message = '', $data = '' ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_message() {
		return $this->message;
	}

	public function get_error_data() {
		return $this->data;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

// ---------------------------------------------------------------------------
// Payment gateways.
// ---------------------------------------------------------------------------

abstract class WC_Payment_Gateway {

	public $id = '';

	public $supports = array();

	public $settings = array();

	public function get_option( $key, $empty_value = null ) {
		return $this->settings[ $key ] ?? $empty_value;
	}

	public function supports( $feature ) {
		return in_array( $feature, $this->supports, true );
	}

	public function get_return_url( $order = null ) {
		return 'https://example.com/checkout/order-received/';
	}
}

class WC_Payment_Gateway_CC extends WC_Payment_Gateway {}

class WC_Payment_Gateway_ECheck extends WC_Payment_Gateway {}

// ---------------------------------------------------------------------------
// Payment tokens.
// ---------------------------------------------------------------------------

abstract class WC_Payment_Token {

	protected $id = 0;

	protected $token = '';

	protected $gateway_id = '';

	protected $user_id = 0;

	protected $meta = array();

	public function get_id() {
		return $this->id;
	}

	public function set_id( $id ) {
		$this->id = (int) $id;
	}

	public function get_token() {
		return $this->token;
	}

	public function set_token( $token ) {
		$this->token = (string) $token;
	}

	public function get_gateway_id() {
		return $this->gateway_id;
	}

	public function set_gateway_id( $gateway_id ) {
		$this->gateway_id = $gateway_id;
	}

	public function get_user_id() {
		return $this->user_id;
	}

	public function set_user_id( $user_id ) {
		$this->user_id = (int) $user_id;
	}

	public function get_meta( $key, $single = true ) {
		return $this->meta[ $key ] ?? '';
	}

	public function update_meta_data( $key, $value ) {
		$this->meta[ $key ] = $value;
	}

	public function set_default( $is_default ) {
		$this->meta['_default'] = (bool) $is_default;
	}

	/**
	 * Saving adds the token to WC_Payment_Tokens, as saving a new token does in WooCommerce.
	 */
	public function save() {
		WC_Payment_Tokens::add( $this );
		return $this->id;
	}

	public function delete() {
		WC_Payment_Tokens::remove( $this->id );
	}

	abstract public function get_display_name();
}

class WC_Payment_Token_CC extends WC_Payment_Token {

	protected $last4 = '';

	protected $card_type = '';

	public function get_last4() {
		return $this->last4;
	}

	public function set_last4( $last4 ) {
		$this->last4 = (string) $last4;
	}

	public function get_card_type() {
		return $this->card_type;
	}

	public function set_card_type( $type ) {
		$this->card_type = $type;
	}

	protected $expiry_month = '';

	protected $expiry_year = '';

	public function get_expiry_month() {
		return $this->expiry_month;
	}

	public function set_expiry_month( $month ) {
		$this->expiry_month = (string) $month;
	}

	public function get_expiry_year() {
		return $this->expiry_year;
	}

	public function set_expiry_year( $year ) {
		$this->expiry_year = (string) $year;
	}

	public function get_display_name() {
		return sprintf( '%s ending in %s', ucwords( $this->card_type ), $this->last4 );
	}
}

class WC_Payment_Token_ECheck extends WC_Payment_Token {

	protected $last4 = '';

	public function get_last4() {
		return $this->last4;
	}

	public function set_last4( $last4 ) {
		$this->last4 = (string) $last4;
	}

	public function get_display_name() {
		return sprintf( 'eCheck ending in %s', $this->last4 );
	}
}

// ---------------------------------------------------------------------------
// WooCommerce Subscriptions and Pre-Orders.
// ---------------------------------------------------------------------------

/**
 * WooCommerce Subscriptions' token helpers. Tests set which subscriptions use a token and whether the
 * customer has another saved method to move them to.
 */
class WCS_Payment_Tokens {

	public static $subscriptions = array();

	public static $has_alternative = false;

	public static function reset() {
		self::$subscriptions   = array();
		self::$has_alternative = false;
	}

	public static function get_subscriptions_from_token( $payment_token ) {
		return self::$subscriptions[ $payment_token->get_id() ] ?? array();
	}

	public static function customer_has_alternative_token( $payment_token ) {
		return self::$has_alternative;
	}
}

class WCS_Staging {

	public static $is_duplicate_site = false;

	public static function is_duplicate_site() {
		return self::$is_duplicate_site;
	}
}

class WC_Pre_Orders_Order {

	public static $requires_tokenization = true;

	public static $pre_ordered = array();

	public static function order_requires_payment_tokenization( $order_id ) {
		return self::$requires_tokenization;
	}

	public static function mark_order_as_pre_ordered( $order ) {
		self::$pre_ordered[] = $order;
	}
}

/**
 * The saved tokens, held in memory. Tests add tokens with WC_Payment_Tokens::add() and clear them with reset().
 */
class WC_Payment_Tokens {

	private static $tokens = array();

	public static function add( WC_Payment_Token $token ) {
		if ( ! $token->get_id() ) {
			$token->set_id( count( self::$tokens ) + 1 );
		}
		self::$tokens[ $token->get_id() ] = $token;
		return $token;
	}

	public static function reset() {
		self::$tokens = array();
	}

	public static function remove( $token_id ) {
		unset( self::$tokens[ $token_id ] );
	}

	public static function get( $token_id ) {
		return self::$tokens[ $token_id ] ?? null;
	}

	public static function set_users_default( $user_id, $token_id ) {
		foreach ( self::$tokens as $token ) {
			if ( $token->get_user_id() === (int) $user_id ) {
				$token->set_default( $token->get_id() === (int) $token_id );
			}
		}
	}

	public static function get_customer_tokens( $customer_id, $gateway_id = '' ) {
		return array_filter(
			self::$tokens,
			function ( $token ) use ( $customer_id, $gateway_id ) {
				return (int) $customer_id === $token->get_user_id() && ( '' === $gateway_id || $gateway_id === $token->get_gateway_id() );
			}
		);
	}
}

// ---------------------------------------------------------------------------
// Orders and subscriptions.
// ---------------------------------------------------------------------------

/**
 * An order with in-memory meta, notes and status. Tests set the values they need through the constructor.
 */
class WC_Authnet_Test_Order {

	public $id;

	public $meta = array();

	public $notes = array();

	public $status = 'pending';

	public $customer_id = 0;

	public $payment_method = 'authnet';

	public $total = '10.00';

	public $needs_payment = true;

	public $created_via = 'checkout';

	public $transaction_id = 'T1';

	public $saved = 0;

	public function __construct( $id = 1, array $values = array() ) {
		$this->id = $id;
		foreach ( $values as $name => $value ) {
			$this->$name = $value;
		}
	}

	public function get_id() {
		return $this->id;
	}

	public function get_meta( $key, $single = true ) {
		return $this->meta[ $key ] ?? '';
	}

	public function update_meta_data( $key, $value ) {
		$this->meta[ $key ] = $value;
	}

	public function delete_meta_data( $key ) {
		unset( $this->meta[ $key ] );
	}

	public function save() {
		$this->saved++;
		return $this->id;
	}

	public function get_customer_id() {
		return $this->customer_id;
	}

	public function get_payment_method() {
		return $this->payment_method;
	}

	public function get_total() {
		return $this->total;
	}

	public function get_created_via() {
		return $this->created_via;
	}

	public function get_transaction_id() {
		return $this->transaction_id;
	}

	public function needs_payment() {
		return $this->needs_payment;
	}

	public function has_status( $status ) {
		return in_array( $this->status, (array) $status, true );
	}

	public function update_status( $status, $note = '' ) {
		$this->status = $status;
		if ( $note ) {
			$this->notes[] = $note;
		}
	}

	public function add_order_note( $note ) {
		$this->notes[] = $note;
	}

	public function get_items() {
		return array();
	}

	/**
	 * Other getters (billing and shipping fields, currency, ...) return '', and setters and actions such as
	 * payment_complete() do nothing, so requests can be built from the order.
	 */
	public function __call( $name, $arguments ) {
		return 0 === strpos( $name, 'get_' ) ? '' : null;
	}
}

/**
 * A subscription: an order with an optional parent order. Like WC_Subscription, get_parent_id() is 0 and
 * get_parent() is false when there is no parent.
 */
class WC_Authnet_Test_Subscription extends WC_Authnet_Test_Order {

	public $parent = false;

	public function get_parent_id() {
		return $this->parent ? $this->parent->get_id() : 0;
	}

	public function get_parent() {
		return $this->parent;
	}
}

class WC_Geolocation {

	public static function get_ip_address() {
		return '127.0.0.1';
	}
}
