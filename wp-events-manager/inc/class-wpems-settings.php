<?php
/**
 * Legacy settings class shim.
 *
 * @package WP-Events-Manager/Class
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPEMS_Settings', false ) ) {
	/**
	 * Backward-compatible global settings class.
	 */
	class WPEMS_Settings extends \WPEMS\Settings {}
}
