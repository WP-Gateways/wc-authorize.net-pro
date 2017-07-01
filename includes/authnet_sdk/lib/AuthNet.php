<?php

/**
 * Builds and sends a AuthNet AIM Request.
 *
 * @package    AuthNet
 * @subpackage AuthNet
 */
class AuthNet extends AuthNet_Request {
    private $live_url = 'https://secure2.authorize.net/gateway/transact.dll';
    private $test_url = 'https://test.authorize.net/gateway/transact.dll';

	public function __construct( $login_id = false, $transaction_key = false, $logging = false, $live_url = false, $test_url = false ) {
		if( $live_url ) {
			$this->live_url = $live_url;
			$this->test_url = $test_url ? $test_url : $live_url;
		}
		parent::__construct( $login_id, $transaction_key, $logging );
	}
	
	protected $_post_fields = array(
		"version" => "3.1",
		"delim_char" => "|",
		"delim_data" => "TRUE",
		"relay_response" => "FALSE",
		"encap_char" => "",
		"method" => "CC",
	);

    /**
     * Checks to make sure a field is actually in the API before setting.
     * Set to false to skip this check.
     */
    public $verify_fields = true;

    /**
     * A list of all fields in the AIM API.
     * Used to warn user if they try to set a field not offered in the API.
     */
	private $_all_authnet_fields = array(
		"address","allow_partial_auth","amount","auth_code","authentication_indicator", "bank_aba_code","bank_acct_name","bank_acct_num","bank_acct_type","bank_check_number",
		"bank_name","card_code","card_num","cardholder_authentication_value","city","company","country","cust_id","customer_ip","delim_char","delim_data","description",
		"duplicate_window","duty","echeck_type","email","email_customer","encap_char","exp_date","fax","first_name","footer_email_receipt","freight","header_email_receipt",
		"invoice_num","last_name","line_item","login","method","phone","po_num","recurring_billing","relay_response","ship_to_address","ship_to_city","ship_to_company",
		"ship_to_country","ship_to_first_name","ship_to_last_name","ship_to_state","ship_to_zip","split_tender_id","state","tax","tax_exempt","test_request","tran_key",
        "trans_id","type","version","zip","currency_code",
    );

	/**
	 * Product Sale transaction (Capture On)
	 * Transaction do completed/processing
	 */
    public function sale() {
        $this->type = "AUTH_CAPTURE";
		return $this->_sendRequest();
    }

	/**
	 * Product Sale transaction (Capture Off)
	 * Transaction to put on-hold
	 */
	public function auth() {
        $this->type = "AUTH_ONLY";
		return $this->_sendRequest();
    }

	/**
	 * Process Product on-hold to complete/processing transaction (Capture Off)
	 */
	public function capture() {
        $this->type = "PRIOR_AUTH_CAPTURE";
        return $this->_sendRequest();
    }

	/**
	 * Process Product on-hold to cancel/refund transaction (Capture Off)
	 */
	public function cancel() {
        $this->type = "VOID";
		return $this->_sendRequest();
    }

    /**
     * Alternative syntax for setting x_ fields.
     *
     * Usage: $sale->method = "echeck";
     *
     * @param string $name
     * @param string $value
     */
    public function __set( $name, $value ) {
        $this->setField( $name, $value );
    }

    /**
     * Quickly set multiple fields.
     *
     * Note: The prefix x_ will be added to all fields. If you want to set a
     * custom field without the x_ prefix, use setCustomField or setCustomFields.
     *
     * @param array $fields Takes an array or object.
     */
    public function setFields( $fields ) {
        $array = (array)$fields;
        foreach( $array as $key => $value ) {
            $this->setField( $key, $value );
        }
    }

    /**
     * Set an individual name/value pair. This will append x_ to the name
     * before posting.
     *
     * @param string $name
     * @param string $value
     */
    public function setField( $name, $value ) {

        if( $this->verify_fields ) {
            if( in_array( $name, $this->_all_authnet_fields ) ) {
                $this->_post_fields[$name] = $value;
            } else {
                throw new AuthNet_Exception( "Error: no field $name exists in the Authorize.Net API.
                To set a custom field use setCustomField('field','value') instead." );
            }
        } else {
            $this->_post_fields[$name] = $value;
        }
    }

    /**
     * Unset an x_ field.
     *
     * @param string $name Field to unset.
     */
    public function unsetField( $name ) {
        unset( $this->_post_fields[$name] );
    }

    /**
     *
     *
     * @param string $response
     *
     * @return AuthNet_Response
     */
    protected function _handleResponse( $response ) {
        return new AuthNet_Gateway_Response( $response );
    }

    /**
     * @return string
     */
    protected function _getPostUrl() {
        return ( $this->_sandbox ? $this->test_url : $this->live_url );
    }

    /**
     * Converts the x_post_fields array into a string suitable for posting.
     */
    protected function _setPostString() {
        $this->_post_fields['login'] = $this->_login_id;
        $this->_post_fields['tran_key'] = $this->_transaction_key;
        $this->_post_string = "";
        foreach( $this->_post_fields as $key => $value ) {
            $this->_post_string .= "x_$key=" . urlencode( $value ) . "&";
        }
        $this->_post_string = rtrim( $this->_post_string, "& " );
    }
}

/**
 * Parses a AuthNet AIM Response.
 *
 * @package    AuthNet
 * @subpackage AuthNet
 */
class AuthNet_Gateway_Response extends AuthNet_Response {
    private $_response_array = array(); // An array with the split response.

    /**
     * Constructor. Parses the AuthNet response string.
     *
     * @param string $response      The response from the AuthNet server.
     * @param string $delimiter     The delimiter used (default is ",")
     * @param string $encap_char    The encap_char used (default is "|")
     * @param array  $custom_fields Any custom fields set in the request.
     */
    public function __construct( $response ) {

		if( $response ) {

			$this->_response_array = explode( '|', $response );

			/**
             * If AuthorizeNet doesn't return a delimited response.
             */
			if( count( $this->_response_array ) < 10 ) {
                $this->approved = false;
                $this->error = true;
                $this->error_message = sprintf( __( 'Unrecognized response from the gateway: %s', 'wc-authnet' ), $response );
                return;
            }

			// Set all fields
            $this->response_code        = $this->_response_array[0];
            $this->response_subcode     = $this->_response_array[1];
            $this->response_reason_code = $this->_response_array[2];
            $this->response_reason_text = $this->_response_array[3];
            $this->authorization_code   = $this->_response_array[4];
            $this->avs_response         = $this->_response_array[5];
            $this->transaction_id       = $this->_response_array[6];
            $this->invoice_number       = $this->_response_array[7];
            $this->description          = $this->_response_array[8];
            $this->amount               = $this->_response_array[9];
            $this->method               = $this->_response_array[10];
            $this->transaction_type     = $this->_response_array[11];
            $this->customer_id          = $this->_response_array[12];
            $this->first_name           = $this->_response_array[13];
            $this->last_name            = $this->_response_array[14];
            $this->company              = $this->_response_array[15];
            $this->address              = $this->_response_array[16];
            $this->city                 = $this->_response_array[17];
            $this->state                = $this->_response_array[18];
            $this->zip_code             = $this->_response_array[19];
            $this->country              = $this->_response_array[20];
            $this->phone                = $this->_response_array[21];
            $this->fax                  = $this->_response_array[22];
            $this->email_address        = $this->_response_array[23];
            $this->ship_to_first_name   = $this->_response_array[24];
            $this->ship_to_last_name    = $this->_response_array[25];
            $this->ship_to_company      = $this->_response_array[26];
            $this->ship_to_address      = $this->_response_array[27];
            $this->ship_to_city         = $this->_response_array[28];
            $this->ship_to_state        = $this->_response_array[29];
            $this->ship_to_zip_code     = $this->_response_array[30];
            $this->ship_to_country      = $this->_response_array[31];
            $this->tax                  = $this->_response_array[32];
            $this->duty                 = $this->_response_array[33];
            $this->freight              = $this->_response_array[34];
            $this->tax_exempt           = $this->_response_array[35];
            $this->purchase_order_number= $this->_response_array[36];
            $this->md5_hash             = $this->_response_array[37];
            $this->card_code_response   = $this->_response_array[38];
            $this->cavv_response        = $this->_response_array[39];
            $this->account_number       = $this->_response_array[50];
            $this->card_type            = $this->_response_array[51];
            $this->split_tender_id      = $this->_response_array[52];
            $this->requested_amount     = $this->_response_array[53];
            $this->balance_on_card      = $this->_response_array[54];

			$this->approved = ( $this->response_code == self::APPROVED );
            $this->declined = ( $this->response_code == self::DECLINED );
            $this->error    = ( $this->response_code == self::ERROR );
            $this->held     = ( $this->response_code == self::HELD );

            if( $this->declined ) {
                $this->error_message = __( 'Your card has been declined.', 'wc-authnet' );
            }

			if( $this->error ) {
                $this->error_message = sprintf( __( 'Gateway Error: %s', 'wc-authnet' ), $this->response_reason_text );
            }

		} else {
            $this->approved = false;
            $this->error = true;
            $this->error_message = __( 'Error connecting to the gateway', 'wc-authnet' );
        }

    }
}