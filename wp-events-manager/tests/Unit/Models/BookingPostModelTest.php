<?php
/**
 * Booking model tests.
 *
 * @package WPEMS\Tests\Unit\Models
 */

namespace WPEMS\Tests\Unit\Models;

use Brain\Monkey\Functions;
use Mockery;
use stdClass;
use WPEMS\Databases\PostDB;
use WPEMS\Models\BookingPostModel;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test booking post model behavior.
 */
class BookingPostModelTest extends TestCase {

	/**
	 * Reset booking cache.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( BookingPostModel::class, 'instances', array() );
		$this->resetStaticProperty( PostDB::class, 'instance', null );
	}

	/**
	 * It finds booking posts.
	 *
	 * @return void
	 */
	public function test_find_returns_booking_model(): void {
		global $wpdb;

		$wpdb = $this->makePostLookupWpdb( $this->makePostRow( 8, 'event_auth_book', 'Booking' ) );

		$booking = BookingPostModel::find( 8 );

		$this->assertInstanceOf( BookingPostModel::class, $booking );
		$this->assertSame( 8, $booking->get_id() );
		$this->assertStringContainsString( "AND p.post_type = 'event_auth_book'", $wpdb->last_query );
		$this->assertStringContainsString( 'AND p.ID = 8', $wpdb->last_query );
	}

	/**
	 * It returns false when filtered booking lookup has no row.
	 *
	 * @return void
	 */
	public function test_find_returns_false_when_booking_lookup_has_no_row(): void {
		global $wpdb;

		$wpdb = $this->makePostLookupWpdb( null );

		$this->assertFalse( BookingPostModel::find( 8 ) );
		$this->assertStringContainsString( "AND p.post_type = 'event_auth_book'", $wpdb->last_query );
		$this->assertStringContainsString( 'AND p.ID = 8', $wpdb->last_query );
	}

	/**
	 * It creates a booking post with normalized metadata.
	 *
	 * @return void
	 */
	public function test_create_booking_returns_id(): void {
		$booking = new BookingPostModel();

		Functions\expect( 'wp_get_current_user' )
			->once()
			->andReturn(
				(object) array(
					'ID'            => 44,
					'user_nicename' => 'demo_user',
				)
			);

		Functions\expect( 'wp_insert_post' )
			->once()
			->andReturnUsing(
				function ( $data, $wp_error ) {
					$this->assertTrue( $wp_error );
					$this->assertSame( 'event_auth_book', $data['post_type'] );
					$this->assertSame( 'ea-pending', $data['post_status'] );
					$this->assertSame( 44, $data['meta_input']['ea_booking_user_id'] );
					$this->assertSame( 77, $data['meta_input']['ea_booking_event_id'] );
					$this->assertSame( 2, $data['meta_input']['ea_booking_qty'] );
					$this->assertSame( 31.0, $data['meta_input']['ea_booking_price'] );
					$this->assertSame( 'USD', $data['meta_input']['ea_booking_currency'] );
					$this->assertSame( 'paypal', $data['meta_input']['ea_booking_payment_id'] );
					$this->assertArrayNotHasKey( 'ea_booking_cost', $data['meta_input'] );

					return 501;
				}
			);

		Functions\expect( 'get_post' )
			->once()
			->with( 501 )
			->andReturn( $this->makePost( 501, 'event_auth_book', 'Saved booking', 'ea-pending' ) );

		Functions\expect( 'do_action' )
			->once()
			->with( 'tp_event_create_new_booking', 501, Mockery::type( 'array' ) );

		$id = $booking->create_booking(
			array(
				'event_id' => 77,
				'qty'      => 2,
				'price'    => 31,
				'currency' => 'USD',
			),
			'paypal'
		);

		$this->assertSame( 501, $id );
	}

	/**
	 * It maps legacy cost input to price metadata.
	 *
	 * @return void
	 */
	public function test_create_booking_maps_legacy_cost_to_price(): void {
		$booking = new BookingPostModel();

		Functions\expect( 'wp_get_current_user' )
			->once()
			->andReturn(
				(object) array(
					'ID'            => 44,
					'user_nicename' => 'demo_user',
				)
			);

		Functions\expect( 'wp_insert_post' )
			->once()
			->andReturnUsing(
				function ( $data ) {
					$this->assertSame( 15.5, $data['meta_input']['ea_booking_price'] );
					$this->assertSame( '', $data['meta_input']['ea_booking_currency'] );
					$this->assertArrayNotHasKey( 'ea_booking_cost', $data['meta_input'] );

					return 502;
				}
			);

		Functions\expect( 'get_post' )
			->once()
			->with( 502 )
			->andReturn( $this->makePost( 502, 'event_auth_book', 'Saved booking', 'ea-pending' ) );

		Functions\expect( 'do_action' )
			->once()
			->with( 'tp_event_create_new_booking', 502, Mockery::type( 'array' ) );

		$id = $booking->create_booking(
			array(
				'event_id' => 77,
				'qty'      => 2,
				'cost'     => 15.5,
			)
		);

		$this->assertSame( 502, $id );
	}

	/**
	 * It exposes legacy booking metadata through typed getters.
	 *
	 * @return void
	 */
	public function test_typed_getters_return_legacy_booking_meta(): void {
		$booking                   = new BookingPostModel( $this->makePost( 11, 'event_auth_book' ) );
		$booking->meta_data        = new stdClass();
		$booking->is_got_meta_data = true;

		$booking->meta_data->ea_booking_event_id   = '77';
		$booking->meta_data->ea_booking_user_id    = '44';
		$booking->meta_data->ea_booking_qty        = '3';
		$booking->meta_data->ea_booking_price      = '30.25';
		$booking->meta_data->ea_booking_currency   = 'USD';
		$booking->meta_data->ea_booking_payment_id = 'paypal';

		$this->assertSame( 77, $booking->get_event_id() );
		$this->assertSame( 44, $booking->get_user_id() );
		$this->assertSame( 3, $booking->get_quantity() );
		$this->assertSame( 3, $booking->get_qty() );
		$this->assertSame( 30.25, $booking->get_price() );
		$this->assertSame( 'USD', $booking->get_currency() );
		$this->assertSame( 'paypal', $booking->get_payment_id() );
		$this->assertSame( 'paypal', $booking->get_meta( 'payment_id' ) );
	}

	/**
	 * It accepts statuses with or without the ea- prefix.
	 *
	 * @return void
	 */
	public function test_update_status_accepts_unprefixed_status(): void {
		$booking = new BookingPostModel( $this->makePost( 9, 'event_auth_book', 'Booking', 'ea-pending' ) );

		Functions\expect( 'get_post_status' )
			->once()
			->with( 9 )
			->andReturn( 'ea-pending' );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				array(
					'ID'          => 9,
					'post_status' => 'ea-completed',
				)
			)
			->andReturn( 9 );

		Functions\expect( 'do_action' )
			->twice()
			->withAnyArgs();

		$this->assertTrue( $booking->update_status( 'completed' ) );
		$this->assertSame( 'ea-completed', $booking->post_status );
	}

	/**
	 * It rejects unsupported booking statuses.
	 *
	 * @return void
	 */
	public function test_update_status_rejects_unknown_status(): void {
		$booking = new BookingPostModel( $this->makePost( 10, 'event_auth_book', 'Booking', 'ea-pending' ) );

		$this->assertFalse( $booking->update_status( 'unknown' ) );
	}
}
