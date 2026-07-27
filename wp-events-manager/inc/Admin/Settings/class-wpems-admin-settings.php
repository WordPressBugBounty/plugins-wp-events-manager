<?php
/**
 * Legacy admin settings class shim.
 *
 * @package WP-Events-Manager/Class
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPEMS_Admin_Settings', false ) ) {
	/**
	 * Backward-compatible global admin settings class.
	 */
	class WPEMS_Admin_Settings extends \WPEMS\Admin\SettingsManager {}
}

WPEMS_Admin_Settings::init();
