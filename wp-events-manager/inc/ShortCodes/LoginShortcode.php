<?php
/**
 * User login shortcode.
 *
 * @package WPEMS/ShortCodes
 */

namespace WPEMS\ShortCodes;

defined( 'ABSPATH' ) || exit;

/**
 * Class LoginShortcode
 */
class LoginShortcode extends AbstractShortcode {
	/**
	 * Shortcode name.
	 *
	 * @var string
	 */
	protected $shortcode_name = 'login';

	/**
	 * Render login shortcode.
	 *
	 * @param string|array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		return \WPEMS_Shortcodes::login( $attrs );
	}
}
