<?php
/**
 * Legacy abstract setting class shim.
 *
 * @package WP-Events-Manager/Class
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPEMS_Abstract_Setting', false ) ) {
	/**
	 * Backward-compatible global abstract setting class.
	 */
	abstract class WPEMS_Abstract_Setting extends \WPEMS\Admin\Settings\AbstractSetting {}
}
