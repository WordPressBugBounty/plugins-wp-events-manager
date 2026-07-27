<?php
/**
 * Legacy pages settings page shim.
 *
 * @package WP-Events-Manager/Class
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPEMS_Admin_Setting_Pages', false ) ) {
	class_alias( 'WPEMS\Admin\Settings\Pages', 'WPEMS_Admin_Setting_Pages' );
}

return new \WPEMS\Admin\Settings\Pages();
