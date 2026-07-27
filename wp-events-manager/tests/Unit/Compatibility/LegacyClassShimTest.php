<?php
/**
 * Legacy class shim tests.
 *
 * @package WPEMS\Tests\Unit\Compatibility
 */

namespace WPEMS\Tests\Unit\Compatibility;

use Brain\Monkey\Functions;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test legacy class compatibility wrappers.
 */
class LegacyClassShimTest extends TestCase {

	/**
	 * Load legacy classes and reset static caches.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		require_once WPEMS_INC . 'class-wpems-event.php';
		require_once WPEMS_INC . 'class-wpems-booking.php';

		$this->resetStaticProperty( \WPEMS_Event::class, 'instance', null );
		$this->resetStaticProperty( \WPEMS_Booking::class, 'instance', null );
	}

	/**
	 * Event shim uses legacy post and meta reads.
	 *
	 * @return void
	 */
	public function test_event_shim_uses_legacy_post_and_meta_reads(): void {
		Functions\when( 'get_post_type' )->alias(
			function ( $post_id ) {
				$this->assertSame( 22, $post_id );

				return 'tp_event';
			}
		);
		Functions\when( 'get_post' )->alias(
			function ( $post_id ) {
				$this->assertSame( 22, $post_id );

				return $this->makePost( 22, 'tp_event', 'Legacy event' );
			}
		);

		Functions\expect( 'get_the_title' )
			->once()
			->with( 22 )
			->andReturn( 'Legacy event' );

		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $key, $single = true ) {
				$this->assertSame( 22, $post_id );
				$this->assertSame( 'tp_event_price', $key );
				$this->assertTrue( $single );

				return '9.5';
			}
		);

		$event = \WPEMS_Event::instance( 22 );

		$this->assertSame( 22, $event->ID );
		$this->assertSame( 'Legacy event', $event->get_title() );
		$this->assertSame( 9.5, $event->get_price() );
		$this->assertSame( '9.5', $event->price );
		$this->assertFalse( $event->is_free() );
	}

	/**
	 * Booking shim uses legacy meta reads and status updates.
	 *
	 * @return void
	 */
	public function test_booking_shim_uses_legacy_meta_reads_and_status_updates(): void {
		Functions\when( 'get_post_type' )->alias(
			function ( $post_id ) {
				$this->assertSame( 33, $post_id );

				return 'event_auth_book';
			}
		);
		Functions\when( 'get_post' )->alias(
			function ( $post_id ) {
				$this->assertSame( 33, $post_id );

				return $this->makePost( 33, 'event_auth_book', 'Legacy booking', 'ea-pending' );
			}
		);

		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $key ) {
				$this->assertSame( 33, $post_id );
				$meta = array(
					'ea_booking_user_id'    => '44',
					'ea_booking_price'      => '12.25',
					'ea_booking_payment_id' => 'paypal',
				);

				return $meta[ $key ] ?? '';
			}
		);

		Functions\expect( 'get_post_status' )
			->once()
			->with( 33 )
			->andReturn( 'ea-pending' );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				array(
					'ID'          => 33,
					'post_status' => 'ea-completed',
				)
			)
			->andReturn( 33 );

		Functions\expect( 'do_action' )
			->twice()
			->withAnyArgs();

		$booking = \WPEMS_Booking::instance( 33 );

		$this->assertSame( 33, $booking->ID );
		$this->assertSame( '44', $booking->user_id );
		$this->assertSame( '12.25', $booking->price );
		$this->assertSame( 'paypal', $booking->payment_id );
		$this->assertNull( $booking->update_status( 'completed' ) );
	}

	/**
	 * Booking shim keeps legacy create_booking available for old integrations.
	 *
	 * @return void
	 */
	public function test_booking_shim_create_booking_uses_legacy_insert_and_meta(): void {
		$booking    = new \WPEMS_Booking();
		$saved_meta = array();

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
					$this->assertSame( 'event_auth_book', $data['post_type'] );
					$this->assertSame( 'ea-pending', $data['post_status'] );
					$this->assertStringContainsString( '77', $data['post_title'] );

					return 503;
				}
			);

		Functions\when( 'update_post_meta' )->alias(
			function ( $post_id, $key, $value ) use ( &$saved_meta ) {
				$this->assertSame( 503, $post_id );
				$saved_meta[ $key ] = $value;

				return true;
			}
		);

		Functions\expect( 'do_action' )
			->once()
			->with( 'tp_event_create_new_booking', 503, \Mockery::type( 'array' ) );

		$id = $booking->create_booking(
			array(
				'event_id'   => 77,
				'qty'        => 1,
				'price'      => 20,
				'currency'   => 'USD',
				'payment_id' => 'paypal',
			)
		);

		$this->assertSame( 503, $id );
		$this->assertSame( 77, $saved_meta['ea_booking_event_id'] );
		$this->assertSame( 20, $saved_meta['ea_booking_price'] );
		$this->assertSame( 'paypal', $saved_meta['ea_booking_payment_id'] );
	}

	/**
	 * Targeted legacy files do not reference new namespaces.
	 *
	 * @return void
	 */
	public function test_targeted_legacy_files_do_not_reference_new_namespaces(): void {
		$files = array(
			WPEMS_INC . 'class-wpems-event.php',
			WPEMS_INC . 'class-wpems-booking.php',
			WPEMS_INC . 'gateways/paypal/class-wpems-payment-gateway-paypal.php',
			WPEMS_INC . 'class-wpems-payment-gateways.php',
		);

		foreach ( $files as $file ) {
			$contents = file_get_contents( $file );

			$this->assertStringNotContainsString( 'WPEMS\\Models', $contents, $file );
			$this->assertStringNotContainsString( 'WPEMS\\Gateways', $contents, $file );
		}
	}
}
