<?php
/**
 * Event query filter data object.
 *
 * @package WPEMS/Filters
 * @version 1.0.1
 * @since   2.3.0
 */

namespace WPEMS\Filters;

defined( 'ABSPATH' ) || exit;

/**
 * Class EventFilter
 */
class EventFilter extends PostFilter {
	/**
	 * Post type.
	 *
	 * @var string
	 */
	public $post_type = 'tp_event';

	/**
	 * Event meta status.
	 *
	 * @var string
	 */
	public $status = '';

	/**
	 * Date range start (Y-m-d).
	 *
	 * @var string
	 */
	public $date_from = '';

	/**
	 * Date range end (Y-m-d).
	 *
	 * @var string
	 */
	public $date_to = '';

	/**
	 * Order by field.
	 *
	 * @var string
	 */
	public $order_by = 'post_date';

	/**
	 * Sort direction.
	 *
	 * @var string
	 */
	public $order = 'DESC';

	/**
	 * Event ID — used by booking-centric queries.
	 *
	 * @var int
	 */
	public $event_id = 0;

	/**
	 * User ID — optional scope for booking queries.
	 *
	 * @var int|null
	 */
	public $user_id = null;

	/**
	 * Booking post status scope. When non-empty, only bookings
	 * with this status are included in aggregate queries.
	 *
	 * @var string
	 */
	public $booking_status = '';
}
