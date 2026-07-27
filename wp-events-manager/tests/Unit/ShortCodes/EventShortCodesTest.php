<?php
/**
 * Event shortcode tests.
 *
 * @package WPEMS\Tests\Unit\ShortCodes
 */

namespace WPEMS\Tests\Unit\ShortCodes;

use Brain\Monkey\Functions;
use WPEMS\ShortCodes\AbstractShortcode;
use WPEMS\ShortCodes\AccountShortcode;
use WPEMS\ShortCodes\CountdownShortcode;
use WPEMS\ShortCodes\EventShortCodes;
use WPEMS\ShortCodes\ForgotPasswordShortcode;
use WPEMS\ShortCodes\ListEventShortcode;
use WPEMS\ShortCodes\LoginShortcode;
use WPEMS\ShortCodes\RegisterShortcode;
use WPEMS\ShortCodes\ResetPasswordShortcode;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test shortcode registration and request sanitization.
 */
class EventShortCodesTest extends TestCase {

	/**
	 * Reset shortcode test double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		\WPEMS_Shortcodes::reset();
		$this->resetStaticProperty( AbstractShortcode::class, 'instances', array() );
	}

	/**
	 * It registers public shortcodes.
	 *
	 * @return void
	 */
	public function test_init_registers_shortcodes(): void {
		$actions    = array();
		$shortcodes = array();

		Functions\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$actions ) {
				$actions[ $hook ] = $callback;
			}
		);
		Functions\when( 'add_shortcode' )->alias(
			function ( $tag, $callback ) use ( &$shortcodes ) {
				$shortcodes[ $tag ] = $callback;
			}
		);

		EventShortCodes::init();

		$this->assertArrayHasKey( 'tp_event_shortcode_wrapper_start', $actions );
		$this->assertArrayHasKey( 'tp_event_shortcode_wrapper_end', $actions );
		$this->assertArrayHasKey( 'template_redirect', $actions );
		$this->assertCount( 7, $shortcodes );
		$this->assertArrayHasKey( 'wp_event_list_event', $shortcodes );
		$this->assertArrayHasKey( 'wp_event_reset_password', $shortcodes );
		$this->assertInstanceOf( ListEventShortcode::class, $shortcodes['wp_event_list_event'][0] );
		$this->assertInstanceOf( RegisterShortcode::class, $shortcodes['wp_event_register'][0] );
		$this->assertInstanceOf( LoginShortcode::class, $shortcodes['wp_event_login'][0] );
		$this->assertInstanceOf( ForgotPasswordShortcode::class, $shortcodes['wp_event_forgot_password'][0] );
		$this->assertInstanceOf( ResetPasswordShortcode::class, $shortcodes['wp_event_reset_password'][0] );
		$this->assertInstanceOf( AccountShortcode::class, $shortcodes['wp_event_account'][0] );
		$this->assertInstanceOf( CountdownShortcode::class, $shortcodes['wp_event_countdown'][0] );
		$this->assertSame( 'render', $shortcodes['wp_event_list_event'][1] );
	}

	/**
	 * It preserves shortcode tag filters.
	 *
	 * @return void
	 */
	public function test_init_preserves_shortcode_tag_filters(): void {
		$shortcodes = array();

		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value = null ) {
				if ( 'wp_event_login_shortcode_tag' === $hook ) {
					return 'custom_event_login';
				}

				return $value;
			}
		);
		Functions\when( 'add_shortcode' )->alias(
			function ( $tag, $callback ) use ( &$shortcodes ) {
				$shortcodes[ $tag ] = $callback;
			}
		);

		EventShortCodes::init();

		$this->assertArrayHasKey( 'custom_event_login', $shortcodes );
		$this->assertArrayNotHasKey( 'wp_event_login', $shortcodes );
		$this->assertInstanceOf( LoginShortcode::class, $shortcodes['custom_event_login'][0] );
	}

	/**
	 * It sanitizes forgot-password request values.
	 *
	 * @return void
	 */
	public function test_forgot_password_adds_notice_from_sanitized_request(): void {
		$_REQUEST['checkemail'] = '<b>confirm</b>';

		Functions\when( 'wpems_get_page_id' )->justReturn( 10 );
		Functions\expect( 'wpems_add_notice' )
			->once()
			->with( 'success', 'Check your email for a link to reset your password.' );

		$this->assertSame( '', EventShortCodes::forgot_password( array() ) );
	}

	/**
	 * It passes sanitized reset-password attributes to the renderer.
	 *
	 * @return void
	 */
	public function test_reset_password_passes_sanitized_atts(): void {
		$_REQUEST['key']        = '<b>abc123</b>';
		$_REQUEST['login']      = '<script>demo</script>';
		$_REQUEST['checkemail'] = 'confirm';

		Functions\when( 'wpems_get_page_id' )->justReturn( 10 );
		Functions\expect( 'wpems_add_notice' )
			->once()
			->with( 'success', 'Check your email for a link to reset your password.' );

		$output = EventShortCodes::reset_password( array() );

		$this->assertSame( 'rendered:reset-password', $output );
		$this->assertSame( 'reset-password', \WPEMS_Shortcodes::$last_render[0] );
		$this->assertSame( 'reset-password.php', \WPEMS_Shortcodes::$last_render[1] );
		$this->assertSame( 'abc123', \WPEMS_Shortcodes::$last_render[2]['atts']['key'] );
		$this->assertSame( 'demo', \WPEMS_Shortcodes::$last_render[2]['atts']['login'] );
		$this->assertTrue( \WPEMS_Shortcodes::$last_render[2]['atts']['checkemail'] );
	}

	/**
	 * It delegates list rendering to the legacy renderer facade.
	 *
	 * @return void
	 */
	public function test_list_event_delegates_to_renderer(): void {
		$output = EventShortCodes::list_event( array( 'limit' => 3 ) );

		$this->assertSame( 'rendered:list-event', $output );
		$this->assertSame( array( 'atts' => array( 'limit' => 3 ) ), \WPEMS_Shortcodes::$last_render[2] );
	}
}
