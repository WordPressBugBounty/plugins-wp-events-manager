<?php
/**
 * Reset password shortcode.
 *
 * @package WPEMS/ShortCodes
 */

namespace WPEMS\ShortCodes;

defined( 'ABSPATH' ) || exit;

/**
 * Class ResetPasswordShortcode
 */
class ResetPasswordShortcode extends AbstractShortcode {
	/**
	 * Shortcode name.
	 *
	 * @var string
	 */
	protected $shortcode_name = 'reset_password';

	/**
	 * Render reset password shortcode.
	 *
	 * @param string|array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		if ( ! wpems_get_page_id( 'reset_password' ) ) {
			return '';
		}

		$attrs = wp_parse_args(
			(array) $attrs,
			array(
				'key'   => self::request_value( 'key' ),
				'login' => self::request_value( 'login' ),
			)
		);

		$attrs = wp_parse_args(
			$attrs,
			array(
				'user_login'  => '',
				'redirect_to' => '',
				'checkemail'  => 'confirm' === self::request_value( 'checkemail' ),
			)
		);

		if ( $attrs['checkemail'] ) {
			wpems_add_notice( 'success', __( 'Check your email for a link to reset your password.', 'wp-events-manager' ) );
		}

		return \WPEMS_Shortcodes::render( 'reset-password', 'reset-password.php', array( 'atts' => $attrs ) );
	}
}
