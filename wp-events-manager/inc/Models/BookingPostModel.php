<?php
/**
 * Booking post model.
 *
 * @package WPEMS/Models
 */

namespace WPEMS\Models;

use Exception;
use WPEMS\Filters\BookingFilter;

defined( 'ABSPATH' ) || exit;

class BookingPostModel extends PostModel {

	/**
	 * Booking post type.
	 *
	 * @var string
	 */
	public $post_type = 'event_auth_book';

	/**
	 * Instance cache.
	 *
	 * @var array<int, BookingPostModel>
	 */
	private static $instances = array();

	/**
	 * Find booking by ID.
	 *
	 * @param int $id Booking ID.
	 *
	 * @return false|self
	 */
	public static function find( int $id ) {
		$id = absint( $id );
		if ( ! $id ) {
			return false;
		}

		if ( isset( self::$instances[ $id ] ) ) {
			return self::$instances[ $id ];
		}

		$filter     = new BookingFilter();
		$filter->ID = $id;

		$booking = static::get_item_model_from_db( $filter );
		if ( ! $booking instanceof self ) {
			return false;
		}

		self::$instances[ $id ] = $booking;

		return self::$instances[ $id ];
	}

	/**
	 * Get booking meta value by short key.
	 *
	 * @param string $key Meta key without ea_booking_ prefix.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 */
	public function get_meta( string $key, $default_value = false ) {
		$key      = sanitize_key( $key );
		$meta_key = strpos( $key, 'ea_booking_' ) === 0 ? $key : 'ea_booking_' . $key;

		return $this->get_meta_value_by_key( $meta_key, $default_value );
	}

	/**
	 * Get event ID.
	 *
	 * @return int
	 */
	public function get_event_id(): int {
		return absint( $this->get_meta( 'event_id', 0 ) );
	}

	/**
	 * Get booking user ID.
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return absint( $this->get_meta( 'user_id', 0 ) );
	}

	/**
	 * Get booked quantity.
	 *
	 * @return int
	 */
	public function get_quantity(): int {
		return absint( $this->get_meta( 'qty', 0 ) );
	}

	/**
	 * Get booked quantity.
	 *
	 * @return int
	 */
	public function get_qty(): int {
		return $this->get_quantity();
	}

	/**
	 * Get booking price.
	 *
	 * @return float
	 */
	public function get_price(): float {
		return (float) $this->get_meta( 'price', 0 );
	}

	/**
	 * Get booking currency.
	 *
	 * @return string
	 */
	public function get_currency(): string {
		return (string) $this->get_meta( 'currency', '' );
	}

	/**
	 * Get payment method ID.
	 *
	 * @return string
	 */
	public function get_payment_id(): string {
		return (string) $this->get_meta( 'payment_id', '' );
	}

	/**
	 * Create booking.
	 *
	 * @param array  $args    Booking args.
	 * @param string $payment Payment method.
	 *
	 * @return int|\WP_Error
	 * @throws Exception If save fails.
	 */
	public function create_booking( array $args = array(), string $payment = '' ) {
		$user = wp_get_current_user();
		if ( ! array_key_exists( 'price', $args ) && array_key_exists( 'cost', $args ) ) {
			$args['price'] = $args['cost'];
		}

		$args = wp_parse_args(
			$args,
			array(
				'user_id'    => isset( $user->ID ) ? absint( $user->ID ) : 0,
				'event_id'   => 0,
				'qty'        => 1,
				'price'      => 0,
				'currency'   => '',
				'payment_id' => $payment,
			)
		);

		$args = array(
			'user_id'    => absint( $args['user_id'] ),
			'event_id'   => absint( $args['event_id'] ),
			'qty'        => max( 1, absint( $args['qty'] ) ),
			'price'      => (float) $args['price'],
			'currency'   => sanitize_text_field( $args['currency'] ),
			'payment_id' => sanitize_key( $args['payment_id'] ),
		);

		$user_name = isset( $user->user_nicename ) ? sanitize_user( $user->user_nicename, true ) : '';

		$this->post_title   = sprintf( __( '%1$s booking event %2$s', 'wp-events-manager' ), $user_name, $args['event_id'] );
		$this->post_content = sprintf( __( '%1$s booking event %2$s with %3$s slot', 'wp-events-manager' ), $user_name, $args['event_id'], $args['qty'] );
		$this->post_excerpt = $this->post_content;
		$this->post_status  = 'ea-pending';
		$this->post_type    = 'event_auth_book';

		foreach ( $args as $key => $value ) {
			$this->meta_data->{ 'ea_booking_' . $key } = $value;
		}

		$this->save( true );
		self::$instances[ $this->get_id() ] = $this;

		do_action( 'tp_event_create_new_booking', $this->get_id(), $args );

		return $this->get_id();
	}

	/**
	 * Update booking status.
	 *
	 * @param string $status Booking status, with or without ea- prefix.
	 *
	 * @return bool
	 * @throws Exception If booking does not exist.
	 */
	public function update_status( string $status = 'ea-completed' ): bool {
		if ( ! $this->get_id() || $this->post_type !== 'event_auth_book' ) {
			throw new Exception( sprintf( __( 'Booking ID #%s is not exists.', 'wp-events-manager' ), $this->get_id() ) );
		}

		$status = sanitize_key( $status );
		if ( strpos( $status, 'ea-' ) !== 0 ) {
			$status = 'ea-' . $status;
		}

		$allowed_statuses = array( 'ea-cancelled', 'ea-pending', 'ea-processing', 'ea-completed' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			return false;
		}

		$old_status = get_post_status( $this->get_id() );
		$id         = wp_update_post(
			array(
				'ID'          => $this->get_id(),
				'post_status' => $status,
			)
		);

		if ( ! $id || is_wp_error( $id ) ) {
			return false;
		}

		$this->post_status = $status;

		do_action( 'tp_event_updated_status', $id, $old_status, $status );
		do_action( 'tp_event_updated_status_' . $old_status . '_' . $status, $id, $old_status, $status );

		return true;
	}
}
