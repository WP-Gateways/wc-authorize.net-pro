<?php
/**
 * Stand-ins for classes the plugin's include files use but tests don't load: Store API and Blocks
 * classes, and WooCommerce's logger and HTTPS helper.
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

	class WC_Logger {}

	class WC_HTTPS {

		public static function force_https_url( $url ) {
			return str_replace( 'http://', 'https://', $url );
		}
	}
}
