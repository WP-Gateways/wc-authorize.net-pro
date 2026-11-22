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

		$this->assertCount( 2, $links );
		$this->assertSame( 'Version 1.0.0', $links[0] );
		$this->assertStringContainsString( 'href="' . WC_Authnet::DOCS_URL . '"', $links[1] );
		$this->assertStringContainsString( '>Documentation</a>', $links[1] );
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

	public function test_every_gateway_shows_the_docs_link_on_its_settings_page(): void {
		foreach ( array( 'includes/class-wc-gateway-authnet.php', 'includes/aim/class-wc-gateway-authnet.php' ) as $file ) {
			$this->assertMatchesRegularExpression(
				'/\$this->method_description\s*=\s*WC_Authnet::docs_link\(\)\s*\./',
				file_get_contents( WC_AUTHNET_PLUGIN_PATH . '/' . $file ),
				$file
			);
		}
	}
}
