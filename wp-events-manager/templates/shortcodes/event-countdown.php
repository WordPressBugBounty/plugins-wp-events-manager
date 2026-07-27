<?php
/**
 * The Template for displaying shortcode event countdown.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/shortcodes/event-countdown.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.8
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $args ) ) {
	return;
}

$event_id = $args['event_id'] ?? '';

if ( ! empty( $event_id ) ) {
	$ids = explode( ',', $event_id );
	foreach ( $ids as $id ) {
		$id = absint( $id );
		if ( $id <= 0 ) {
			continue;
		}

		$event = get_post( $id );
		if ( ! $event ) {
			continue;
		}

		printf( '<h2><a href="%s">%s</a></h2>', esc_url( get_permalink( $id ) ), esc_html( get_the_title( $id ) ) );

		$current_time = current_time( 'Y-m-d H:i' );
		$time         = wpems_get_time( 'Y-m-d H:i', $event, false ); ?>
		<div class="event-countdown">
			<?php $date = new DateTime( date( 'Y-m-d H:i', strtotime( $time ) ) ); ?>
			<div class="tp_event_counter" data-time="<?php echo esc_attr( $date->format( 'M j, Y H:i:s O' ) ); ?>"></div>
		</div>
		<?php
	}
} else {
	?>
	<p class="tp-event-notice error"><?php echo esc_html__( 'Invalid Event ID', 'wp-events-manager' ); ?></p>
	<?php
}
