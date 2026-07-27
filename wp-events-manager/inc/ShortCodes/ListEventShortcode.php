<?php
/**
 * Event list shortcode.
 *
 * @package WPEMS/ShortCodes
 */

namespace WPEMS\ShortCodes;

defined( 'ABSPATH' ) || exit;

/**
 * Class ListEventShortcode
 */
class ListEventShortcode extends AbstractShortcode {
	/**
	 * Shortcode name.
	 *
	 * @var string
	 */
	protected $shortcode_name = 'list_event';

	/**
	 * Render event list shortcode.
	 *
	 * @param string|array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		return \WPEMS_Shortcodes::list_event( $attrs );
	}
}
