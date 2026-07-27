<?php
/**
 * WP Events Manager Register Event Mail class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 *
 */
class WPEMS_Email_Register_Event {

	public function __construct() {

		add_action( 'tp_event_updated_status', array( $this, 'email_register' ), 10, 3 );
	}

	// send email
	public function email_register( $booking_id, $old_status, $status ) {

		if ( $old_status === $status ) {
			return;
		}

		if ( ! $booking_id ) {
			throw new Exception( sprintf( __( 'Error %s booking ID', 'wp-events-manager' ), $booking_id ) );
		}

		if ( wpems_get_option( 'email_enable', 'yes' ) === 'no' ) {
			return;
		}

		$booking = \WPEMS\Models\BookingPostModel::find( absint( $booking_id ) );

		if ( $booking ) {
			$user_id = $booking->get_user_id();
			if ( ! $user_id ) {
				throw new Exception( __( 'User is not exists!', 'wp-events-manager' ) );
				die();
			}
			$user = get_userdata( $user_id );

			$email_subject = wpems_get_option( 'email_subject' ) ? wpems_get_option( 'email_subject' ) : __( 'Register event', 'wp-events-manager' );

			$headers[] = 'Content-Type: text/html; charset=UTF-8';
			// set mail from email
			add_filter( 'wp_mail_from', array( $this, 'email_from' ) );
			// set mail from name
			add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );

			if ( $user && $to = $user->data->user_email ) {

				$email_body = wpems_get_option( 'email_body' ) ? html_entity_decode( wpems_get_option( 'email_body' ) ) : wpems_get_template_content( 'emails/register-event-body.php' );

				$find       = array(
					'user-displayname' => '{user_displayname}',
					'user-link'        => '{user_link}',
					'event-link'       => '{event_link}',
					'event-type'       => '{event_type}',
					'booking-id'       => '{booking_id}',
					'booking-quantity' => '{booking_quantity}',
					'booking-price'    => '{booking_price}',
					'booking-payment'  => '{booking_payment_method}',
					'booking-status'   => '{booking_status}',
					'event-title'      => '{event_title}',
				);
				$return     = array();
				$return[]   = sprintf( '%s', wpems_booking_status( $booking->get_id() ) );
				$return[]   = $booking->get_payment_id() ? sprintf( '(%s)', wpems_get_payment_title( $booking->get_payment_id() ) ) : '';
				$replace    = array(
					'user-displayname' => esc_html( $user->data->display_name ),
					'user-link'        => esc_url( wpems_account_url() ),
					'event-link'       => esc_url( get_permalink( $booking->get_event_id() ) ),
					'event-type'       => $booking->get_price() == 0 ? __( 'Free', 'wp-events-manager' ) : __( 'Cost', 'wp-events-manager' ),
					'booking-id'       => esc_html( $booking->get_id() ),
					'booking-quantity' => esc_html( $booking->get_quantity() ),
					'booking-price'    => wp_kses_post( wpems_format_price( $booking->get_price(), true ) ),
					'booking-payment'  => esc_html( $booking->get_payment_id() ? wpems_get_payment_title( $booking->get_payment_id() ) : __( 'No payment', 'wp-events-manager' ) ),
					'booking-status'   => wp_kses_post( implode( '', $return ) ),
					'event-title'      => esc_html( get_the_title( $booking->get_event_id() ) ),
				);
				$email_body = str_replace( $find, $replace, $email_body );

				$email_user_content  = wpems_get_template_content(
					'emails/register-event.php',
					array(
						'booking'    => $booking,
						'email_body' => $email_body,
						'user'       => $user,
					)
				);
				$email_admin_content = wpems_get_template_content(
					'emails/register-admin-event.php',
					array(
						'booking' => $booking,
						'user'    => $user,
					)
				);

				wp_mail( get_option( 'admin_email' ), $email_subject, stripslashes( $email_admin_content ), $headers );

				return wp_mail( $to, $email_subject, stripslashes( $email_user_content ), $headers );
			}
		}
	}

	// set from email
	public function email_from( $email ) {
		if ( $email = wpems_get_option( 'admin_email', get_option( 'admin_email' ) ) ) {
			if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				return $email;
			}
		}

		return $email;
	}

	// set from name
	public function from_name( $name ) {
		if ( $name = wpems_get_option( 'email_from_name' ) ) {
			return sanitize_text_field( $name );
		}

		return $name;
	}
}

new WPEMS_Email_Register_Event();
