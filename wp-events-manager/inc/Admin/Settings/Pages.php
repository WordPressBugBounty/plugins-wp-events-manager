<?php
/**
 * Pages settings page.
 *
 * @package WPEMS\Admin\Settings
 */

namespace WPEMS\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Pages settings.
 */
class Pages extends AbstractSetting {

	/**
	 * Setting page ID.
	 *
	 * @var string|null
	 */
	public $id = null;

	/**
	 * Setting page label.
	 *
	 * @var string|null
	 */
	public $label = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'event_pages';
		$this->label = __( 'Pages', 'wp-events-manager' );

		parent::__construct();
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		$prefix = 'thimpress_events_';

		return apply_filters(
			'event_admin_setting_page_' . $this->id,
			array(
				array(
					'type'  => 'section_start',
					'id'    => 'pages_settings',
					'title' => __( 'Pages Settings', 'wp-events-manager' ),
				),
				array(
					'type'  => 'select_page',
					'title' => __( 'Register Page', 'wp-events-manager' ),
					'desc'  => __( 'This controls which the register page', 'wp-events-manager' ),
					'id'    => $prefix . 'register_page_id',
				),
				array(
					'type'  => 'select_page',
					'title' => __( 'Login Page', 'wp-events-manager' ),
					'desc'  => __( 'This controls which the login page', 'wp-events-manager' ),
					'id'    => $prefix . 'login_page_id',
				),
				array(
					'type'  => 'select_page',
					'title' => __( 'Forgot Password', 'wp-events-manager' ),
					'desc'  => __( 'This controls which the forgot password page', 'wp-events-manager' ),
					'id'    => $prefix . 'forgot_password_page_id',
				),
				array(
					'type'  => 'select_page',
					'title' => __( 'Reset Password', 'wp-events-manager' ),
					'desc'  => __( 'This controls which the reset password page', 'wp-events-manager' ),
					'id'    => $prefix . 'reset_password_page_id',
				),
				array(
					'type'  => 'select_page',
					'title' => __( 'My Account', 'wp-events-manager' ),
					'desc'  => __( 'This controls which the user account page', 'wp-events-manager' ),
					'id'    => $prefix . 'account_page_id',
				),
				array(
					'type' => 'section_end',
					'id'   => 'pages_settings',
				),
			)
		);
	}
}
