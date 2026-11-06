<?php
namespace WCAuthnet\Tests;

use Brain\Monkey\Functions;
use ReflectionClass;
use ReflectionMethod;
use WC_Authnet_Blocks_Support;

/**
 * The card icons the block checkout shows: one per allowed card type, like classic checkout, whatever
 * the store currency.
 */
class BlocksIconsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'WC' )->justReturn(
			new class() {
				public function plugin_url() {
					return 'http://shop.example/wp-content/plugins/woocommerce';
				}
			}
		);
		Functions\when( 'get_woocommerce_currency' )->justReturn( 'EUR' );
	}

	private function icons( array $settings ) {
		$reflection = new ReflectionClass( WC_Authnet_Blocks_Support::class );
		$blocks     = $reflection->newInstanceWithoutConstructor();

		$property = $reflection->getProperty( 'settings' );
		$property->setAccessible( true );
		$property->setValue( $blocks, $settings );

		$method = new ReflectionMethod( $blocks, 'get_icons' );
		$method->setAccessible( true );
		return $method->invoke( $blocks );
	}

	public function test_no_allowed_card_types_gives_no_icons() {
		$this->assertSame( array(), $this->icons( array( 'allowed_card_types' => array() ) ) );
	}

	public function test_missing_card_type_setting_gives_no_icons() {
		$this->assertSame( array(), $this->icons( array() ) );
	}

	public function test_every_allowed_card_type_has_an_icon_in_any_currency() {
		$icons = $this->icons( array( 'allowed_card_types' => array( 'visa', 'discover', 'jcb', 'diners-club' ) ) );

		$this->assertSame( array( 'visa', 'discover', 'jcb', 'diners' ), array_keys( $icons ) );
		$this->assertSame( 'https://shop.example/wp-content/plugins/woocommerce/assets/images/icons/credit-cards/jcb.svg', $icons['jcb']['src'] );
	}
}
