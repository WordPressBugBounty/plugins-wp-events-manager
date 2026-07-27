<?php
/**
 * Admin menu.
 *
 * @package WPEMS\Admin
 */

namespace WPEMS\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * WP Events Manager admin menu.
 */
class Menu {

	/**
	 * Menus.
	 *
	 * @var array
	 */
	public $_menus = array();

	/**
	 * Instance.
	 *
	 * @var self|null
	 */
	public static $_instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Add admin menu callback.
	 *
	 * @return void
	 */
	public function admin_menu() {
		/**
		 * Menus.
		 *
		 * @var array
		 */
		$menus = apply_filters( 'tp_event_admin_menu', $this->_menus );
		add_menu_page( __( 'Events Manager', 'wp-events-manager' ), __( 'Events Manager', 'wp-events-manager' ), 'manage_options', 'tp-event-setting', null, 'dashicons-calendar-alt', 4 );
		if ( $menus ) {
			foreach ( $menus as $menu ) {
				call_user_func_array( 'add_submenu_page', $menu );
			}
		}
		add_submenu_page( 'tp-event-setting', __( 'WP Event Users', 'wp-events-manager' ), __( 'Users', 'wp-events-manager' ), 'list_users', 'tp-event-users', array( Users::class, 'output' ) );
		add_submenu_page( 'tp-event-setting', __( 'WP Event Settings', 'wp-events-manager' ), __( 'Settings', 'wp-events-manager' ), 'manage_options', 'tp-event-setting', array( SettingsManager::class, 'output' ) );
	}

	/**
	 * Add menu item.
	 *
	 * @param array $params Menu params.
	 *
	 * @return void
	 */
	public function add_menu( $params ) {
		$this->_menus[] = $params;
	}

	/**
	 * Instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( self::$_instance ) {
			return self::$_instance;
		}

		self::$_instance = new self();

		return self::$_instance;
	}
}

if ( ! \class_exists( 'WPEMS_Admin_Menu', false ) ) {
	\class_alias( Menu::class, 'WPEMS_Admin_Menu' );
}
