<?php
/**
 * The Template for displaying shortcode user account.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/shortcodes/user-account.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7.4
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

wpems_print_notices();

if ( ! is_user_logged_in() ) {
	printf( wp_kses_post( __( 'You are not <a href="%s">login</a>', 'wp-events-manager' ) ), esc_url( wpems_login_url() ) );

	return;
}

$query = new WP_Query( $args );

if ( $query->have_posts() ) { ?>

	<table>
		<thead>
		<th><?php _e( 'Booking ID', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Events', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Type', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Cost', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Quantity', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Method', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Status', 'wp-events-manager' ); ?></th>
		</thead>

		<tbody>
		<?php
		foreach ( $query->posts as $post ) {
			?>
			<?php
			$booking = \WPEMS\Models\BookingPostModel::find( absint( $post->ID ) );
			if ( ! $booking ) {
				continue;
			}
			?>
			<tr>
				<td><?php echo esc_html( wpems_format_ID( $post->ID ) ); ?></td>
				<td><?php printf( '<a href="%s">%s</a>', esc_url( get_the_permalink( $booking->get_event_id() ) ), esc_html( get_the_title( $booking->get_event_id() ) ) ); ?></td>
				<td><?php echo esc_html( $booking->get_price() == 0 ? __( 'Free', 'wp-events-manager' ) : __( 'Cost', 'wp-events-manager' ) ); ?></td>
				<td><?php echo wp_kses_post( wpems_format_price( $booking->get_price(), $booking->get_currency() ) ); ?></td>
				<td><?php echo esc_html( $booking->get_quantity() ); ?></td>
				<td><?php echo esc_html( $booking->get_payment_id() ? wpems_get_payment_title( $booking->get_payment_id() ) : __( 'No payment', 'wp-events-manager' ) ); ?></td>
				<th><?php echo wp_kses_post( wpems_booking_status( $booking->get_id() ) ); ?></th>
			</tr>
		<?php } ?>
		</tbody>
	</table>

	<?php
	$args = array(
		'base'               => '%_%',
		'format'             => '?paged=%#%',
		'total'              => 1,
		'current'            => 0,
		'show_all'           => false,
		'end_size'           => 1,
		'mid_size'           => 2,
		'prev_next'          => true,
		'prev_text'          => __( '« Previous', 'wp-events-manager' ),
		'next_text'          => __( 'Next »', 'wp-events-manager' ),
		'type'               => 'plain',
		'add_args'           => false,
		'add_fragment'       => '',
		'before_page_number' => '',
		'after_page_number'  => '',
	);

	echo wp_kses_post(
		paginate_links(
			array(
				'base'      => str_replace( 9999999, '%#%', esc_url( get_pagenum_link( 9999999 ) ) ),
				'format'    => '?paged=%#%',
				'prev_text' => __( '« Previous', 'wp-events-manager' ),
				'next_text' => __( 'Next »', 'wp-events-manager' ),
				'current'   => max( 1, get_query_var( 'paged' ) ),
				'total'     => $query->max_num_pages,
			)
		)
	);
	?>

<?php } else { ?>
	<p><?php esc_html_e( 'No event booking has been made yet.', 'wp-events-manager' ); ?></p>
	<a class="button"
		href="<?php echo esc_url( get_post_type_archive_link( 'tp_event' ) ); ?>"><?php esc_html_e( 'Go to Events', 'wp-events-manager' ); ?></a>
<?php } ?>

<?php wp_reset_postdata(); ?>
