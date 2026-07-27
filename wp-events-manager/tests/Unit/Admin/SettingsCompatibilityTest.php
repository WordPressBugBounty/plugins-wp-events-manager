<?php
/**
 * Settings compatibility tests.
 *
 * @package WPEMS\Tests\Unit\Admin
 */

namespace WPEMS\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use WPEMS\Admin\Admin;
use WPEMS\Admin\Assets;
use WPEMS\Admin\Menu;
use WPEMS\Admin\Metaboxes;
use WPEMS\Admin\Metaboxes\Booking;
use WPEMS\Admin\Metaboxes\Event;
use WPEMS\Admin\Settings\Checkout;
use WPEMS\Admin\Settings\Emails;
use WPEMS\Admin\Settings\General;
use WPEMS\Admin\Settings\Pages;
use WPEMS\Admin\SettingsManager;
use WPEMS\Settings;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test PSR-4 and legacy settings compatibility.
 */
class SettingsCompatibilityTest extends TestCase {

	/**
	 * Prepare hook doubles and reset caches.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'wpems_get_option' )->justReturn( '' );
		Functions\when( 'wpems_currencies' )->justReturn(
			array(
				'USD' => 'US Dollar',
			)
		);
		Functions\when( 'wpems_get_template_content' )->justReturn( 'email body' );
		Functions\when( 'wpems_payment_gateways' )->justReturn( array() );

		SettingsManager::reset_setting_pages();
	}

	/**
	 * Namespaced settings classes are available through PSR-4.
	 *
	 * @return void
	 */
	public function test_namespaced_settings_classes_are_available(): void {
		$this->assertTrue( class_exists( Settings::class ) );
		$this->assertTrue( class_exists( SettingsManager::class ) );
		$this->assertTrue( class_exists( General::class ) );
		$this->assertTrue( class_exists( Pages::class ) );
		$this->assertTrue( class_exists( Emails::class ) );
		$this->assertTrue( class_exists( Checkout::class ) );
		$this->assertTrue( class_exists( Admin::class ) );
		$this->assertTrue( class_exists( Assets::class ) );
		$this->assertTrue( class_exists( Menu::class ) );
		$this->assertTrue( class_exists( Metaboxes::class ) );
		$this->assertTrue( class_exists( Booking::class ) );
		$this->assertTrue( class_exists( Event::class ) );
	}

	/**
	 * Legacy global classes still resolve.
	 *
	 * @return void
	 */
	public function test_legacy_global_classes_still_resolve(): void {
		require_once WPEMS_INC . 'class-wpems-settings.php';
		require_once WPEMS_INC . 'Admin/Settings/class-wpems-admin-settings.php';
		require_once WPEMS_INC . 'abstracts/class-wpems-abstract-setting.php';

		$this->assertTrue( class_exists( '\WPEMS_Settings' ) );
		$this->assertTrue( class_exists( '\WPEMS_Admin_Settings' ) );
		$this->assertTrue( class_exists( '\WPEMS_Abstract_Setting' ) );
		$this->assertInstanceOf( Settings::class, new \WPEMS_Settings() );
	}

	/**
	 * Legacy setting page files return namespaced page instances and alias old names.
	 *
	 * @return void
	 */
	public function test_legacy_setting_page_files_return_namespaced_instances(): void {
		$general  = require WPEMS_INC . 'Admin/Settings/class-wpems-admin-setting-general.php';
		$pages    = require WPEMS_INC . 'Admin/Settings/class-wpems-admin-setting-pages.php';
		$emails   = require WPEMS_INC . 'Admin/Settings/class-wpems-admin-setting-emails.php';
		$checkout = require WPEMS_INC . 'Admin/Settings/class-wpems-admin-setting-checkout.php';

		$this->assertInstanceOf( General::class, $general );
		$this->assertInstanceOf( Pages::class, $pages );
		$this->assertInstanceOf( Emails::class, $emails );
		$this->assertInstanceOf( Checkout::class, $checkout );
		$this->assertTrue( class_exists( '\WPEMS_Admin_Setting_General' ) );
		$this->assertTrue( class_exists( '\WPEMS_Admin_Setting_Pages' ) );
		$this->assertTrue( class_exists( '\WPEMS_Admin_Setting_Emails' ) );
		$this->assertTrue( class_exists( '\WPEMS_Admin_Setting_Checkout' ) );
	}

	/**
	 * Setting pages are cached.
	 *
	 * @return void
	 */
	public function test_get_setting_pages_caches_instances(): void {
		$first  = SettingsManager::get_setting_pages();
		$second = SettingsManager::get_setting_pages();

		$this->assertCount( 4, $first );
		$this->assertSame( $first, $second );
	}
}
