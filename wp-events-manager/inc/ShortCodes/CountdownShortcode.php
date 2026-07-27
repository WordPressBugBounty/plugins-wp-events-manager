<?php
/**
 * Event countdown shortcode.
 *
 * @package WPEMS/ShortCodes
 */

namespace WPEMS\ShortCodes;

defined( 'ABSPATH' ) || exit;

/**
 * Class CountdownShortcode
 */
class CountdownShortcode extends AbstractShortcode {
	/**
	 * Shortcode name.
	 *
	 * @var string
	 */
	protected $shortcode_name = 'countdown';

	/**
	 * Render countdown shortcode.
	 *
	 * @param string|array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		return \WPEMS_Shortcodes::countdown( $attrs );
	}
}
