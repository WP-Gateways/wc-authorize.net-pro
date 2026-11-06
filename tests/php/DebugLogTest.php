<?php
namespace WCAuthnet\Tests;

use WC_Authnet_API;
use WCAuthnet\Tests\Fakes\AuthnetApi;

/**
 * Gateway Debug logs each request and response. API keys, card numbers, CVV codes, wallet and Accept.js
 * tokens and bank account details are masked first.
 */
class DebugLogTest extends TestCase {

	private function log_request( $method, array $args ) {
		$log = $this->capture_log();
		WC_Authnet_API::set_debugging( true );
		WC_Authnet_API::set_transaction_key( 'SECRETKEY987' );

		WC_Authnet_API::execute( $method, $args );

		return implode( "\n", $log->getArrayCopy() );
	}

	public function test_card_requests_are_logged_without_the_key_card_number_or_cvv() {
		$log = $this->log_request(
			'createTransactionRequest',
			array(
				'transactionRequest' => array(
					'payment' => array(
						'creditCard' => array(
							'cardNumber'     => '4111111111111111',
							'expirationDate' => '2030-12',
							'cardCode'       => '987',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( 'createTransactionRequest', $log );
		$this->assertStringNotContainsString( 'SECRETKEY987', $log );
		$this->assertStringNotContainsString( '4111111111111111', $log );
		$this->assertStringContainsString( 'XXXX1111', $log );
		$this->assertDoesNotMatchRegularExpression( '/\[cardCode\] => 987/', $log );
	}

	public function test_opaque_data_is_not_logged() {
		$log = $this->log_request(
			'createTransactionRequest',
			array(
				'transactionRequest' => array(
					'payment' => array(
						'opaqueData' => array(
							'dataDescriptor' => 'COMMON.GOOGLE.INAPP.PAYMENT',
							'dataValue'      => 'eyJ3YWxsZXQtdG9rZW4iOiJzZWNyZXQifQ==',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( 'COMMON.GOOGLE.INAPP.PAYMENT', $log );
		$this->assertStringNotContainsString( 'eyJ3YWxsZXQtdG9rZW4iOiJzZWNyZXQifQ==', $log );
	}

	public function test_bank_account_and_routing_numbers_are_masked_in_requests_and_responses() {
		$this->api->respond(
			'getCustomerPaymentProfileRequest',
			AuthnetApi::ok( array( 'paymentProfile' => array( 'payment' => array( 'bankAccount' => array( 'accountNumber' => '9876543210', 'routingNumber' => '021000021' ) ) ) ) )
		);

		$log = $this->log_request(
			'createCustomerPaymentProfileRequest',
			array( 'paymentProfile' => array( 'payment' => array( 'bankAccount' => array( 'accountNumber' => '1234567890', 'routingNumber' => '011000015' ) ) ) )
		);
		$log .= $this->log_request( 'getCustomerPaymentProfileRequest', array( 'customerPaymentProfileId' => 'P1' ) );

		foreach ( array( '1234567890', '011000015', '9876543210', '021000021' ) as $number ) {
			$this->assertStringNotContainsString( $number, $log );
		}
		$this->assertStringContainsString( 'XXXX7890', $log );
		$this->assertStringContainsString( 'XXXX0021', $log );
	}

	public function test_masking_does_not_change_the_request_sent() {
		$this->log_request(
			'createTransactionRequest',
			array( 'transactionRequest' => array( 'payment' => array( 'creditCard' => array( 'cardNumber' => '4111111111111111', 'cardCode' => '987' ) ) ) )
		);

		$sent = $this->api->sent( 'createTransactionRequest' );
		$this->assertSame( '4111111111111111', $sent['transactionRequest']['payment']['creditCard']['cardNumber'] );
		$this->assertSame( '987', $sent['transactionRequest']['payment']['creditCard']['cardCode'] );
	}

	public function test_legacy_aim_fields_are_masked() {
		$redacted = WC_Authnet_API::redact(
			array(
				'x_login'     => 'login',
				'x_tran_key'  => 'SECRETKEY987',
				'x_card_num'  => '4111111111111111',
				'x_card_code' => '987',
			)
		);

		$this->assertSame(
			array(
				'x_login'     => 'login',
				'x_tran_key'  => '[redacted]',
				'x_card_num'  => 'XXXX1111',
				'x_card_code' => '[redacted]',
			),
			$redacted
		);
	}
}
