<?php
/*
Plugin Name: WooCommerce Authorize.Net Gateway
Plugin URI: https://pledgedplugins.com/products/authorize-net-payment-gateway-woocommerce/
Description: A payment gateway for Authorize.Net. A Authorize.Net account and a server with cURL, SSL support, and a valid SSL certificate is required (for security reasons) for this gateway to function. Requires WC 3.0.0+
Version: 4.0.0
Author: Pledged Plugins
Author URI: https://pledgedplugins.com
Text Domain: wc-authnet
Domain Path: /languages

	Copyright: © Pledged Plugins.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Authorize.Net class which sets the gateway up for us
 */
class WC_AuthNet {

	/**
	 * Constructor
	 */
	public function __construct() {
		define( 'WC_AUTHNET_VERSION', '4.0.0' );
		define( 'WC_AUTHNET_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
		define( 'WC_AUTHNET_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_AUTHNET_MAIN_FILE', __FILE__ );

		// required files
		require_once( 'includes/class-wc-gateway-authnet-logger.php' );

		// Actions
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'cancel_payment' ) );
	}

	/**
	 * Add relevant links to plugins page
	 * @param  array $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=authnet' ) . '">' . __( 'Settings', 'wc-authnet' ) . '</a>',
			'<a href="https://pledgedplugins.com/support/" target="_blank">' . __( 'Support', 'wc-authnet' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Init localisations and files
	 */
	public function init() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		// Includes
		include_once( 'includes/class-wc-gateway-authnet.php' );

		$this->load_plugin_textdomain();
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if
	 * the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/wc-authnet/wc-authnet-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/wc-authnet-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wc-authnet' );
		$dir    = trailingslashit( WP_LANG_DIR );

		load_textdomain( 'wc-authnet', $dir . 'wc-authnet/wc-authnet-' . $locale . '.mo' );
		load_plugin_textdomain( 'wc-authnet', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Register the gateway for use
	 */
	public function register_gateway( $methods ) {
		$methods[] = 'WC_Gateway_AuthNet';
		return $methods;
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @param  int $order_id
	 */
	public function capture_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_payment_method() == 'authnet' ) {
			$charge   = get_post_meta( $order_id, '_authnet_charge_id', true );
			$captured = get_post_meta( $order_id, '_authnet_charge_captured', true );

			if ( $charge && $captured == 'no' ) {
				$gateway = new WC_Gateway_AuthNet();
				$args = array(
					'amount'		=> $order->get_total(),
					'trans_id'		=> $order->get_transaction_id(),
					'type' 			=> 'capture',
				);
				$response = $gateway->authnet_request( $args );

				if ( $response->error || $response->declined ) {
					$order->add_order_note( __( 'Unable to capture charge!', 'wc-authnet' ) . ' ' . $response->error_message );
				} else {
					$complete_message = sprintf( __( 'Authorize.Net charge complete (Charge ID: %s)', 'wc-authnet' ), $response->transaction_id );
					$order->add_order_note( $complete_message );

					update_post_meta( $order_id, '_authnet_charge_captured', 'yes' );
					update_post_meta( $order_id, 'Authorize.Net Payment ID', $response->transaction_id );

					$order->set_transaction_id( $response->transaction_id );
					$order->save();
				}
			}
		}
	}

	/**
	 * Cancel pre-auth on refund/cancellation
	 *
	 * @param  int $order_id
	 */
	public function cancel_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_payment_method() == 'authnet' ) {
			$charge   = get_post_meta( $order_id, '_authnet_charge_id', true );

			if ( $charge ) {
				$gateway = new WC_Gateway_AuthNet();
				$args = array(
					'amount'		=> $order->get_total(),
					'trans_id'		=> $order->get_transaction_id(),
					'type' 			=> 'cancel',
				);
				$response = $gateway->authnet_request( $args );

				if ( $response->error || $response->declined ) {
					$order->add_order_note( __( 'Unable to refund charge!', 'wc-authnet' ) . ' ' . $response->error_message );
				} else {
					$cancel_message = sprintf( __( 'Authorize.Net charge refunded (Charge ID: %s)', 'wc-authnet' ), $response->transaction_id );
					$order->add_order_note( $cancel_message );

					delete_post_meta( $order_id, '_authnet_charge_captured' );
					delete_post_meta( $order_id, '_authnet_charge_id' );
					$order->save();
				}
			}
		}
	}

}
new WC_AuthNet();