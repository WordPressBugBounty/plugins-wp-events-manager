<?php
/**
 * Legacy general settings page shim.
 *
 * @package WP-Events-Manager/Class
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPEMS_Admin_Setting_General', false ) ) {
	class_alias( 'WPEMS\Admin\Settings\General', 'WPEMS_Admin_Setting_General' );
}

return new \WPEMS\Admin\Settings\General();
