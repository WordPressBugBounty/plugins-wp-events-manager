<?php
/**
 * WP Events Manager Autoloader class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPEMS_Autoloader {

	/**
	 * Path to the includes directory
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor
	 */
	public function __construct() {
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( WPEMS_PATH ) . '/inc/';
	}

	/**
	 * Take a class name and turn it into a file name
	 *
	 * @param  string $class_name Class name.
	 *
	 * @return string
	 */
	private function get_file_name_from_class( $class_name ) {
		return 'class-' . str_replace( array( 'tp_', '_' ), array( '', '-' ), $class_name ) . '.php';
	}

	/**
	 * Include a class file
	 *
	 * @param  string $path
	 *
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		$base_path = realpath( $this->include_path );
		$file_path = $path ? realpath( $path ) : false;

		if ( $base_path && $file_path && 0 === strpos( $file_path, $base_path . DIRECTORY_SEPARATOR ) && is_readable( $file_path ) ) {
			include_once $file_path;
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WPEMS classes on demand to reduce memory consumption.
	 *
	 * @param string $class_name Class name.
	 */
	public function autoload( $class_name ) {
		$class_name = strtolower( $class_name );
		$file       = $this->get_file_name_from_class( $class_name );

		$legacy_admin_classes = array(
			'wpems_admin'                 => 'Admin/Admin.php',
			'wpems_admin_assets'          => 'Admin/Assets.php',
			'wpems_admin_menu'            => 'Admin/Menu.php',
			'wpems_admin_metaboxes'       => 'Admin/Metaboxes.php',
			'wpems_admin_metabox_booking' => 'Admin/Metaboxes/Booking.php',
			'wpems_admin_metabox_event'   => 'Admin/Metaboxes/Event.php',
			'wpems_admin_settings'        => 'Admin/Settings/class-wpems-admin-settings.php',
			'wpems_admin_users'           => 'Admin/Users.php',
		);

		if ( isset( $legacy_admin_classes[ $class_name ] ) ) {
			$this->load_file( $this->include_path . $legacy_admin_classes[ $class_name ] );
			return;
		}

		$path = $this->include_path;

		// payment gateways
		if ( strpos( $class_name, 'wpems_payment_gateway_' ) === 0 ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class_name ), strlen( 'wpems_payment_gateway_' ) ) . '/';
		}
		// abstract class
		if ( strpos( $class_name, 'wpems_abstract_' ) === 0 ) {
			$path = $this->include_path . 'abstracts/';
		}

		// widgets
		if ( stripos( $class_name, 'wpems_widget_' ) === 0 ) {
			$path = $this->include_path . '/widgets/';
		}

		$this->load_file( $path . $file );
	}
}

new WPEMS_Autoloader();
