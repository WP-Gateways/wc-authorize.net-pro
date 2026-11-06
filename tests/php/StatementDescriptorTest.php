<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use WC_Authnet_API;

/**
 * The Statement Descriptor starts each transaction's description. An empty setting means the site name,
 * as the setting's help text says.
 */
class StatementDescriptorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'get_bloginfo' )->justReturn( 'Jane &amp; Co' );
		Functions\when( 'wp_specialchars_decode' )->alias( 'htmlspecialchars_decode' );
	}

	public function test_an_empty_descriptor_is_the_site_name() {
		$this->assertSame( 'Jane & Co', WC_Authnet_API::descriptor_or_site_name( '' ) );
		$this->assertSame( 'Jane & Co', WC_Authnet_API::descriptor_or_site_name( '  ' ) );
		$this->assertSame( 'Jane & Co', WC_Authnet_API::descriptor_or_site_name( null ) );
	}

	public function test_a_set_descriptor_is_kept() {
		$this->assertSame( 'JANECO', WC_Authnet_API::descriptor_or_site_name( 'JANECO' ) );
	}

	public function test_the_api_uses_the_site_name_when_the_saved_setting_is_empty() {
		Functions\when( 'get_option' )->justReturn( array( 'statement_descriptor' => '' ) );
		WC_Authnet_API::set_statement_descriptor( '' );

		$this->assertSame( 'Jane & Co', WC_Authnet_API::get_statement_descriptor() );
	}
}
