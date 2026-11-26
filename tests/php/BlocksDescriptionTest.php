<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use ReflectionClass;
use ReflectionMethod;
use WC_Authnet_Blocks_Support;

/**
 * The card block shows the gateway description as classic checkout does, including the test mode notice and
 * the test card instructions the gateway adds to it in test mode (as the CardPointe blocks do).
 */
class BlocksDescriptionTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wptexturize' )->returnArg();
		Functions\when( 'wp_kses_post' )->alias(
			function ( $html ) {
				return strip_tags( $html, '<strong><a><p><br>' );
			}
		);
		Functions\when( 'wpautop' )->alias(
			function ( $text ) {
				return '<p>' . str_replace( "\n\n", "</p>\n<p>", $text ) . "</p>\n";
			}
		);
	}

	/**
	 * @param string|null $description The card gateway's description, or null when the gateway isn't available.
	 * @return string What the block gets.
	 */
	private function description( $description ) {
		$gateways = array();

		if ( null !== $description ) {
			$gateways['authnet'] = new class( $description ) {
				public $description;

				public function __construct( $description ) {
					$this->description = $description;
				}

				public function get_description() {
					return $this->description;
				}
			};
		}

		Functions\when( 'WC' )->justReturn(
			(object) array(
				'payment_gateways' => new class( $gateways ) {
					private $gateways;

					public function __construct( $gateways ) {
						$this->gateways = $gateways;
					}

					public function payment_gateways() {
						return $this->gateways;
					}

					public function get_available_payment_gateways() {
						return $this->gateways;
					}
				},
			)
		);

		$blocks = ( new ReflectionClass( WC_Authnet_Blocks_Support::class ) )->newInstanceWithoutConstructor();
		$method = new ReflectionMethod( $blocks, 'get_description' );
		$method->setAccessible( true );

		return $method->invoke( $blocks );
	}

	public function test_the_description_is_sent_as_html_with_the_test_mode_notice() {
		$this->assertSame(
			"<p>Pay with your card.</p>\n<p><strong>TEST MODE ENABLED</strong>\nUse 4111111111111111.alert(1)</p>\n",
			$this->description( "Pay with your card.\n\n<strong>TEST MODE ENABLED</strong>\nUse 4111111111111111.<script>alert(1)</script>" )
		);
	}

	public function test_no_description_is_an_empty_string() {
		$this->assertSame( '', $this->description( "  \n" ) );
	}

	public function test_no_gateway_is_an_empty_string() {
		$this->assertSame( '', $this->description( null ) );
	}

	public function test_the_block_data_carries_the_description() {
		$php = (string) file_get_contents( WC_AUTHNET_PLUGIN_PATH . '/includes/class-wc-authnet-blocks-support.php' );

		$this->assertMatchesRegularExpression( "/'description'\s*=>\s*\\\$this->get_description\(\)/", $php );
	}
}
