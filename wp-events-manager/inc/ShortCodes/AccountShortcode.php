<?php
/**
 * User account shortcode.
 *
 * @package WPEMS/ShortCodes
 */

namespace WPEMS\ShortCodes;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountShortcode
 */
class AccountShortcode extends AbstractShortcode {
	/**
	 * Shortcode name.
	 *
	 * @var string
	 */
	protected $shortcode_name = 'account';

	/**
	 * Render account shortcode.
	 *
	 * @param string|array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		return \WPEMS_Shortcodes::account( $attrs );
	}
}
