<?php
/**
 * Legacy email settings page shim.
 *
 * @package WP-Events-Manager/Class
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPEMS_Admin_Setting_Emails', false ) ) {
	class_alias( 'WPEMS\Admin\Settings\Emails', 'WPEMS_Admin_Setting_Emails' );
}

return new \WPEMS\Admin\Settings\Emails();
