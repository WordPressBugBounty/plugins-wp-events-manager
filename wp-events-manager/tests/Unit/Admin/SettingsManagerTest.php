<?php
/**
 * Admin settings manager tests.
 *
 * @package WPEMS\Tests\Unit\Admin
 */

namespace WPEMS\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use WPEMS\Admin\SettingsManager;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test admin settings helpers.
 */
class SettingsManagerTest extends TestCase {

	/**
	 * Captured option updates.
	 *
	 * @var array
	 */
	private $updates = array();

	/**
	 * Prepare doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->updates = array();

		Functions\when( 'update_option' )->alias(
			function ( $name, $value ) {
				$this->updates[ $name ] = $value;

				return true;
			}
		);
		Functions\when( 'wp_kses_post' )->alias(
			function ( $value ) {
				return strip_tags( (string) $value, '<strong><em><a>' );
			}
		);
	}

	/**
	 * It preserves falsey saved values.
	 *
	 * @return void
	 */
	public function test_get_option_preserves_falsey_saved_values(): void {
		Functions\when( 'get_option' )->alias(
			function ( $name, $default = null ) {
				$options = array(
					'thimpress_events_checkbox' => '0',
					'thimpress_events_yes_no'   => 'no',
				);

				return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
			}
		);

		$this->assertSame( '0', SettingsManager::get_option( 'thimpress_events_checkbox', '1' ) );
		$this->assertSame( 'no', SettingsManager::get_option( 'thimpress_events_yes_no', 'yes' ) );
	}

	/**
	 * It normalizes fields and escapes custom attributes.
	 *
	 * @return void
	 */
	public function test_normalize_field_builds_escaped_custom_attributes(): void {
		$field = SettingsManager::normalize_field(
			array(
				'id'   => 'thimpress_events_cancel_payment',
				'type' => 'number',
				'atts' => array(
					'min'      => 0,
					'disabled' => true,
					'onclick'  => false,
				),
				'step' => 'any',
			),
			false
		);

		$this->assertSame( 'thimpress_events_cancel_payment', $field['field_name'] );
		$this->assertStringContainsString( ' min="0"', $field['custom_attributes'] );
		$this->assertStringContainsString( ' step="any"', $field['custom_attributes'] );
		$this->assertStringContainsString( ' disabled', $field['custom_attributes'] );
		$this->assertStringNotContainsString( 'onclick', $field['custom_attributes'] );
	}

	/**
	 * It saves and sanitizes supported field types.
	 *
	 * @return void
	 */
	public function test_save_fields_sanitizes_supported_types(): void {
		$fields = array(
			array(
				'id'   => 'thimpress_events_text',
				'type' => 'text',
			),
			array(
				'id'   => 'thimpress_events_email',
				'type' => 'email',
			),
			array(
				'id'   => 'thimpress_events_number',
				'type' => 'number',
			),
			array(
				'id'   => 'thimpress_events_textarea',
				'type' => 'textarea',
			),
			array(
				'id'      => 'thimpress_events_select',
				'type'    => 'select',
				'default' => 'many',
				'options' => array(
					'once' => 'Once',
					'many' => 'Many',
				),
			),
			array(
				'id'      => 'thimpress_events_multi',
				'type'    => 'multiselect',
				'options' => array(
					'a' => 'A',
					'b' => 'B',
				),
			),
			array(
				'id'   => 'thimpress_events_checkbox',
				'type' => 'checkbox',
			),
			array(
				'id'   => 'thimpress_events_yes_no',
				'type' => 'yes_no',
			),
			array(
				'id'   => 'thimpress_events_page',
				'type' => 'select_page',
			),
			array(
				'id'   => 'ignored_section',
				'type' => 'section_start',
			),
		);

		$data = array(
			'thimpress_events_text'     => '<b> demo </b>',
			'thimpress_events_email'    => 'admin@@example.test',
			'thimpress_events_number'   => '12.5',
			'thimpress_events_textarea' => '<strong>Allowed</strong><script>bad</script>',
			'thimpress_events_select'   => 'invalid',
			'thimpress_events_multi'    => array( 'a', 'x' ),
			'thimpress_events_checkbox' => '1',
			'thimpress_events_yes_no'   => 'yes',
			'thimpress_events_page'     => '-44',
		);

		$this->assertTrue( SettingsManager::save_fields( $fields, $data ) );

		$this->assertSame( 'demo', $this->updates['thimpress_events_text'] );
		$this->assertSame( 'admin@@example.test', $this->updates['thimpress_events_email'] );
		$this->assertSame( '12.5', $this->updates['thimpress_events_number'] );
		$this->assertSame( '<strong>Allowed</strong>bad', $this->updates['thimpress_events_textarea'] );
		$this->assertSame( 'many', $this->updates['thimpress_events_select'] );
		$this->assertSame( array( 'a' ), $this->updates['thimpress_events_multi'] );
		$this->assertSame( '1', $this->updates['thimpress_events_checkbox'] );
		$this->assertSame( 'yes', $this->updates['thimpress_events_yes_no'] );
		$this->assertSame( 44, $this->updates['thimpress_events_page'] );
		$this->assertArrayNotHasKey( 'ignored_section', $this->updates );
	}

	/**
	 * It saves image-size width and height.
	 *
	 * @return void
	 */
	public function test_save_fields_handles_image_size_fields(): void {
		$fields = array(
			array(
				'id'      => 'thimpress_events_thumbnail',
				'type'    => 'image_size',
				'options' => array(
					'width'  => true,
					'height' => true,
				),
			),
		);

		$data = array(
			'thimpress_events_thumbnail_width'  => '-320',
			'thimpress_events_thumbnail_height' => '180',
		);

		$this->assertTrue( SettingsManager::save_fields( $fields, $data ) );
		$this->assertSame( 320, $this->updates['thimpress_events_thumbnail_width'] );
		$this->assertSame( 180, $this->updates['thimpress_events_thumbnail_height'] );
	}
}
