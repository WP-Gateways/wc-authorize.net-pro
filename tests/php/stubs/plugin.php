<?php
/**
 * Stand-ins for classes the plugin's include files use but tests don't load: the main plugin class
 * (in gateway.php), Store API and Blocks classes, and WooCommerce's logger and HTTPS helper.
 */

namespace Automattic\WooCommerce\StoreApi\Exceptions {

	class RouteException extends \Exception {

		public $error_code;

		public function __construct( $error_code, $message, $http_status_code = 400 ) {
			$this->error_code = $error_code;
			parent::__construct( $message, $http_status_code );
		}
	}
}

namespace Automattic\WooCommerce\Blocks\Payments\Integrations {

	abstract class AbstractPaymentMethodType {

		protected $name = '';

		protected $settings = array();

		public function get_name() {
			return $this->name;
		}
	}
}

namespace {

	/**
	 * The main plugin class from gateway.php. Records how it was created and the payments it cancels.
	 */
	class WC_Authnet {

		public static $constructed = 0;

		public static $cancelled = array();

		private static $instance;

		public function __construct() {
			self::$constructed++;
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public static function reset() {
			self::$constructed = 0;
			self::$cancelled   = array();
			self::$instance    = null;
		}

		public function cancel_payment( $order_id ) {
			self::$cancelled[] = $order_id;
		}
	}

	class WC_Logger {}

	class WC_HTTPS {

		public static function force_https_url( $url ) {
			return str_replace( 'http://', 'https://', $url );
		}
	}
}
