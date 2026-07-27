<?php
/**
 * Admin metaboxes.
 *
 * @package WPEMS\Admin
 */

namespace WPEMS\Admin;

use WPEMS\Admin\Metaboxes\Booking;
use WPEMS\Admin\Metaboxes\Event;

defined( 'ABSPATH' ) || exit;

/**
 * WP Events Manager admin metabox registration.
 */
class Metaboxes {

	/**
	 * Whether hooks have been registered.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		add_action( 'add_meta_boxes', array( static::class, 'add_meta_boxes' ), 0 );
		add_action( 'save_post', array( static::class, 'save_post_meta' ) );
		add_action( 'admin_notices', array( static::class, 'print_errors' ) );

		/**
		 * Save post meta.
		 */
		add_action( 'tp_event_process_update_tp_event_meta', array( Event::class, 'save' ), 10, 2 );
		add_action( 'tp_event_process_update_event_auth_book_meta', array( Booking::class, 'save' ), 10, 2 );
	}

	/**
	 * Add meta boxes.
	 *
	 * @return void
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'event-settings-metabox',
			__( 'Event Settings', 'wp-events-manager' ),
			array( Event::class, 'render' ),
			'tp_event',
			'normal',
			'high'
		);
		add_meta_box(
			'booking-information-metabox',
			__( 'Booking Information', 'wp-events-manager' ),
			array( Booking::class, 'render' ),
			'event_auth_book',
			'normal',
			'default'
		);
		add_meta_box(
			'booking-status-side',
			__( 'Booking Actions', 'wp-events-manager' ),
			array( Booking::class, 'side' ),
			'event_auth_book',
			'side',
			'high'
		);
	}

	/**
	 * Save post meta.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public static function save_post_meta( $post_id ) {
		if ( empty( $_POST ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, array( 'tp_event', 'event_auth_book' ), true ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$event_nonce   = ! empty( $_POST['event-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['event-nonce'] ) ) : '';
		$booking_nonce = ! empty( $_POST['event-booking-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['event-booking-nonce'] ) ) : '';

		if ( 'tp_event' === $post_type && ( ! $event_nonce || ! wp_verify_nonce( $event_nonce, 'event_nonce' ) ) ) {
			return false;
		} elseif ( 'event_auth_book' === $post_type && ( ! $booking_nonce || ! wp_verify_nonce( $booking_nonce, 'event_booking_nonce' ) ) ) {
			return false;
		}

		do_action( 'tp_event_process_update_' . $post_type . '_meta', $post_id, wp_unslash( $_POST ) );
	}

	/**
	 * Add error message save post meta.
	 *
	 * @param string $message Error message.
	 *
	 * @return void
	 */
	public static function add_error( $message = '' ) {
		$error   = get_option( 'tp_event_meta_box_error_messages', array() );
		$error[] = $message;
		update_option( 'tp_event_meta_box_error_messages', $error );
	}

	/**
	 * Print notices error save post meta.
	 *
	 * @return void
	 */
	public static function print_errors() {
		$errors = get_option( 'tp_event_meta_box_error_messages' );
		if ( ! $errors ) {
			return;
		}
		echo '<div id="event_error" class="error notice is-dismissible">';

		foreach ( $errors as $error ) {
			echo '<p>' . wp_kses_post( $error ) . '</p>';
		}

		echo '</div>';
		delete_option( 'tp_event_meta_box_error_messages' );
	}
}

if ( ! \class_exists( 'WPEMS_Admin_Metaboxes', false ) ) {
	\class_alias( Metaboxes::class, 'WPEMS_Admin_Metaboxes' );
}
