<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_AuthNet class.
 *
 * @extends WC_Payment_Gateway
 */

class WC_Gateway_AuthNet extends WC_Payment_Gateway_CC {

	// Supported currencies
	private $currencies = array(
		'AED', 'AMD', 'ANG', 'ARS', 'AUD', 'AWG', 'AZN', 'BBD', 'BDT', 'BGN', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BWP', 'BYR', 'BZD', 'CAD', 'CHF', 'CLP', 'CNY', 'COP',
		'CRC', 'CVE', 'CYP', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EEK', 'EGP', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GHC', 'GIP', 'GMD', 'GNF', 'GTQ', 'GWP', 'GYD',
		'HKD', 'HNL', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JMD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LTL', 'LVL', 'MAD',
		'MDL', 'MGF', 'MNT', 'MOP', 'MRO', 'MTL', 'MUR', 'MVR', 'MWK', 'MYR', 'MZN', 'MXN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR',
		'PLN', 'PYG', 'QAR', 'RON', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SKK', 'SLL', 'SOS', 'STD', 'SVC', 'SZL', 'THB', 'TOP', 'TRY', 'TTD', 'TWD',
		'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMK', 'ZWD',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                    = 'authnet';
		$this->method_title          = __( 'Authorize.Net', 'wc-authnet' );
		$this->method_description    = __( 'Authorize.Net works by adding credit card fields on the checkout and then sending the details to the gateway for processing the transactions.', 'wc-authnet' );
		$this->has_fields            = true;
		$this->supports              = array( 'products' );
		$this->live_url 			 = 'https://secure2.authorize.net/gateway/transact.dll';
		$this->test_url 			 = '';
		$this->label_login_id 		 = __( 'API Login ID', 'wc-authnet' );
		$this->label_transaction_key = __( 'Transaction Key', 'wc-authnet' );

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values.
		$this->title       		  = $this->get_option( 'title' );
		$this->description 		  = $this->get_option( 'description' );
		$this->enabled     		  = $this->get_option( 'enabled' );
		$this->testmode    		  = $this->get_option( 'testmode' ) === 'yes' ? true : false;
		$this->capture     		  = $this->get_option( 'capture', 'yes' ) === 'yes' ? true : false;
		$this->login_id	   		  = $this->get_option( 'login_id' );
		$this->transaction_key	  = $this->get_option( 'transaction_key' );
		$this->logging     		  = $this->get_option( 'logging' ) === 'yes' ? true : false;
		$this->debugging   		  = $this->get_option( 'debugging' ) === 'yes' ? true : false;
		$this->allowed_card_types = $this->get_option( 'allowed_card_types' );
		$this->customer_receipt   = $this->get_option( 'customer_receipt' ) === 'yes' ? true : false;

		if ( $this->testmode ) {
			$this->description .= ' ' . sprintf( __( '<br /><br /><strong>TEST MODE ENABLED</strong><br /> In test mode, you can use the card number 4111111111111111 with any CVC and a valid expiration date or check the documentation "<a href="%s">%s API</a>" for more card numbers.', 'wc-authnet' ), 'https://developer.authorize.net/hello_world/testing_guide/', $this->method_title );
			$this->description  = trim( $this->description );
		}

		// Hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	}

	/**
	 * get_icon function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_icon() {
		$icon = '';
		if( in_array( 'visa', $this->allowed_card_types ) ) {
			$icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/visa.png' ) . '" alt="Visa" />';
		}
		if( in_array( 'mastercard', $this->allowed_card_types ) ) {
			$icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/mastercard.png' ) . '" alt="Mastercard" />';
		}
		if( in_array( 'amex', $this->allowed_card_types ) ) {
			$icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/amex.png' ) . '" alt="Amex" />';
		}
		if( in_array( 'discover', $this->allowed_card_types ) ) {
			$icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/discover.png' ) . '" alt="Discover" />';
		}
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Check if SSL is enabled and notify the user
	 */
	public function admin_notices() {
		if ( $this->enabled == 'no' ) {
			return;
		}

		// Check required fields
		if ( ! $this->login_id ) {
			echo '<div class="error"><p>' . sprintf( __( 'Gateway error: Please enter your API Login ID <a href="%s">here</a>', 'wc-authnet' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=authnet' ) ) . '</p></div>';
			return;

		} elseif ( ! $this->transaction_key ) {
			echo '<div class="error"><p>' . sprintf( __( 'Gateway error: Please enter your Transaction Key <a href="%s">here</a>', 'wc-authnet' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=authnet' ) ) . '</p></div>';
			return;
		}

		// Simple check for duplicate keys
		if ( $this->login_id == $this->transaction_key ) {
			echo '<div class="error"><p>' . sprintf( __( 'Gateway error: Your API Login ID and Transaction Key match. Please check and re-enter.', 'wc-authnet' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=authnet' ) ) . '</p></div>';
			return;
		}

		// Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
		if ( ! wc_checkout_is_https() ) {
			echo '<div class="notice notice-warning"><p>' . sprintf( __( 'Authorize.Net is enabled, but a SSL certificate is not detected. Your checkout may not be secure! Please ensure your server has a valid <a href="%1$s" target="_blank">SSL certificate</a>', 'wc-authnet' ), 'https://en.wikipedia.org/wiki/Transport_Layer_Security' ) . '</p></div>';
 		}

		if ( ! $this->currency_is_accepted() ) {
			echo '<div class="error"><p>' . sprintf( __( 'Authorize.Net supports these currencies: %s', 'wc-authnet' ), implode( ', ', $this->currencies ) ) . '</p></div>';
			return;
		}
	}

	/**
	 * Check if this gateway is enabled
	 */
	public function is_available() {
		if ( $this->enabled == "yes" ) {
			if ( is_add_payment_method_page() && ! $this->saved_cards ) {
				return false;
			}
			// Required fields check
			if ( ! $this->login_id || ! $this->transaction_key ) {
				return false;
			}
			if ( ! $this->currency_is_accepted() ) {
				return false;
			}
			return true;
		}
		return parent::is_available();
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = apply_filters( 'wc_authnet_settings', array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'wc-authnet' ),
				'label'       => __( 'Enable Authorize.Net', 'wc-authnet' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'wc-authnet' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-authnet' ),
				'default'     => __( 'Credit card', 'wc-authnet' )
			),
			'description' => array(
				'title'       => __( 'Description', 'wc-authnet' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc-authnet' ),
				'default'     => sprintf( __( 'Pay with your credit card via %s.', 'wc-authnet' ), $this->method_title )
			),
			'testmode' => array(
				'title'       => __( 'Test mode', 'wc-authnet' ),
				'label'       => __( 'Enable Test Mode', 'wc-authnet' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in test mode. This will display test information on the checkout page and enable processing in non HTTPS mode.', 'wc-authnet' ),
				'default'     => 'yes'
			),
			'login_id' => array(
				'title'       => $this->label_login_id,
				'type'        => 'text',
				'description' => sprintf( __( 'Get your %s from your %s account.', 'wc-authnet' ), $this->label_login_id, $this->method_title ),
				'default'     => ''
			),
			'transaction_key' => array(
				'title'       => $this->label_transaction_key,
				'type'        => 'text',
				'description' => sprintf( __( 'Get your %s from your %s account.', 'wc-authnet' ), $this->label_transaction_key, $this->method_title ),
				'default'     => ''
			),
			'capture' => array(
				'title'       => __( 'Capture', 'wc-authnet' ),
				'label'       => __( 'Capture charge immediately', 'wc-authnet' ),
				'type'        => 'checkbox',
				'description' => __( 'Whether or not to immediately capture the charge. When unchecked, the charge issues an authorization and will need to be captured later.', 'wc-authnet' ),
				'default'     => 'yes'
			),
			'logging' => array(
				'title'       => __( 'Logging', 'wc-authnet' ),
				'label'       => __( 'Log debug messages', 'wc-authnet' ),
				'type'        => 'checkbox',
				'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'wc-authnet' ),
				'default'     => 'no'
			),
			'debugging' => array(
				'title'       => __( 'Gateway Debug', 'wc-authnet' ),
				'label'       => __( 'Log gateway requests and response to the WooCommerce System Status log.', 'wc-authnet' ),
				'type'        => 'checkbox',
				'description' => __( '<strong>CAUTION! Enabling this option will write gateway requests including card numbers and CVV to the logs.</strong> Do not turn this on unless you have a problem processing credit cards. You must only ever enable it temporarily for troubleshooting or to send requested information to the plugin author. It must be disabled straight away after the issues are resolved and the plugin logs should be deleted from <code>/wp-content/uploads/wc-logs/</code> folder.', 'wc-authnet' ),
				'default'     => 'no'
			),
			'allowed_card_types' => array(
				'title'       => __( 'Allowed Card types', 'wc-authnet' ),
				'class'       => 'wc-enhanced-select',
				'type'        => 'multiselect',
				'description' => __( 'Select the card types you want to allow payments from.', 'wc-authnet' ),
				'default'     => array( 'visa', 'mastercard', 'discover', 'amex' ),
				'options'	  => array(
					'visa' => __( 'Visa', 'wc-authnet' ),
					'mastercard' => __( 'MasterCard', 'wc-authnet' ),
					'discover' => __( 'Discover', 'wc-authnet' ),
					'amex' => __( 'American Express', 'wc-authnet' ),
				),
			),
			'customer_receipt' => array(
				'title'       => __( 'Receipt', 'wc-authnet' ),
				'label'       => __( 'Send Gateway Receipt', 'wc-authnet' ),
				'type'        => 'checkbox',
				'description' => __( 'If enabled, the customer will be sent an email receipt from Authorize.Net.', 'wc-authnet' ),
				'default'     => 'no'
			),
		) );
	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		$total = WC()->cart->total;

		// If paying from order, we need to get total from order not cart.
		if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) {
			$order = wc_get_order( wc_get_order_id_by_order_key( wc_clean( $_GET['key'] ) ) );
			$total = $order->get_total();
		}

        echo '<div class="authnet_new_card"
			id="authnet-payment-data"
			data-description=""
			data-amount="' . esc_attr( $total ) . '"
			data-name="' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '"
			data-currency="' . esc_attr( strtolower( get_woocommerce_currency() ) ) . '">';

		if ( $this->description ) {
			echo apply_filters( 'wc_authnet_description', wpautop( wp_kses_post( $this->description ) ) );
		}
        $this->form();

		echo '</div>';
	}

	/**
	 * payment_scripts function.
	 *
	 * Outputs scripts used for Authorize.Net payment
	 *
	 * @access public
	 */
	public function payment_scripts() {
		if ( ! is_checkout() ) {
			return;
		}

		wp_enqueue_script( 'woocommerce_authnet', plugins_url( 'assets/js/scripts.js', dirname( __FILE__ ) ), array( 'jquery' ), WC_AUTHNET_VERSION, true );

		$authnet_params = array(
			'i18n_terms'             => __( 'Please accept the terms and conditions first', 'wc-authnet' ),
			'i18n_required_fields'   => __( 'Please fill in required checkout fields first', 'wc-authnet' ),
			'error_response_handler' => 'authnetErrorHandler',
		);

		wp_localize_script( 'woocommerce_authnet', 'wc_authnet_params', apply_filters( 'wc_authnet_params', $authnet_params ) );
	}

	/**
	 * Process the payment
	 */
	public function process_payment( $order_id, $retry = true ) {

		$order = wc_get_order( $order_id );

		$this->log( "Info: Beginning processing payment for order $order_id for the amount of {$order->get_total()}" );

		// Use Authorize.Net CURL API for payment
		try {
			$payment_args = array();

			// Check for CC details filled or not
			if( empty( $_POST['authnet-card-number'] ) || empty( $_POST['authnet-card-expiry'] ) || empty( $_POST['authnet-card-cvc'] ) ) {
				throw new Exception( __( 'Credit card details cannot be left incomplete.', 'wc-authnet' ) );
			}

			// Check for card type supported or not
			if( ! in_array( $this->get_card_type( $_POST['authnet-card-number'], 'pattern', 'name' ), $this->allowed_card_types ) ) {
				throw new Exception( __( 'Card Type Not Accepted', 'wc-authnet' ) );
			}

			$expiry = explode( ' / ', $_POST['authnet-card-expiry'] );

			$description = sprintf( __( '%s - Order %s', 'wc-authnet' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order->get_order_number() );

			$payment_args = array(
				'card_num'	 		=> $_POST['authnet-card-number'],
				'exp_date'	 		=> $expiry[0] . $expiry[1],
				'card_code'	 		=> $_POST['authnet-card-cvc'],
				'description'		=> $description,
				'amount'			=> $order->get_total(),
				'type'				=> $this->capture ? 'sale' : 'auth',
				'first_name'		=> isset( $_POST['billing_first_name'] ) ? $_POST['billing_first_name'] : $order->get_billing_first_name(),
				'last_name'			=> isset( $_POST['billing_last_name'] ) ? $_POST['billing_last_name'] : $order->get_billing_last_name(),
				'address'			=> ( isset( $_POST['billing_address_1'] ) ? $_POST['billing_address_1'] : $order->get_billing_address_1() ) . ' ' . ( isset( $_POST['billing_address_2'] ) ? $_POST['billing_address_2'] : $order->get_billing_address_2() ),
				'city'				=> isset( $_POST['billing_city'] ) ? $_POST['billing_city'] : $order->get_billing_city(),
				'state'				=> isset( $_POST['billing_state'] ) ? $_POST['billing_state'] : $order->get_billing_state(),
				'country'			=> isset( $_POST['billing_country'] ) ? $_POST['billing_country'] : $order->get_billing_country(),
				'zip'				=> isset( $_POST['billing_postcode'] ) ? $_POST['billing_postcode'] : $order->get_billing_postcode(),
				'email' 			=> isset( $_POST['billing_email'] ) ? $_POST['billing_email'] : $order->get_billing_email(),
				'phone'				=> isset( $_POST['billing_phone'] ) ? $_POST['billing_phone'] : $order->get_billing_phone(),
				'company'			=> isset( $_POST['billing_company'] ) ? $_POST['billing_company'] : $order->get_billing_company(),
				'invoice_num'	 	=> $order_id,
				'trans_id'			=> $order->get_transaction_id(),
				'customer_ip'       => WC_Geolocation::get_ip_address(),
				'currency_code'		=> $this->get_payment_currency( $order_id ),
			);

			$response = $this->authnet_request( $payment_args );

			if ( $response->error || $response->declined ) {
				throw new Exception( $response->error_message );
			}

			// Store charge ID
			update_post_meta( $order_id, '_authnet_charge_id', $response->transaction_id );

			if ( $response->approved ) {
				$order->set_transaction_id( $response->transaction_id );

				if( $payment_args['type'] == 'sale' ) {

					// Store captured value
					update_post_meta( $order_id, '_authnet_charge_captured', 'yes' );
					update_post_meta( $order_id, 'Authorize.Net Payment ID', $response->transaction_id );

					// Payment complete
					$order->payment_complete( $response->transaction_id );

					// Add order note
					$complete_message = sprintf( __( 'Authorize.Net charge complete (Charge ID: %s)', 'wc-authnet' ), $response->transaction_id );
					$order->add_order_note( $complete_message );
					$this->log( "Success: $complete_message" );

				} else {

					// Store captured value
					update_post_meta( $order_id, '_authnet_charge_captured', 'no' );

					if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
						$order->reduce_order_stock();
					}

					// Mark as on-hold
					$authorized_message = sprintf( __( 'Authorize.Net charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'wc-authnet' ), $response->transaction_id );
					$order->update_status( 'on-hold', $authorized_message );
					$this->log( "Success: $authorized_message" );

				}

				$order->save();

			}

			// Remove cart
			WC()->cart->empty_cart();

			do_action( 'wc_gateway_' . $this->id . '_process_payment', $response, $order );

			// Return thank you page redirect
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);

		} catch ( Exception $e ) {
			wc_add_notice( sprintf( __( 'Error: %s', 'wc-authnet' ), $e->getMessage() ), 'error' );
			$this->log( sprintf( __( 'Error: %s', 'wc-authnet' ), $e->getMessage() ) );

			if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
				$this->send_failed_order_email( $order_id );
			}

			do_action( 'wc_gateway_' . $this->id . '_process_payment_error', $e, $order );

			return array(
				'result'   => 'fail',
				'redirect' => ''
			);

		}
	}

	function authnet_request( $args ) {
		if( !class_exists( 'AuthNet' ) ) {
			require_once( dirname( __FILE__ ) . '/authnet_sdk/AuthNet.php' );
		}
		$gateway_debug = ( $this->logging && $this->debugging );
		$transaction = new AuthNet( $this->login_id, $this->transaction_key, $gateway_debug );
		$transaction->setSandbox( $this->testmode );

		if( isset( $args['amount'] ) ) {
			$transaction->amount = $args['amount'];
		}
		if( isset( $args['card_num'] ) ) {
			$transaction->card_num = $args['card_num'];
		}
		if( isset( $args['exp_date'] ) ) {
			$transaction->exp_date = $args['exp_date'];
		}
		if( isset( $args['card_code'] ) ) {
			$transaction->card_code = $args['card_code'];
		}
		if( isset( $args['first_name'] ) ) {
			$transaction->first_name = $args['first_name'];
		}
		if( isset( $args['last_name'] ) ) {
			$transaction->last_name = $args['last_name'];
		}
		if( isset( $args['address'] ) ) {
			$transaction->address = $args['address'];
		}
		if( isset( $args['city'] ) ) {
			$transaction->city = $args['city'];
		}
		if( isset( $args['state'] ) ) {
			$transaction->state = $args['state'];
		}
		if( isset( $args['country'] ) ) {
			$transaction->country = $args['country'];
		}
		if( isset( $args['zip'] ) ) {
			$transaction->zip = $args['zip'];
		}
		if( isset( $args['email'] ) ) {
			$transaction->email = $args['email'];
		}
		if( isset( $args['phone'] ) ) {
			$transaction->phone = $args['phone'];
		}
		if( isset( $args['company'] ) ) {
			$transaction->company = $args['company'];
		}
		if( isset( $args['invoice_num'] ) ) {
			$transaction->invoice_num = $args['invoice_num'];
		}
		if( isset( $args['trans_id'] ) && !empty( $args['trans_id'] ) ) {
			$transaction->trans_id = $args['trans_id'];
		}
		if( isset( $args['description'] ) ) {
			$transaction->description = $args['description'];
		}

		$transaction->currency_code = isset( $args['currency_code'] ) ? $args['currency_code'] : get_woocommerce_currency();
		$transaction->email_customer = isset( $args['customer_receipt'] ) ? $args['customer_receipt'] : $this->customer_receipt;
		$transaction->customer_ip = isset( $args['customer_ip'] ) ? $args['customer_ip'] : WC_Geolocation::get_ip_address();

		$response = $transaction->{$args['type']}();

		return $response;
	}

	function get_card_type( $value, $field = 'pattern', $return = 'label' ) {
		$card_types = array(
			array(
				'label' => 'American Express',
				'name' => 'amex',
				'pattern' => '/^3[47]/',
				'valid_length' => '[15]'
			),
			array(
				'label' => 'JCB',
				'name' => 'jcb',
				'pattern' => '/^35(2[89]|[3-8][0-9])/',
				'valid_length' => '[16]'
			),
			array(
				'label' => 'Visa Electron',
				'name' => 'visa_electron',
				'pattern' => '/^(4026|417500|4508|4844|491(3|7))/',
				'valid_length' => '[16]'
			),
			array(
				'label' => 'Discover',
				'name' => 'discover',
				'pattern' => '/^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)/',
				'valid_length' => '[16]'
			),
			array(
				'label' => 'MasterCard',
				'name' => 'mastercard',
				'pattern' => '/^5[1-5]/',
				'valid_length' => '[16]'
			),
			array(
				'label' => 'Visa',
				'name' => 'visa',
				'pattern' => '/^4/',
				'valid_length' => '[16]'
			),
			array(
				'label' => 'Maestro',
				'name' => 'maestro',
				'pattern' => '/^(5018|5020|5038|6304|6759|676[1-3])/',
				'valid_length' => '[12, 13, 14, 15, 16, 17, 18, 19]'
			),
		);

		foreach( $card_types as $type ) {
			$card_type = $type['name'];
			$compare = $type[$field];
			if ( ( $field == 'pattern' && preg_match( $compare, $value, $match ) ) || $compare == $value ) {
				return $type[$return];
			}
		}

	}

	/**
	 * Get payment currency, either from current order or WC settings
	 *
	 * @since 4.1.0
	 * @return string three-letter currency code
	 */
	function get_payment_currency( $order_id = false ) {
 		$currency = get_woocommerce_currency();
		$order_id = ! $order_id ? $this->get_checkout_pay_page_order_id() : $order_id;

 		// Gets currency for the current order, that is about to be paid for
 		if ( $order_id ) {
 			$order    = wc_get_order( $order_id );
 			$currency = $order->get_currency();
 		}
 		return $currency;
 	}

	/**
	 * Returns true if $currency is accepted by this gateway
	 *
	 * @since 2.1.0
	 * @param string $currency optional three-letter currency code, defaults to
	 *        order currency (if available) or currently configured WooCommerce
	 *        currency
	 * @return boolean true if $currency is accepted, false otherwise
	 */
	public function currency_is_accepted( $currency = null ) {
		// accept all currencies
		if ( ! $this->currencies ) {
			return true;
		}
		// default to order/WC currency
		if ( is_null( $currency ) ) {
			$currency = $this->get_payment_currency();
		}
		return in_array( $currency, $this->currencies );
	}

	/**
	 * Returns the order_id if on the checkout pay page
	 *
	 * @since 3.0.0
	 * @return int order identifier
	 */
	public function get_checkout_pay_page_order_id() {
		global $wp;
		return isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
	}

	/**
	 * Send the request to Authorize.Net's API
	 *
	 * @since 2.6.10
	 *
	 * @param string $context
	 * @param string $message
	 */
	public function log( $message ) {
		if ( $this->logging ) {
			WC_AuthNet_Logger::log( $message );
		}
	}

	/**
	 * Sends the failed order email to admin
	 *
	 * @version 1.0.2
	 * @since 1.0.2
	 * @param int $order_id
	 * @return null
	 */
	public function send_failed_order_email( $order_id ) {
		$emails = WC()->mailer()->get_emails();
		if ( ! empty( $emails ) && ! empty( $order_id ) ) {
			$emails['WC_Email_Failed_Order']->trigger( $order_id );
		}
	}
}