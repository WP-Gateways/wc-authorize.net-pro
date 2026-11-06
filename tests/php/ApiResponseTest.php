<?php
namespace WCAuthnet\Tests;

use WC_Authnet_API;

/**
 * Reading Authorize.net's JSON responses. They start with a byte-order mark, which is removed before
 * decoding; the text itself (including non-ASCII characters) and value types are kept, and strings are
 * sanitised.
 */
class ApiResponseTest extends TestCase {

	const BOM = "\xEF\xBB\xBF";

	private function respond( array $response ) {
		$this->api->respond_raw( 'getCustomerProfileRequest', self::BOM . json_encode( $response, JSON_UNESCAPED_UNICODE ) );
		return WC_Authnet_API::execute( 'getCustomerProfileRequest', array( 'customerProfileId' => 'C1' ) );
	}

	private function ok( array $fields ) {
		return array_merge( $fields, array( 'messages' => array( 'resultCode' => 'Ok', 'message' => array( array( 'code' => 'I00001', 'text' => 'Successful.' ) ) ) ) );
	}

	public function test_non_ascii_text_is_kept() {
		$result = $this->respond( $this->ok( array( 'profile' => array( 'description' => 'Zoë Müller – Café' ) ) ) );

		$this->assertSame( 'Zoë Müller – Café', $result['profile']['description'] );
	}

	public function test_value_types_are_kept() {
		$result = $this->respond( $this->ok( array( 'profile' => array( 'defaultPaymentProfile' => true, 'count' => 2 ) ) ) );

		$this->assertTrue( $result['profile']['defaultPaymentProfile'] );
		$this->assertSame( 2, $result['profile']['count'] );
	}

	public function test_strings_are_sanitised() {
		$result = $this->respond( $this->ok( array( 'profile' => array( 'description' => '<script>alert(1)</script>Jane' ) ) ) );

		$this->assertStringNotContainsString( '<script>', $result['profile']['description'] );
	}

	public function test_errors_after_a_byte_order_mark_are_read() {
		$result = $this->respond( array( 'messages' => array( 'resultCode' => 'Error', 'message' => array( array( 'code' => 'E00040', 'text' => 'The record cannot be found.' ) ) ) ) );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'E00040', $result->get_error_code() );
	}

	public function test_a_body_that_is_not_json_cannot_connect() {
		$this->api->respond_raw( 'getCustomerProfileRequest', '<html>Service Unavailable</html>' );

		$result = WC_Authnet_API::execute( 'getCustomerProfileRequest', array() );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'cannot_connect', $result->get_error_code() );
	}
}
