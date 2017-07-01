<?php
/**
 * Sends requests to the Authorize.Net gateways.
 *
 * @package    AuthNet
 * @subpackage AuthNet_Request
 */
abstract class AuthNet_Request {

    protected $_login_id;
    protected $_transaction_key;
	protected $_logging;
    protected $_post_string;
    public $VERIFY_PEER = false; // Set to false if getting connection errors.
    protected $_sandbox = true;
    protected $_log_file = false;

    /**
     * Set the _post_string
     */
    abstract protected function _setPostString();

    /**
     * Handle the response string
     */
    abstract protected function _handleResponse( $string );

    /**
     * Get the post url. We need this because until 5.3 you
     * you could not access child constants in a parent class.
     */
    abstract protected function _getPostUrl();

    /**
     * Constructor.
     *
     * @param string $api_login_id       The Merchant's API Login ID.
     * @param string $transaction_key    The Merchant's Transaction Key.
     */
    public function __construct( $_login_id = false, $_transaction_key = false, $_logging = false ) {
        $this->_login_id = ( $_login_id ? $_login_id : ( defined( 'LOGIN_ID' ) ? LOGIN_ID : "" ) );
        $this->_transaction_key = ( $_transaction_key ? $_transaction_key : ( defined( 'TRANSACTION_KEY' ) ? TRANSACTION_KEY : "" ) );
		$this->_logging = $_logging;
        $this->_sandbox = ( defined( 'AUTHNET_SANDBOX' ) ? AUTHNET_SANDBOX : true );
        $this->_log_file = ( defined( 'AUTHNET_LOG_FILE' ) ? AUTHNET_LOG_FILE : false );
    }

    /**
     * Alter the gateway url.
     *
     * @param bool $bool Use the Sandbox.
     */
    public function setSandbox( $bool ) {
        $this->_sandbox = $bool;
    }

    /**
     * Set a log file.
     *
     * @param string $filepath Path to log file.
     */
    public function setLogFile( $filepath ) {
        $this->_log_file = $filepath;
    }

    /**
     * Return the post string.
     *
     * @return string
     */
    public function getPostString() {
        return $this->_post_string;
    }

    /**
     * Posts the request to AuthNet & returns response.
     *
     * @return AuthNet_Response The response.
     */
    protected function _sendRequest() {
        $this->_setPostString();
        $post_url = $this->_getPostUrl();

        $curl_request = curl_init( $post_url );

        curl_setopt( $curl_request, CURLOPT_POSTFIELDS, $this->_post_string );
        curl_setopt( $curl_request, CURLOPT_HEADER, 0 );
        curl_setopt( $curl_request, CURLOPT_TIMEOUT, 45 );
        curl_setopt( $curl_request, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl_request, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $curl_request, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2 ); // version 1.2 required by authorize.net by April 30, 2017

		if( $this->VERIFY_PEER ) {
            curl_setopt( $curl_request, CURLOPT_CAINFO, dirname( dirname( __FILE__ ) ) . '/ssl/cert.pem' );
        } else {
            curl_setopt( $curl_request, CURLOPT_SSL_VERIFYPEER, false );
        }

        if( preg_match( '/xml/', $post_url ) ) {
            curl_setopt( $curl_request, CURLOPT_HTTPHEADER, Array( "Content-Type: text/xml" ) );
        }

        $response = curl_exec( $curl_request );
		
		// Saving to Log here
		if( $this->_logging ) {
			$message = sprintf( "\nPosting to: \n%s\nRequest: \n%s\nResponse: \n%s", $post_url, $this->_post_string, $response );
			WC_AuthNet_Logger::log( $message );
		}

        if( $this->_log_file ) {
            if( $curl_error = curl_error( $curl_request ) ) {
                file_put_contents( $this->_log_file, "----CURL ERROR----\n$curl_error\n\n", FILE_APPEND );
            }
            // Do not log requests that could contain CC info.
            // file_put_contents($this->_log_file, "----Request----\n{$this->_post_string}\n", FILE_APPEND);
            file_put_contents( $this->_log_file, "----Response----\n$response\n\n", FILE_APPEND );
        }
        curl_close( $curl_request );

        return $this->_handleResponse( $response );
    }

}