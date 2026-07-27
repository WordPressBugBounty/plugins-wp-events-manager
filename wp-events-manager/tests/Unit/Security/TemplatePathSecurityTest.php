<?php
/**
 * Template path security tests.
 *
 * @package WPEMS\Tests\Unit\Security
 */

namespace WPEMS\Tests\Unit\Security;

use Brain\Monkey\Functions;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test template path hardening.
 */
class TemplatePathSecurityTest extends TestCase {

	/**
	 * Load template helpers with common WordPress doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'locate_template' )->justReturn( '' );
		Functions\when( '_doing_it_wrong' )->justReturn( true );
		Functions\when( 'do_action' )->justReturn( true );
		Functions\when( 'trailingslashit' )->alias(
			function ( $path ) {
				return rtrim( (string) $path, '/\\' ) . '/';
			}
		);

		require_once WPEMS_INC . 'wpems-core-functions.php';
	}

	/**
	 * It rejects traversal in template names.
	 *
	 * @return void
	 */
	public function test_locate_template_rejects_traversal_template_name(): void {
		$this->assertSame( '', wpems_locate_template( '../wp-config.php' ) );
		$this->assertSame( '', tp_event_locate_template( '../wp-config.php' ) );
	}

	/**
	 * It does not allow extracted args to override the selected template.
	 *
	 * @return void
	 */
	public function test_get_template_args_cannot_override_template_name(): void {
		ob_start();
		wpems_get_template(
			'safe.php',
			array(
				'template_name' => '../wp-config.php',
			),
			'',
			WPEMS_PATH . 'tests/fixtures/templates/'
		);
		$output = ob_get_clean();

		$this->assertSame( 'safe-template', $output );
	}

	/**
	 * It rejects filter-returned files outside allowed template directories.
	 *
	 * @return void
	 */
	public function test_get_template_rejects_filter_returned_outside_path(): void {
		$unsafe_file = tempnam( sys_get_temp_dir(), 'wpems-template-' );
		file_put_contents( $unsafe_file, "<?php echo 'unsafe-template';" );

		Functions\when( 'apply_filters' )->alias(
			function ( $tag, $value ) use ( $unsafe_file ) {
				if ( 'wpems_get_template' === $tag ) {
					return $unsafe_file;
				}

				return $value;
			}
		);

		ob_start();
		wpems_get_template( 'safe.php', array(), '', WPEMS_PATH . 'tests/fixtures/templates/' );
		$output = ob_get_clean();

		unlink( $unsafe_file );

		$this->assertSame( '', $output );
	}

	/**
	 * Admin templates are located under templates/admin.
	 *
	 * @return void
	 */
	public function test_admin_template_loader_uses_admin_template_directory(): void {
		$template = wpems_locate_template( 'settings/section-start.php', wpems_admin_template_path(), WPEMS_PATH . 'templates/admin/' );

		$this->assertSame(
			wpems_normalize_filesystem_path( realpath( WPEMS_PATH . 'templates/admin/settings/section-start.php' ) ),
			$template
		);
	}
}
