<?php
/**
 * The WooCommerce Apple Pay and Google Pay extension's processor contract, as Authorize.net uses it.
 *
 * Mirrors wc-apple-pay-google-pay/includes/interface-wcapgp-processor-integration.php and
 * class-wcapgp-wallet-payment.php. The method signatures must match the real interface, or
 * WC_Authnet_Wallet_Integration would be a fatal error with the real extension.
 */

interface WCAPGP_Processor_Integration {

	public function get_gateway();

	public function is_ready( $wallet_id );

	public function supports_subscriptions();

	public function get_google_pay_params();

	public function validate_apple_merchant( $validation_url );

	public function process_payment( WCAPGP_Wallet_Payment $payment, $order_id, $retry = true );
}

class WCAPGP_Wallet_Payment {

	private $wallet_id;
	private $token;

	public function __construct( $wallet_id, $token ) {
		$this->wallet_id = (string) $wallet_id;
		$this->token     = (string) $token;
	}

	public function get_wallet_id() {
		return $this->wallet_id;
	}

	public function is_apple_pay() {
		return 'wcapgp_apple_pay' === $this->wallet_id;
	}

	public function get_token() {
		return $this->token;
	}

	public function get_decoded_token() {
		return (string) base64_decode( $this->token );
	}
}
