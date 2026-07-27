<?php
/**
 * WP Events Manager Ajax class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */
use WPEMS\Models\EventPostModel;
use WPEMS\Models\BookingPostModel;
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * Ajax Process
 */
class WPEMS_Ajax {

	public function __construct() {
		// actions with
		// key is action ajax: wp_ajax_{action}
		// value is allow ajax nopriv: wp_ajax_nopriv_{action}
		$actions = array(
			'event_remove_notice' => false,
			'event_auth_register' => false,
			'event_login_action'  => true,
			'load_form_register'  => true,
		);

		foreach ( $actions as $action => $nopriv ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_' . $action, array( $this, $action ) );
			} else {
				add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'must_login' ) );
			}
		}
	}

	/**
	 * Remove admin notice
	 */
	public function event_remove_notice() {
		check_ajax_referer( 'event_remove_notice', 'event_remove_notice_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$error = new WP_Error( __( 'Permission denied', 'wp-events-manager' ) );
			wp_send_json_error( $error, 403 );
		}

		if ( is_multisite() ) {
			update_site_option( 'thimpress_events_show_remove_event_auth_notice', 1 );
		} else {
			update_option( 'thimpress_events_show_remove_event_auth_notice', 1 );
		}
		wp_send_json(
			array(
				'status'  => true,
				'message' => __( 'Remove admin notice successful', 'wp-events-manager' ),
			)
		);
	}


	/**
	 * load form register
	 *
	 * @return html login form if user not logged in || @return html register event form
	 */
	public function load_form_register() {
		$nonce = ! empty( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'event-auth-register-nonce' ) ) {
			wp_die( '', 403 );
		}

		$event_id = ! empty( $_POST['event_id'] ) ? absint( wp_unslash( $_POST['event_id'] ) ) : 0;

		if ( ! $event_id ) {
			wpems_add_notice( 'error', __( 'Event not found.', 'wp-events-manager' ) );
			wpems_print_notices();
			die();
		} elseif ( ! is_user_logged_in() ) {
			wpems_print_notices( 'error', __( 'You must login before register ', 'wp-events-manager' ) . sprintf( ' <strong>%s</strong>', get_the_title( $event_id ) ) );
			die();
		} else {
			$event = EventPostModel::find( $event_id );
			if ( ! $event ) {
				wpems_print_notices( 'error', __( 'Event not found.', 'wp-events-manager' ) );
				die();
			}

			$registered_time = $event->booked_quantity( get_current_user_id() );
			ob_start();
			if ( $event->get_status() === 'expired' ) {
				wpems_print_notices( 'error', sprintf( '%s %s', get_the_title( $event_id ), __( 'has been expired', 'wp-events-manager' ) ) );
			} elseif ( $registered_time && wpems_get_option( 'email_register_times' ) === 'once' && $event->is_free() ) {
				wpems_print_notices( 'error', __( 'You have registered this event before', 'wp-events-manager' ) );
			} elseif ( ! $event->get_slot_available() ) {
				wpems_print_notices( 'error', __( 'The event is full, the registration is closed', 'wp-events-manager' ) );
			} else {
				wpems_get_template( 'loop/booking-form.php', array( 'event_id' => $event_id ) );
			}
			echo ob_get_clean();
			die();
		}
	}

	/**
	 * Login Ajax
	 */
	public function event_login_action() {
		WPEMS_User_Process::process_login();
		die();
	}

	// register event
	public function event_auth_register() {
		try {
			// sanitize, validate data
			$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
			if ( 'POST' !== $request_method ) {
				throw new Exception( __( 'Invalid request', 'wp-events-manager' ) );
			}

			$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
			if ( 'event_auth_register' !== $action ) {
				throw new Exception( __( 'Invalid request', 'wp-events-manager' ) );
			}
			check_ajax_referer( 'event_auth_register_nonce', 'event_auth_register_nonce' );

			$event_id     = false;
			$raw_event_id = isset( $_POST['event_id'] ) ? wp_unslash( $_POST['event_id'] ) : '';
			if ( '' === $raw_event_id || ! is_numeric( $raw_event_id ) ) {
				throw new Exception( __( 'Invalid event request', 'wp-events-manager' ) );
			} else {
				$event_id = absint( $raw_event_id );
			}

			$qty     = 0;
			$raw_qty = isset( $_POST['qty'] ) ? wp_unslash( $_POST['qty'] ) : '';
			if ( '' === $raw_qty || ! is_numeric( $raw_qty ) ) {
				throw new Exception( __( 'Quantity must integer', 'wp-events-manager' ) );
			} else {
				$qty = absint( $raw_qty );
			}

			if ( $qty < 1 ) {
				throw new Exception( __( 'Quantity must integer', 'wp-events-manager' ) );
			}

			// End sanitize, validate data
			// load booking module
			$booking = new BookingPostModel();
			$event   = EventPostModel::find( $event_id );
			if ( ! $event ) {
				throw new Exception( __( 'Event not found.', 'wp-events-manager' ) );
			}

			$user       = wp_get_current_user();
			$registered = $event->booked_quantity( $user->ID );

			if ( $event->is_free() && $registered != 0 && wpems_get_option( 'email_register_times', 'once' ) === 'once' ) {
				throw new Exception( __( 'You are registered this event.', 'wp-events-manager' ) );
			}

			if ( $event->get_quantity() && ( $event->booked_quantity() + $qty > $event->get_quantity() ) ) {
				throw new Exception( __( 'There is not any slots now. Please try with next future events!', 'wp-events-manager' ) );
			}

			$payment_methods = wpems_payment_gateways();

			$payment = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : false;

			// create new book return $booking_id if success and WP Error if fail
			$args = apply_filters(
				'tp_event_create_booking_args',
				array(
					'event_id'   => $event_id,
					'qty'        => $qty,
					'price'      => (float) $event->get_price() * $qty,
					'payment_id' => $payment,
					'currency'   => wpems_get_currency(),
				)
			);

			$payment = ! empty( $payment_methods[ $payment ] ) ? $payment_methods[ $payment ] : false;

			$return = array();

			if ( $args['price'] > 0 && $payment && ! $payment->is_available() ) {
				throw new Exception( sprintf( '%s %s', get_title(), __( 'is not ready. Please contact administrator to setup payment gateways', 'wp-events-manager' ) ) );
			}

			if ( $payment && $payment->id == 'woo_payment' ) {

				do_action( 'tp_event_register_event_action', $args );
				$return = $payment->process( $event_id );
				wp_send_json( $return );

			} else {

				$booking_id = $booking->create_booking( $args, $args['payment_id'] );
				// create booking result
				if ( is_wp_error( $booking_id ) ) {
					throw new Exception( $booking_id->get_error_message() );
				} elseif ( $args['price'] == 0 ) {
						// update booking status
					$book = BookingPostModel::find( $booking_id );
					if ( ! $book ) {
						throw new Exception( __( 'Booking ID is not exists!', 'wp-events-manager' ) );
					}

					$book->update_status();

					// user booking
					$user       = get_userdata( $book->get_user_id() );
					$user_email = $user && ! empty( $user->user_email ) ? $user->user_email : '';
					wpems_add_notice( 'success', sprintf( __( 'Book ID <strong>%1$s</strong> completed! We\'ll send mail to <strong>%2$s</strong> when it is approve.', 'wp-events-manager' ), wpems_format_ID( $booking_id ), $user_email ) );
					wp_send_json(
						apply_filters(
							'event_auth_register_ajax_result',
							array(
								'status' => true,
								'url'    => wpems_account_url(),
							)
						)
					);
				} elseif ( $payment ) {

					$return = $payment->process( $booking_id );
					if ( isset( $return['status'] ) && $return['status'] === false ) {
						wp_delete_post( $booking_id );
					}
					wp_send_json( $return );
				} else {
					wp_send_json(
						array(
							'status'  => false,
							'message' => __( 'Payment method is not available', 'wp-events-manager' ),
						)
					);
				}
			}
		} catch ( Exception $e ) {
			if ( $e ) {
				wpems_add_notice( 'error', $e->getMessage() );
			}
		}
		wpems_print_notices();
		$message = ob_get_clean();
		// allow hook
		wp_send_json(
			array(
				'status'  => false,
				'message' => $message,
			)
		);
		die();
	}

	// ajax nopriv: user is not signin
	public function must_login() {
		wp_send_json(
			array(
				'status'  => false,
				'message' => sprintf( __( 'You Must <a href="%s">Login</a>', 'wp-events-manager' ), esc_url( wpems_login_url() ) ),
			)
		);
		die();
	}
}

// initialize ajax class process
new WPEMS_Ajax();
