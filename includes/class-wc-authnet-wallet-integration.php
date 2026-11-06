<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Authorize.net as the processor for the WooCommerce Apple Pay and Google Pay extension.
 *
 * Loaded from WC_Authnet::wallet_integration(), only while the extension (and so this interface) exists.
 * Subscriptions are supported only when the gateway declares 'wcapgp_subscriptions', which the free
 * plugin's gateway doesn't: it takes one-time wallet payments.
 *
 * @see wc-apple-pay-google-pay/docs/INTEGRATION.md
 */
class WC_Authnet_Wallet_Integration implements WCAPGP_Processor_Integration {

	/**
	 * @var WC_Gateway_Authnet
	 */
	private $gateway;

	/**
	 * @param WC_Gateway_Authnet $gateway Authorize.net card gateway.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	public function get_gateway() {
		return $this->gateway;
	}

	/**
	 * Ready with API credentials, even when Authorize.net's own card form is switched off.
	 */
	public function is_ready( $wallet_id ) {
		return (bool) $this->gateway->is_wallet_ready( $wallet_id );
	}

	/**
	 * Authorize.net saves the wallet's card to the customer profile for subscriptions and pre-orders.
	 */
	public function supports_subscriptions() {
		return $this->gateway->supports( 'wcapgp_subscriptions' );
	}

	public function get_google_pay_params() {
		return $this->gateway->get_params_google_pay();
	}

	/**
	 * Authorize.net has no merchant validation of its own; the extension uses its Apple merchant certificate.
	 */
	public function validate_apple_merchant( $validation_url ) {
		return null;
	}

	public function process_payment( WCAPGP_Wallet_Payment $payment, $order_id, $retry = true ) {
		return $this->gateway->process_wallet_payment( $payment, $order_id, $retry );
	}
}
