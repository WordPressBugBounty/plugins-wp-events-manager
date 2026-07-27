<?php

namespace WPEMS\Widgets;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WidgetBase.php';

class WidgetCountdown extends WidgetBase {
	protected $wpems_widget_id = 'widget_countdown';

	public function __construct() {
		$this->wpems_widget_name        = __( 'WP Event Countdown', 'wp-events-manager' );
		$this->wpems_widget_description = __( 'Countdown timer for event', 'wp-events-manager' );

		parent::__construct();
	}

	public function widget( $args, $instance ) {
		echo wp_kses_post( $args['before_widget'] );

		if ( ! empty( $instance['title'] ) ) {
			echo wp_kses_post( $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'] );
		}

		unset( $instance['title'] );

		$html   = array();
		$html[] = '[wp_event_countdown';

		$data_send = [];
		$event_ids = [];
		foreach ( $instance as $key => $value ) {
			if ( $key === 'wp_events' ) {
				$event_ids = $value;
			} else {
				$data_send[ $key ] = $value;
			}
		}

		$data_send['event_id'] = implode( ',', $event_ids );

		wpems_get_template( 'shortcodes/event-countdown.php', [ 'args' => $data_send ] );
		//echo do_shortcode( implode( ' ', $html ) );
		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$title      = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$selected   = ! empty( $instance['wp_events'] ) ? $instance['wp_events'] : array();
		$nav        = isset( $instance['wp_navigation'] ) ? $instance['wp_navigation'] : false;
		$pagination = isset( $instance['wp_pagination'] ) ? $instance['wp_pagination'] : false;
		$slide      = isset( $instance['wp_slide'] ) ? $instance['wp_slide'] : false;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'wp-events-manager' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'wp_slide' ) ); ?>"><?php esc_html_e( 'Carousel Slide:', 'wp-events-manager' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'wp_slide' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wp_slide' ) ); ?>" type="checkbox" value="true"<?php checked( $slide, 'true' ); ?>>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'wp_navigation' ) ); ?>"><?php esc_html_e( 'Navigation:', 'wp-events-manager' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'wp_navigation' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wp_navigation' ) ); ?>" type="checkbox" value="true"<?php checked( $nav, 'true' ); ?>>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'wp_pagination' ) ); ?>"><?php esc_html_e( 'Pagination:', 'wp-events-manager' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'wp_pagination' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wp_pagination' ) ); ?>" type="checkbox" value="true"<?php checked( $pagination, 'true' ); ?>>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'wp_events' ) ); ?>"><?php esc_html_e( 'Events:', 'wp-events-manager' ); ?></label>
			<?php echo $this->events( $selected ); ?>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance                  = array();
		$instance['title']         = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['wp_events']     = isset( $new_instance['wp_events'] ) ? array_map( 'absint', (array) $new_instance['wp_events'] ) : array();
		$instance['wp_slide']      = isset( $new_instance['wp_slide'] ) && 'true' === $new_instance['wp_slide'] ? 'true' : false;
		$instance['wp_navigation'] = isset( $new_instance['wp_navigation'] ) && 'true' === $new_instance['wp_navigation'] ? 'true' : false;
		$instance['wp_pagination'] = isset( $new_instance['wp_pagination'] ) && 'true' === $new_instance['wp_pagination'] ? 'true' : false;

		return $instance;
	}

	public function events( $selected ) {
		$status   = array(
			'upcoming'  => __( 'Upcoming', 'wp-events-manager' ),
			'happening' => __( 'Happening', 'wp-events-manager' ),
			'expired'   => __( 'Expired', 'wp-events-manager' ),
		);
		$selected = array_map( 'intval', $selected );
		$status   = apply_filters( 'tp_event_widget_countdown', $status );
		$i        = 0;
		?>
		<ul class="tp_event_widget_tab">
			<?php foreach ( $status as $key => $label ) : ?>
				<li>
					<a href="#" data-tab="<?php echo esc_attr( $key ); ?>" class="button<?php echo 0 === $i ? esc_attr( ' button-primary' ) : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				</li>
				<?php ++$i; ?>
			<?php endforeach; ?>
		</ul>
		<?php
		$i = 0;

		foreach ( $status as $stt => $label ) {
			$results = new \WP_Query(
				array(
					'post_type'      => 'tp_event',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'   => 'tp_event_status',
							'value' => $stt,
						),
					),
				)
			);

			if ( ! $results->have_posts() ) {
				continue;
			}
			?>
			<div class="tp_event_admin_widget<?php echo 0 === $i ? esc_attr( ' active' ) : ''; ?>" data-status="<?php echo esc_attr( $stt ); ?>">
				<ul>
					<?php while ( $results->have_posts() ) : ?>
						<?php $results->the_post(); ?>
						<li>
							<p>
								<input id="<?php echo esc_attr( $this->id . '-' . get_the_ID() ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'wp_events' ) ); ?>[]" value="<?php echo esc_attr( get_the_ID() ); ?>" <?php checked( in_array( get_the_ID(), $selected, true ) ); ?>>
								<label for="<?php echo esc_attr( $this->id . '-' . get_the_ID() ); ?>"><?php echo esc_html( get_the_title() ); ?></label>
							</p>
						</li>
					<?php endwhile; ?>
				</ul>
			</div>
			<?php
			wp_reset_postdata();
			++$i;
		}
	}
}
