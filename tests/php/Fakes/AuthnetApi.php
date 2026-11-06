<?php
namespace WCAuthnet\Tests\Fakes;

use Brain\Monkey\Functions;

/**
 * Stands in for Authorize.net behind wp_remote_post(): records each request WC_Authnet_API::execute()
 * sends, and answers with the response queued for that request type (e.g. 'createTransactionRequest').
 * A request type with no queued response gets a plain "Ok" response.
 */
class AuthnetApi {

	/**
	 * Requests sent, in order: each is [ request type, request body without merchantAuthentication ].
	 *
	 * @var array[]
	 */
	public $requests = array();

	/**
	 * @var array[] Queued responses per request type.
	 */
	private $responses = array();

	public function __construct() {
		Functions\when( 'wp_remote_post' )->alias(
			function ( $url, $args ) {
				$body   = json_decode( $args['body'], true );
				$method = key( $body );
				$sent   = $body[ $method ];
				unset( $sent['merchantAuthentication'] );

				$this->requests[] = array( $method, $sent );

				$response = ! empty( $this->responses[ $method ] )
					? array_shift( $this->responses[ $method ] )
					: self::ok();

				return array( 'body' => is_string( $response ) ? $response : json_encode( $response ) );
			}
		);
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			function ( $response ) {
				return $response['body'];
			}
		);
	}

	/**
	 * Queue a response for the next request of a type.
	 *
	 * @param string $method   Request type, e.g. 'createCustomerPaymentProfileRequest'.
	 * @param array  $response Response body.
	 */
	public function respond( $method, array $response ) {
		$this->responses[ $method ][] = $response;
	}

	/**
	 * Queue a raw response body, exactly as Authorize.net would send it, for the next request of a type.
	 *
	 * @param string $method Request type.
	 * @param string $body   Response body.
	 */
	public function respond_raw( $method, $body ) {
		$this->responses[ $method ][] = $body;
	}

	/**
	 * Request types sent, in order.
	 *
	 * @return string[]
	 */
	public function methods() {
		return array_column( $this->requests, 0 );
	}

	/**
	 * The body of the first request of a type.
	 *
	 * @param string $method Request type.
	 * @return array|null
	 */
	public function sent( $method ) {
		foreach ( $this->requests as $request ) {
			if ( $request[0] === $method ) {
				return $request[1];
			}
		}
		return null;
	}

	/**
	 * A successful response with extra fields.
	 *
	 * @param array $fields Fields added to the response.
	 * @return array
	 */
	public static function ok( array $fields = array() ) {
		return array_merge(
			$fields,
			array(
				'messages' => array(
					'resultCode' => 'Ok',
					'message'    => array( array( 'code' => 'I00001', 'text' => 'Successful.' ) ),
				),
			)
		);
	}

	/**
	 * An error response with extra fields.
	 *
	 * @param string $code   Error code, e.g. 'E00039'.
	 * @param string $text   Error text.
	 * @param array  $fields Fields added to the response.
	 * @return array
	 */
	public static function error( $code, $text, array $fields = array() ) {
		return array_merge(
			$fields,
			array(
				'messages' => array(
					'resultCode' => 'Error',
					'message'    => array( array( 'code' => $code, 'text' => $text ) ),
				),
			)
		);
	}
}
