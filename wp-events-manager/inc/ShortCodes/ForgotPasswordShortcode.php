<?php
/**
 * Forgot password shortcode.
 *
 * @package WPEMS/ShortCodes
 */

namespace WPEMS\ShortCodes;

defined( 'ABSPATH' ) || exit;

/**
 * Class ForgotPasswordShortcode
 */
class ForgotPasswordShortcode extends AbstractShortcode {
	/**
	 * Shortcode name.
	 *
	 * @var string
	 */
	protected $shortcode_name = 'forgot_password';

	/**
	 * Render forgot password shortcode.
	 *
	 * @param string|array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		if ( ! wpems_get_page_id( 'forgot_password' ) ) {
			return '';
		}

		$checkemail = self::request_value( 'checkemail' );
		if ( 'confirm' === $checkemail ) {
			wpems_add_notice( 'success', __( 'Check your email for a link to reset your password.', 'wp-events-manager' ) );

			return '';
		}

		return \WPEMS_Shortcodes::render( 'forgot-password', 'forgot-password.php' );
	}
}
