<?php
/**
 * Booking post query filter.
 *
 * @package WPEMS/Filters
 */

namespace WPEMS\Filters;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingFilter
 */
class BookingFilter extends PostFilter {
	/**
	 * Booking post type.
	 *
	 * @var string
	 */
	public $post_type = 'event_auth_book';
}
