<?php
/**
 * PayPal gateway tests.
 *
 * @package WPEMS\Tests\Unit\Gateways
 */

namespace WPEMS\Tests\Unit\Gateways;

use Brain\Monkey\Functions;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test legacy PayPal gateway.
 */
class PaypalGatewayTest extends TestCase {

	/**
	 * Reset booking cache and load legacy classes.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		require_once WPEMS_INC . 'class-wpems-booking.php';
		require_once WPEMS_INC . 'abstracts/class-wpems-abstract-payment-gateway.php';
		require_once WPEMS_INC . 'gateways/paypal/class-wpems-payment-gateway-paypal.php';

		$this->resetStaticProperty( \WPEMS_Booking::class, 'instance', null );
	}

	/**
	 * It is always available when registered.
	 *
	 * @return void
	 */
	public function test_is_available_true(): void {
		$gateway = $this->makeGateway();

		$this->assertTrue( $gateway->is_available() );
	}

	/**
	 * It reads enabled state from PayPal settings.
	 *
	 * @return void
	 */
	public function test_is_enable_reads_settings(): void {
		$gateway = $this->makeGateway(
			array(
				'paypal_email'  => 'merchant@example.com',
				'paypal_enable' => 'yes',
			)
		);

		$this->assertTrue( $gateway->is_enable() );
	}

	/**
	 * It builds a PayPal checkout URL from booking data.
	 *
	 * @return void
	 */
	public function test_checkout_url_contains_booking_data(): void {
		$gateway = $this->makeGateway(
			array(
				'paypal_email'  => 'merchant@example.com',
				'paypal_enable' => 'yes',
			)
		);

		$this->mockLegacyBookingPost( 70 );

		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $key, $single = true ) {
				$this->assertSame( 70, $post_id );
				$this->assertTrue( $single );
				$meta = array(
					'ea_booking_user_id'  => 99,
					'ea_booking_price'    => '20.5',
					'ea_booking_currency' => 'USD',
				);

				return $meta[ $key ] ?? '';
			}
		);

		Functions\expect( 'get_userdata' )
			->once()
			->with( 99 )
			->andReturn( (object) array( 'user_email' => 'buyer@example.com' ) );

		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce-1' );
		Functions\when( 'wpems_get_currency' )->justReturn( 'USD' );
		Functions\when( 'home_url' )->justReturn( 'https://example.test' );
		Functions\when( 'wpems_account_url' )->justReturn( 'https://example.test/account' );
		Functions\when( 'wpems_format_price' )->alias(
			function ( $price, $currency ) {
				return $currency . ' ' . number_format( $price, 2 );
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			function ( $args, $url ) {
				return $url . '?' . http_build_query( $args, '', '&' );
			}
		);

		$url = $gateway->checkout_url( 70 );

		$this->assertStringStartsWith( 'https://www.paypal.com/cgi-bin/webscr?', $url );

		parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $params );

		$this->assertSame( '_xclick', $params['cmd'] );
		$this->assertSame( 'merchant@example.com', $params['business'] );
		$this->assertSame( '20.5', $params['amount'] );
		$this->assertSame( 'USD', $params['currency_code'] );
		$this->assertSame( 'buyer@example.com', $params['email'] );
		$this->assertSame(
			array(
				'booking_id' => 70,
				'user_id'    => 99,
			),
			json_decode( $params['custom'], true )
		);
	}

	/**
	 * It completes a booking only when the PayPal IPN matches local payment data.
	 *
	 * @return void
	 */
	public function test_payment_validation_completes_matching_ipn(): void {
		$gateway = $this->makeGateway(
			array(
				'paypal_email'  => 'merchant@example.com',
				'paypal_enable' => 'yes',
			)
		);

		$this->mockVerifiedPaypalIpn();
		$this->mockBookingForIpn(
			array(
				'ea_booking_user_id'    => 99,
				'ea_booking_price'      => '20.5',
				'ea_booking_currency'   => 'USD',
				'ea_booking_payment_id' => 'paypal',
			)
		);

		Functions\expect( 'get_post_status' )
			->once()
			->with( 70 )
			->andReturn( 'ea-pending' );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				array(
					'ID'          => 70,
					'post_status' => 'ea-completed',
				)
			)
			->andReturn( 70 );

		Functions\when( 'do_action' )->justReturn( true );

		$_POST = $this->paypalIpnData();

		$gateway->payment_validation();
	}

	/**
	 * It ignores verified IPN messages with mismatched payment totals.
	 *
	 * @return void
	 */
	public function test_payment_validation_rejects_amount_mismatch(): void {
		$gateway = $this->makeGateway(
			array(
				'paypal_email'  => 'merchant@example.com',
				'paypal_enable' => 'yes',
			)
		);

		$this->mockVerifiedPaypalIpn();
		$this->mockBookingForIpn(
			array(
				'ea_booking_user_id'    => 99,
				'ea_booking_price'      => '20.5',
				'ea_booking_currency'   => 'USD',
				'ea_booking_payment_id' => 'paypal',
			)
		);

		Functions\expect( 'wp_update_post' )->never();

		$_POST             = $this->paypalIpnData();
		$_POST['mc_gross'] = '1.00';

		$gateway->payment_validation();
		$this->assertTrue( true );
	}

	/**
	 * Build gateway with mocked settings.
	 *
	 * @param array $settings Gateway settings.
	 *
	 * @return \WPEMS_Payment_Gateway_Paypal
	 */
	private function makeGateway( array $settings = array() ): \WPEMS_Payment_Gateway_Paypal {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wpems_get_option' )->alias(
			function ( $key, $default = '' ) use ( $settings ) {
				return $settings[ $key ] ?? $default;
			}
		);

		return new \WPEMS_Payment_Gateway_Paypal();
	}

	/**
	 * Mock legacy booking post lookup.
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return void
	 */
	private function mockLegacyBookingPost( int $booking_id ): void {
		Functions\when( 'get_post_type' )->alias(
			function ( $post_id ) use ( $booking_id ) {
				$this->assertSame( $booking_id, $post_id );

				return 'event_auth_book';
			}
		);
		Functions\when( 'get_post' )->alias(
			function ( $post_id ) use ( $booking_id ) {
				$this->assertSame( $booking_id, $post_id );

				return $this->makePost( $booking_id, 'event_auth_book', 'Booking #' . $booking_id, 'ea-pending' );
			}
		);
	}

	/**
	 * Mock a verified PayPal IPN postback.
	 *
	 * @return void
	 */
	private function mockVerifiedPaypalIpn(): void {
		Functions\expect( 'wp_safe_remote_post' )
			->once()
			->andReturn(
				array(
					'response' => array(
						'code' => 200,
					),
					'body'     => 'VERIFIED',
				)
			);

		Functions\expect( 'wp_remote_retrieve_response_code' )
			->once()
			->andReturn( 200 );

		Functions\expect( 'wp_remote_retrieve_body' )
			->once()
			->andReturn( 'VERIFIED' );
	}

	/**
	 * Mock a booking lookup for PayPal IPN validation.
	 *
	 * @param array $meta Booking meta.
	 *
	 * @return void
	 */
	private function mockBookingForIpn( array $meta ): void {
		$this->mockLegacyBookingPost( 70 );

		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $key, $single = true ) use ( $meta ) {
				$this->assertSame( 70, $post_id );
				$this->assertTrue( $single );

				return $meta[ $key ] ?? '';
			}
		);
	}

	/**
	 * Build a PayPal IPN payload.
	 *
	 * @return array
	 */
	private function paypalIpnData(): array {
		return array(
			'txn_type'       => 'web_accept',
			'payment_status' => 'Completed',
			'receiver_email' => 'merchant@example.com',
			'mc_gross'       => '20.50',
			'mc_currency'    => 'USD',
			'custom'         => wp_json_encode(
				array(
					'booking_id' => 70,
					'user_id'    => 99,
				)
			),
		);
	}
}
