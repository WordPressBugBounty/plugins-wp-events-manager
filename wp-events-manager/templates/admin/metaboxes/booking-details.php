<?php
/**
 * WP Events Manager Booking Details meta box view
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/View
 * @version       2.1.7
 */
use WPEMS\Models\EventPostModel;
use WPEMS\Models\BookingPostModel;
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

global $post;
$booking = BookingPostModel::find( absint( $post->ID ) );
if ( ! $booking ) {
	return;
}

$event_id   = $booking->get_event_id();
$event      = EventPostModel::find( $event_id );
$user_id    = $booking->get_user_id();
$user       = get_userdata( $user_id );
$user_name  = $user && isset( $user->data->user_nicename ) ? $user->data->user_nicename : '';
$user_email = $user && isset( $user->user_email ) ? $user->user_email : '';
$prefix     = 'tp_event_';
?>

<?php do_action( 'tp_event_admin_booking_metabox_before_fields', $post, $prefix ); ?>

	<div id="event-booking-details" class="booking-details">
		<div class="booking-user-data">
			<div class="user-avatar">
				<?php echo get_avatar( $user_id, 120 ); ?>
			</div>
			<div class="order-user-meta">
				<div class="user-display-name">
					<?php printf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=tp-event-users&user_id=' . absint( $user_id ) ) ), esc_html( $user_name ) ); ?>
				</div>
				<div class="user-email">
					<?php echo $user_email ? esc_html( $user_email ) : ''; ?>
				</div>
			</div>
		</div>
		<div class="booking-data">
			<h3 class="booking-data-number"><?php printf( esc_html__( 'Order %s', 'wp-events-manager' ), esc_html( wpems_format_ID( $post->ID ) ) ); ?></h3>
			<div class="booking-date">
				<?php printf( esc_html__( 'Date %s', 'wp-events-manager' ), esc_html( $post->post_date ) ); ?>
			</div>
		</div>

		<h3><?php _e( 'Booking Details', 'wp-events-manager' ); ?></h3>

		<table class="booking-table">
			<thead>
			<tr>
				<th><?php _e( 'Item', 'wp-events-manager' ); ?></th>
				<th><?php _e( 'Cost', 'wp-events-manager' ); ?></th>
				<th><?php _e( 'Quantity', 'wp-events-manager' ); ?></th>
				<th><?php _e( 'Payment Method', 'wp-events-manager' ); ?></th>
				<th><?php _e( 'Amount', 'wp-events-manager' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<tr data-item_id="<?php echo esc_attr( $event_id ); ?>">
				<td><?php printf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $event_id ) ), esc_html( get_the_title( $event_id ) ) ); ?></td>
				<td><?php echo wp_kses_post( wpems_format_price( $event ? $event->get_price() : 0 ) ); ?></td>
				<td><?php echo esc_html( $booking->get_quantity() ); ?></td>
				<td><?php echo esc_html( $booking->get_payment_id() ? wpems_get_payment_title( $booking->get_payment_id() ) : __( 'No payment', 'wp-events-manager' ) ); ?></td>
				<td><?php echo wp_kses_post( wpems_format_price( $booking->get_price() ) ); ?></td>
			</tr>
			</tbody>
			<tfoot>
			<tr>
				<td width="300" colspan="4"><?php _e( 'Sub Total', 'wp-events-manager' ); ?></td>
				<td width="100"><span class="booking-subtotal"></span></td>
			</tr>
			<tr>
				<td colspan="4"><?php _e( 'Total', 'wp-events-manager' ); ?></td>
				<td class="booking-total"><?php echo wp_kses_post( wpems_format_price( $booking->get_price() ) ); ?></td>
			</tr>
			</tfoot>
			<?php wp_nonce_field( 'event_booking_nonce', 'event-booking-nonce' ); ?>
		</table>
	</div>


<?php do_action( 'tp_event_admin_booking_metabox_after_fields', $post, $prefix ); ?>
