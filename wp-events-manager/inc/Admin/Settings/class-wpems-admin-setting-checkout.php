<?php
/**
 * Legacy checkout settings page shim.
 *
 * @package WP-Events-Manager/Class
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPEMS_Admin_Setting_Checkout', false ) ) {
	class_alias( 'WPEMS\Admin\Settings\Checkout', 'WPEMS_Admin_Setting_Checkout' );
}

return new \WPEMS\Admin\Settings\Checkout();
