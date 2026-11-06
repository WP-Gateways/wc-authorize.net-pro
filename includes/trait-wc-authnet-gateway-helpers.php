<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Authnet_Gateway_Helpers trait.
 *
 * Helpers the card and Legacy AIM gateways share: line item names, the payment currency,
 * the pay-for-order page, AVS / CVV messages and the log URL.
 */
trait WC_Authnet_Gateway_Helpers {

	/**
	 * Taken from https://gist.github.com/jaywilliams/119517
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public function format_line_item( $string ) {

		// Replace Single Curly Quotes
		$search[]  = chr( 226 ) . chr( 128 ) . chr( 152 );
		$replace[] = "'";
		$search[]  = chr( 226 ) . chr( 128 ) . chr( 153 );
		$replace[] = "'";

		// Replace Smart Double Curly Quotes
		$search[]  = chr( 226 ) . chr( 128 ) . chr( 156 );
		$replace[] = '"';
		$search[]  = chr( 226 ) . chr( 128 ) . chr( 157 );
		$replace[] = '"';

		// Replace En Dash
		$search[]  = chr( 226 ) . chr( 128 ) . chr( 147 );
		$replace[] = '--';

		// Replace Em Dash
		$search[]  = chr( 226 ) . chr( 128 ) . chr( 148 );
		$replace[] = '---';

		// Replace Bullet
		$search[]  = chr( 226 ) . chr( 128 ) . chr( 162 );
		$replace[] = '*';

		// Replace Middle Dot
		$search[]  = chr( 194 ) . chr( 183 );
		$replace[] = '*';

		// Replace Ellipsis with three consecutive dots
		$search[]  = chr( 226 ) . chr( 128 ) . chr( 166 );
		$replace[] = '...';

		// Replace Ampersand with dash
		$search[]  = '&';
		$replace[] = '-';

		// Replace Percentage with pc char
		$search[]  = '%';
		$replace[] = 'pc';

		// Apply Replacements
		$string = str_replace( $search, $replace, $string );

		// Remove any non-ASCII Characters
		$string = preg_replace( "/[^\x01-\x7F]/", "", $string );

		return $string;
	}

	/**
	 * Returns the order_id if on the checkout pay page
	 *
	 * @since 3.3
	 * @return int order identifier
	 */
	public function get_checkout_pay_page_order_id() {
		global $wp;

		return isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
	}

	public function get_payment_currency( $order_id = false ) {
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
	 * get_avs_message function.
	 *
	 * @access public
	 * @param string $code
	 * @return string
	 */
	public function get_avs_message( $code ) {
		$avs_messages = array(
			'A' => __( 'Street Address: Match -- First 5 Digits of ZIP: No Match', 'wc-authnet' ),
			'B' => __( 'Address not provided for AVS check or street address match, postal code could not be verified', 'wc-authnet' ),
			'E' => __( 'AVS Error', 'wc-authnet' ),
			'G' => __( 'Non U.S. Card Issuing Bank', 'wc-authnet' ),
			'N' => __( 'Street Address: No Match -- First 5 Digits of ZIP: No Match', 'wc-authnet' ),
			'P' => __( 'AVS not applicable for this transaction', 'wc-authnet' ),
			'R' => __( 'Retry, System Is Unavailable', 'wc-authnet' ),
			'S' => __( 'AVS Not Supported by Card Issuing Bank', 'wc-authnet'),
			'U' => __( 'Address Information For This Cardholder Is Unavailable', 'wc-authnet' ),
			'W' => __( 'Street Address: No Match -- All 9 Digits of ZIP: Match', 'wc-authnet' ),
			'X' => __( 'Street Address: Match -- All 9 Digits of ZIP: Match', 'wc-authnet' ),
			'Y' => __( 'Street Address: Match - First 5 Digits of ZIP: Match', 'wc-authnet' ),
			'Z' => __( 'Street Address: No Match - First 5 Digits of ZIP: Match', 'wc-authnet' ),
		);

		if ( array_key_exists( $code, $avs_messages ) ) {
			return $code . ' - ' . $avs_messages[$code];
		} else {
			return $code;
		}
	}

	/**
	 * get_cvv_message function.
	 *
	 * @access public
	 * @param string $code
	 * @return string
	 */
	public function get_cvv_message( $code ) {
		$cvv_messages = array(
			'M' => __( 'CVV2/CVC2 Match', 'wc-authnet' ),
			'N' => __( 'CVV2 / CVC2 No Match', 'wc-authnet' ),
			'P' => __( 'Not Processed', 'wc-authnet' ),
			'S' => __( 'Merchant Has Indicated that CVV2 / CVC2 is not present on card', 'wc-authnet' ),
			'U' => __( 'Issuer is not certified and/or has not provided visa encryption keys', 'wc-authnet' ),
		);

		if ( array_key_exists( $code, $cvv_messages ) ) {
			return $code . ' - ' . $cvv_messages[$code];
		} else {
			return $code;
		}
	}

	public function is_valid_pay_for_order_endpoint() {
		// If not on the pay for order page, return false.
		if ( ! is_wc_endpoint_url( 'order-pay' ) || ! isset( $_GET['key'] ) ) {
			return false;
		}
		$order_id = absint( get_query_var( 'order-pay' ) );
		$order    = wc_get_order( $order_id );
		// If the order is not found or the param `key` is not set or the order key does not match the order key in the URL param, return false.
		if ( ! $order || ! isset( $_GET['key'] ) || wc_clean( wp_unslash( $_GET['key'] ) ) !== $order->get_order_key() ) {
			return false;
		}
		// If the order doesn't need payment, we don't need to prepare the payment page.
		if ( ! $order->needs_payment() ) {
			return false;
		}
		return current_user_can( 'pay_for_order', $order->get_id() );
	}

	public function get_log_url() {
		$log_file_name = WC_Log_Handler_File::get_log_file_name( 'woocommerce-gateway-authnet' );
		$name_array    = explode( '-', $log_file_name );
		if ( ! empty( $name_array[6] ) ) {
			unset( $name_array[6] );
		}
		return admin_url( 'admin.php?page=wc-status&tab=logs&view=single_file&file_id=' . implode( '-', $name_array ) );
	}

}
