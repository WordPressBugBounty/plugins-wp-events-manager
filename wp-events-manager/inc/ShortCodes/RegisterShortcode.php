<?php
/**
 * User register shortcode.
 *
 * @package WPEMS/ShortCodes
 */

namespace WPEMS\ShortCodes;

defined( 'ABSPATH' ) || exit;

/**
 * Class RegisterShortcode
 */
class RegisterShortcode extends AbstractShortcode {
	/**
	 * Shortcode name.
	 *
	 * @var string
	 */
	protected $shortcode_name = 'register';

	/**
	 * Render register shortcode.
	 *
	 * @param string|array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		if ( ! wpems_get_page_id( 'register' ) ) {
			return '';
		}

		if ( ! get_option( 'users_can_register' ) ) {
			return \WPEMS_Shortcodes::render( 'user-register', 'user-cannot-register.php' );
		}

		$registered = self::request_value( 'registered', 'email' );
		if ( $registered ) {
			$user = get_user_by( 'email', $registered );
			if ( $user && $user->ID ) {
				wp_new_user_notification( $user->ID, null, 'user' );

				return \WPEMS_Shortcodes::render( 'user-register', 'register-completed.php' );
			}

			return \WPEMS_Shortcodes::render( 'user-register', 'register-error.php' );
		}

		if ( ! is_user_logged_in() ) {
			return \WPEMS_Shortcodes::render( 'user-register', 'form-register.php' );
		}

		return '';
	}
}
