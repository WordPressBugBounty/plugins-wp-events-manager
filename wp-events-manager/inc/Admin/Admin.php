<?php
/**
 * Admin bootstrap.
 *
 * @package WPEMS\Admin
 */

namespace WPEMS\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * WP Events Manager admin bootstrap.
 */
class Admin {

	/**
	 * Whether admin hooks have been registered.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Register admin components.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		Menu::instance();
		Assets::init();
		Metaboxes::init();
		SettingsManager::init();
	}
}

if ( ! \class_exists( 'WPEMS_Admin', false ) ) {
	\class_alias( Admin::class, 'WPEMS_Admin' );
}
