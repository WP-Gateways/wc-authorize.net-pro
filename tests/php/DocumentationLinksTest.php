<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use WC_Authnet;

/**
 * Links to the customer docs: the Plugins screen row and the top of each gateway settings page.
 */
class DocumentationLinksTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'plugin_basename' )->alias( function ( $file ) {
			return basename( dirname( $file ) ) . '/' . basename( $file );
		} );
	}

	public function test_docs_url_points_at_the_authorize_net_docs(): void {
		$this->assertSame( 'https://docs.pledgedplugins.com/authorize-net/', WC_Authnet::DOCS_URL );
	}

	public function test_plugin_row_gets_a_documentation_link(): void {
		$plugin = $this->gateway( WC_Authnet::class );
		$links  = $plugin->plugin_row_meta( array( 'Version 1.0.0' ), plugin_basename( WC_AUTHNET_MAIN_FILE ) );

		$this->assertCount( 3, $links );
		$this->assertSame( 'Version 1.0.0', $links[0] );
		$this->assertStringContainsString( 'href="' . WC_Authnet::DOCS_URL . '"', $links[1] );
		$this->assertStringContainsString( '>Documentation</a>', $links[1] );
		$this->assertStringContainsString( 'href="https://example.com/wp-admin/admin.php?page=authnet-contact"', $links[2] );
		$this->assertStringContainsString( '>Support</a>', $links[2] );
	}

	/**
	 * Under the plugin name: Settings and About (both on-site). Support is in the row meta.
	 */
	public function test_action_links_have_settings_and_about_but_not_support(): void {
		Functions\when( 'admin_url' )->alias( function ( $path ) {
			return 'https://example.com/wp-admin/' . $path;
		} );
		$plugin = $this->gateway( WC_Authnet::class );
		$links  = $plugin->plugin_action_links( array( 'deactivate' => 'Deactivate' ) );

		$this->assertCount( 3, $links );
		$this->assertStringContainsString( '>Settings</a>', $links[0] );
		$this->assertStringContainsString( '>About</a>', $links[1] );
		$this->assertSame( 'Deactivate', $links['deactivate'] );
		$this->assertStringNotContainsString( 'Support', implode( '', $links ) );
	}

	public function test_other_plugin_rows_are_left_alone(): void {
		$plugin = $this->gateway( WC_Authnet::class );

		$this->assertSame( array( 'Version 1.0' ), $plugin->plugin_row_meta( array( 'Version 1.0' ), 'akismet/akismet.php' ) );
	}

	public function test_settings_page_docs_link(): void {
		$html = WC_Authnet::docs_link();

		$this->assertStringContainsString( 'href="' . WC_Authnet::DOCS_URL . '"', $html );
		$this->assertStringContainsString( 'target="_blank"', $html );
	}

	/**
	 * WooCommerce's Payments list shows the description as plain text, so it is a plain-text summary: the docs
	 * line, the sandbox note and the upgrade pitch would run into it there.
	 */
	public function test_description_is_a_plain_text_summary(): void {
		$description = $this->gateway( \WC_Gateway_Authnet::class )->get_gateway_description();

		$this->assertStringContainsString( 'credit or debit card', $description );
		$this->assertSame( strip_tags( $description ), $description );
		$this->assertStringNotContainsString( 'documentation', $description );
		$this->assertStringNotContainsString( 'Upgrade', $description );
	}

	/**
	 * The settings page shows the docs line above the description and the sandbox note and upgrade pitch below
	 * it, then leaves the description as it was.
	 */
	public function test_settings_page_adds_the_docs_line_and_the_notes(): void {
		$gateway                     = $this->gateway( \WC_Gateway_Authnet::class );
		$gateway->method_title       = 'Authorize.net';
		$gateway->method_description = 'Authorize.net description.';

		ob_start();
		$gateway->admin_options();
		$html = ob_get_clean();

		$this->assertStringContainsString( '<h2>Authorize.net</h2>' . WC_Authnet::docs_link() . 'Authorize.net description.<p>', $html );
		$this->assertMatchesRegularExpression( '/Live merchant accounts cannot be used in a sandbox environment.*<h3>Upgrade to Enterprise<\/h3>.*https:\/\/example\.com\/upgrade/s', $html );
		$this->assertSame( 'Authorize.net description.', $gateway->get_method_description() );
	}

	/**
	 * Both gateways, the Legacy AIM one included (it can't be loaded alongside the card gateway, so its source is
	 * checked), set a plain description and add the docs line only in admin_options().
	 */
	public function test_every_gateway_adds_the_docs_line_only_on_its_settings_page(): void {
		foreach ( array( 'includes/class-wc-gateway-authnet.php', 'includes/aim/class-wc-gateway-authnet.php' ) as $file ) {
			$source = file_get_contents( WC_AUTHNET_PLUGIN_PATH . '/' . $file );

			$this->assertMatchesRegularExpression( '/\$this->method_description\s*=\s*\$this->get_gateway_description\(\);/', $source, $file );
			$this->assertMatchesRegularExpression( '/function admin_options\(\).*\$this->method_description\s*=\s*WC_Authnet::docs_link\(\)\s*\.\s*\$description\s*\.\s*\$this->get_settings_note\(\);.*parent::admin_options\(\);.*\$this->method_description\s*=\s*\$description;/s', $source, $file );
			$this->assertSame( 1, substr_count( $source, 'WC_Authnet::docs_link()' ), $file );
		}
	}
}
