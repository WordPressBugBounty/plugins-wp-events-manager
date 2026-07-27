<?php
/**
 * Settings storage tests.
 *
 * @package WPEMS\Tests\Unit\Settings
 */

namespace WPEMS\Tests\Unit\Settings;

use Brain\Monkey\Functions;
use WPEMS\Settings;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test settings storage compatibility.
 */
class SettingsStorageTest extends TestCase {

	/**
	 * Option store.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Prepare option doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = array(
			'thimpress_events'          => array(
				'legacy_key' => 'legacy-value',
			),
			'thimpress_events_currency' => 'EUR',
		);

		Functions\when( 'get_option' )->alias(
			function ( $name, $default = null ) {
				return array_key_exists( $name, $this->options ) ? $this->options[ $name ] : $default;
			}
		);

		Functions\when( 'update_option' )->alias(
			function ( $name, $value ) {
				$this->options[ $name ] = $value;

				return true;
			}
		);
	}

	/**
	 * Logical keys resolve to canonical per-option storage.
	 *
	 * @return void
	 */
	public function test_get_resolves_logical_key_to_prefixed_option(): void {
		$settings = new Settings();

		$this->assertSame( 'EUR', $settings->get( 'currency', 'USD' ) );
	}

	/**
	 * Already-prefixed keys are not prefixed twice.
	 *
	 * @return void
	 */
	public function test_get_accepts_already_prefixed_option_name(): void {
		$settings = new Settings();

		$this->assertSame( 'EUR', $settings->get( 'thimpress_events_currency', 'USD' ) );
	}

	/**
	 * It falls back to the legacy array option.
	 *
	 * @return void
	 */
	public function test_get_preserves_legacy_array_fallback(): void {
		$settings = new Settings();

		$this->assertSame( 'legacy-value', $settings->get( 'legacy_key', 'default' ) );
	}

	/**
	 * Set writes to canonical per-option storage.
	 *
	 * @return void
	 */
	public function test_set_updates_prefixed_option(): void {
		$settings = new Settings();

		$this->assertTrue( $settings->set( 'currency', 'GBP' ) );
		$this->assertSame( 'GBP', $this->options['thimpress_events_currency'] );
	}
}
