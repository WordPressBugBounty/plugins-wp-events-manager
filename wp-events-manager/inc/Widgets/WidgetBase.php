<?php

namespace WPEMS\Widgets;

use WP_Widget;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for widgets.
 *
 * @since 2.2.5
 */
class WidgetBase extends WP_Widget {
	protected $prefix                   = 'wpems_';
	protected $wpems_widget_id          = '';
	protected $wpems_widget_name        = '';
	protected $wpems_widget_description = '';
	protected $wpems_widget_class       = '';
	protected $wpems_widget_options     = array();

	public function __construct() {
		$id_base         = $this->prefix . $this->wpems_widget_id;
		$name            = $this->wpems_widget_name;
		$widget_options  = array_merge(
			array(
				'description'                 => $this->wpems_widget_description,
				'classname'                   => $this->wpems_widget_class,
				'customize_selective_refresh' => true,
			),
			$this->wpems_widget_options
		);
		$control_options = $this->control_options;

		parent::__construct( $id_base, $name, $widget_options, $control_options );
	}
}
