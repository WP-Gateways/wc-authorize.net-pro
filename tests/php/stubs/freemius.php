<?php
/**
 * Stand-in for the plugin's Freemius instance. The free plugin has no paid plan, so the plan checks return
 * false unless a test sets WC_Authnet_Test_Freemius::$enterprise to true.
 */
class WC_Authnet_Test_Freemius {

	public static $enterprise = false;

	public function is_plan( $plan, $exact = false ) {
		return self::$enterprise;
	}

	public function is_plan__premium_only( $plan, $exact = false ) {
		return self::$enterprise;
	}

	public function is__premium_only() {
		return self::$enterprise;
	}

	public function can_use_premium_code() {
		return self::$enterprise;
	}

	public function add_filter( $tag, $callback ) {
		return true;
	}

	public function get_upgrade_url() {
		return 'https://example.com/upgrade';
	}
}

function wc_authnet_fs() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new WC_Authnet_Test_Freemius();
	}
	return $instance;
}
