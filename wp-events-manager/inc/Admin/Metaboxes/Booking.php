<?php
/**
 * Booking metabox.
 *
 * @package WPEMS\Admin\Metaboxes
 */

namespace WPEMS\Admin\Metaboxes;

use WPEMS\Models\BookingPostModel;

defined( 'ABSPATH' ) || exit;

/**
 * WP Events Manager booking metabox.
 */
class Booking {

	/**
	 * Save booking metabox data.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $posted  Posted data.
	 *
	 * @return void
	 */
	public static function save( $post_id, $posted ) {
		if ( ! empty( $posted['booking-status'] ) ) {
			remove_action( 'tp_event_process_update_event_auth_book_meta', array( static::class, 'save' ), 10 );
			$booking = BookingPostModel::find( absint( $post_id ) );
			if ( ! $booking ) {
				return;
			}

			$status           = sanitize_key( $posted['booking-status'] );
			$allowed_statuses = array_keys( wpems_get_payment_status() );
			if ( in_array( $status, $allowed_statuses, true ) ) {
				$booking->update_status( $status );
			}
			add_action( 'tp_event_process_update_event_auth_book_meta', array( static::class, 'save' ), 10, 2 );
		}
		if ( ! empty( $posted['booking-notes'] ) ) {
			update_post_meta( $post_id, 'ea_booking_note', sanitize_textarea_field( $posted['booking-notes'] ) );
		}
	}

	/**
	 * Render booking details metabox.
	 *
	 * @return void
	 */
	public static function render() {
		wpems_get_admin_template( 'metaboxes/booking-details.php' );
	}

	/**
	 * Render booking actions metabox.
	 *
	 * @return void
	 */
	public static function side() {
		wpems_get_admin_template( 'metaboxes/booking-actions.php' );
	}
}

if ( ! \class_exists( 'WPEMS_Admin_Metabox_Booking', false ) ) {
	\class_alias( Booking::class, 'WPEMS_Admin_Metabox_Booking' );
}
