<?php
/**
 * Event post model.
 *
 * @package WPEMS/Models
 */

namespace WPEMS\Models;

use WPEMS\Databases\EventDB;
use WPEMS\Filters\EventFilter;

defined( 'ABSPATH' ) || exit;

class EventPostModel extends PostModel {

	/**
	 * Event post type.
	 *
	 * @var string
	 */
	public $post_type = 'tp_event';

	/**
	 * Instance cache.
	 *
	 * @var array<int, EventPostModel>
	 */
	private static $instances = array();

	/**
	 * Find event by ID.
	 *
	 * @param int $id Event ID.
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

		$filter     = new EventFilter();
		$filter->ID = $id;

		$event = static::get_item_model_from_db( $filter );
		if ( ! $event instanceof self ) {
			return false;
		}

		self::$instances[ $id ] = $event;

		return self::$instances[ $id ];
	}

	/**
	 * Get event title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->get_the_title();
	}

	/**
	 * Get event meta by short or full key.
	 *
	 * @param string $key     Meta key, with or without tp_event_ prefix.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 */
	public function get_meta( string $key, $default_value = false ) {
		$key      = sanitize_key( $key );
		$meta_key = strpos( $key, 'tp_event_' ) === 0 ? $key : 'tp_event_' . $key;

		return $this->get_meta_value_by_key( $meta_key, $default_value );
	}

	/**
	 * Whether event is free.
	 *
	 * @return bool
	 */
	public function is_free(): bool {
		return $this->get_price() === 0.0;
	}

	/**
	 * Get event price.
	 *
	 * @return float
	 */
	public function get_price(): float {
		return (float) $this->get_meta( 'price', 0 );
	}

	/**
	 * Get total event quantity.
	 *
	 * @return int
	 */
	public function get_quantity(): int {
		return (int) $this->get_meta( 'qty', 0 );
	}

	/**
	 * Get event status meta.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return (string) $this->get_meta( 'status', '' );
	}

	/**
	 * Get event start date.
	 *
	 * @return string
	 */
	public function get_date_start(): string {
		return (string) $this->get_meta( 'date_start', '' );
	}

	/**
	 * Get event start time.
	 *
	 * @return string
	 */
	public function get_time_start(): string {
		return (string) $this->get_meta( 'time_start', '' );
	}

	/**
	 * Get event end date.
	 *
	 * @return string
	 */
	public function get_date_end(): string {
		return (string) $this->get_meta( 'date_end', '' );
	}

	/**
	 * Get event end time.
	 *
	 * @return string
	 */
	public function get_time_end(): string {
		return (string) $this->get_meta( 'time_end', '' );
	}

	/**
	 * Get event location.
	 *
	 * @return string
	 */
	public function get_location(): string {
		return (string) $this->get_meta( 'location', '' );
	}

	/**
	 * Get registered bookings.
	 *
	 * @return array
	 */
	public function load_registered(): array {
		$filter           = new EventFilter();
		$filter->event_id = $this->get_id();

		return EventDB::getInstance()->get_registered_bookings( $filter );
	}

	/**
	 * Get available slots.
	 *
	 * @return int
	 */
	public function get_slot_available(): int {
		$quantity = $this->get_quantity();

		return (int) apply_filters( 'event_slot_available', $quantity - $this->booked_quantity(), $this->get_id() );
	}

	/**
	 * Get registered booking count.
	 *
	 * @return int
	 */
	public function get_registered_time(): int {
		return (int) apply_filters( 'event_registered_time', count( $this->load_registered() ), $this->get_id() );
	}

	/**
	 * Get booked quantity.
	 *
	 * @param int|null $user_id Optional user ID.
	 *
	 * @return int
	 */
	public function booked_quantity( ?int $user_id = null ): int {
		$filter           = new EventFilter();
		$filter->event_id = $this->get_id();
		$filter->user_id  = $user_id;

		return EventDB::getInstance()->get_booked_quantity( $filter );
	}
}
