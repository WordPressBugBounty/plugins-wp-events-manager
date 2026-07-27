<?php
/**
 * Admin settings manager.
 *
 * @package WPEMS\Admin
 */

namespace WPEMS\Admin;

use WPEMS\Admin\Settings\Checkout;
use WPEMS\Admin\Settings\Emails;
use WPEMS\Admin\Settings\General;
use WPEMS\Admin\Settings\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * WP Events Manager admin settings manager.
 */
class SettingsManager {

	const OPTION_GROUP = 'thimpress_events';
	const CAPABILITY   = 'manage_options';
	const NONCE_ACTION = 'tp-event-settings';
	const NONCE_NAME   = 'tp-event-settings-nonce';

	/**
	 * Setting page objects.
	 *
	 * @var array
	 */
	private static $settings = array();

	/**
	 * Error messages.
	 *
	 * @var array
	 */
	private static $errors = array();

	/**
	 * Update messages.
	 *
	 * @var array
	 */
	private static $messages = array();

	/**
	 * Registered field schemas.
	 *
	 * @var array
	 */
	private static $setting_schema = array();

	/**
	 * Whether hooks have been registered.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		add_action( 'admin_init', array( static::class, 'register_setting' ) );
	}

	/**
	 * Get setting pages.
	 *
	 * @return array
	 */
	public static function get_setting_pages() {
		if ( empty( self::$settings ) ) {
			self::$settings = apply_filters(
				'event_admin_setting_pages',
				array(
					new General(),
					new Pages(),
					new Emails(),
					new Checkout(),
				)
			);
		}

		return self::$settings;
	}

	/**
	 * Reset cached setting pages.
	 *
	 * @internal Test helper and feature-toggle support.
	 *
	 * @return void
	 */
	public static function reset_setting_pages() {
		self::$settings       = array();
		self::$setting_schema = array();
	}

	/**
	 * Add update message.
	 *
	 * @param string $message Message text.
	 *
	 * @return void
	 */
	public static function add_message( $message = '' ) {
		self::$messages[] = $message;
	}

	/**
	 * Add error message.
	 *
	 * @param string $message Message text.
	 *
	 * @return void
	 */
	public static function add_error( $message = '' ) {
		self::$errors[] = $message;
	}

	/**
	 * Display errors and messages.
	 *
	 * @return void
	 */
	public static function show_messages() {
		if ( count( self::$errors ) > 0 ) {
			foreach ( self::$errors as $error ) {
				echo '<div class="error inline"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
			}

			return;
		}

		foreach ( self::$messages as $message ) {
			echo '<div class="updated inline"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Save current settings tab.
	 *
	 * @return bool
	 */
	public static function save() {
		$nonce = ! empty( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return false;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-events-manager' ) );
		}

		global $current_tab;

		do_action( 'event_admin_setting_update_' . $current_tab );
		do_action( 'event_admin_setting_update', $current_tab );

		self::add_message( __( 'Your settings have been saved.', 'wp-events-manager' ) );
		do_action( 'event_admin_settings_updated', wp_unslash( $_POST ), self::get_posted_settings( $_POST ) );

		return true;
	}

	/**
	 * Output settings screen.
	 *
	 * @return void
	 */
	public static function output() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-events-manager' ) );
		}

		global $current_tab, $current_section;

		self::get_setting_pages();

		$tabs            = apply_filters( 'event_admin_settings_tabs_array', array() );
		$requested_tab   = isset( $_GET['tab'] ) && $_GET['tab'] ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$current_tab     = $requested_tab && isset( $tabs[ $requested_tab ] ) ? $requested_tab : self::get_default_tab( $tabs );
		$current_section = isset( $_GET['section'] ) && $_GET['section'] ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';

		if ( ! empty( $_POST ) ) {
			self::save();
		}

		if ( ! $tabs ) {
			return;
		}
		?>
		<div class="wrap">
			<form method="post" name="tp_event_options" action="">
				<h2 class="nav-tab-wrapper">
					<?php foreach ( $tabs as $key => $title ) : ?>
						<a href="<?php echo esc_url( self::get_tab_url( $key ) ); ?>" class="nav-tab<?php echo $current_tab === $key ? ' nav-tab-active' : ''; ?>" data-tab="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $title ); ?>
						</a>
					<?php endforeach; ?>
				</h2>
				<div class="tp_event_wrapper_content">
					<?php do_action( 'event_admin_setting_sections_' . $current_tab ); ?>
					<?php self::show_messages(); ?>
					<?php do_action( 'event_admin_setting_' . $current_tab ); ?>
				</div>
				<p class="submit">
					<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
					<input name="save" class="button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'wp-events-manager' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Legacy render wrapper.
	 *
	 * @param array $fields Setting fields.
	 *
	 * @return void
	 */
	public static function render_fields( $fields = array() ) {
		self::output_fields( $fields );
	}

	/**
	 * Output fields.
	 *
	 * @param array $fields Setting fields.
	 *
	 * @return void
	 */
	public static function output_fields( $fields = array() ) {
		if ( empty( $fields ) ) {
			return;
		}

		foreach ( $fields as $field ) {
			$field = self::normalize_field( $field );

			switch ( $field['type'] ) {
				case 'section_start':
					wpems_get_admin_template( 'settings/section-start.php', array( 'field' => $field ) );
					break;
				case 'section_end':
					wpems_get_admin_template( 'settings/section-end.php', array( 'field' => $field ) );
					break;
				case 'select':
				case 'multiselect':
					wpems_get_admin_template( 'settings/select.php', array( 'field' => $field ) );
					break;
				case 'text':
				case 'number':
				case 'email':
				case 'password':
					wpems_get_admin_template( 'settings/text.php', array( 'field' => $field ) );
					break;
				case 'checkbox':
					wpems_get_admin_template( 'settings/checkbox.php', array( 'field' => $field ) );
					break;
				case 'yes_no':
					wpems_get_admin_template( 'settings/yes-no.php', array( 'field' => $field ) );
					break;
				case 'radio':
					wpems_get_admin_template( 'settings/radio.php', array( 'field' => $field ) );
					break;
				case 'image_size':
					wpems_get_admin_template( 'settings/image-size.php', array( 'field' => $field ) );
					break;
				case 'textarea':
					wpems_get_admin_template( 'settings/textarea.php', array( 'field' => $field ) );
					break;
				case 'select_page':
					wpems_get_admin_template( 'settings/select-page.php', array( 'field' => $field ) );
					break;
				default:
					do_action( 'tp_event_setting_field_' . $field['id'], $field );
					break;
			}
		}
	}

	/**
	 * Save fields.
	 *
	 * @param array      $fields Setting fields.
	 * @param array|null $data   Data source.
	 *
	 * @return bool
	 */
	public static function save_fields( $fields = array(), $data = null ) {
		if ( null === $data ) {
			$data = $_POST;
		}

		if ( empty( $fields ) || empty( $data ) ) {
			return false;
		}

		foreach ( $fields as $field ) {
			self::save_field( self::normalize_field( $field, false ), $data );
		}

		return true;
	}

	/**
	 * Get an option using WooCommerce-style default semantics.
	 *
	 * @param string $option_name   Option name.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 */
	public static function get_option( $option_name, $default_value = '' ) {
		if ( ! $option_name ) {
			return $default_value;
		}

		if ( strstr( $option_name, '[' ) ) {
			parse_str( $option_name, $option_array );
			$option_name   = current( array_keys( $option_array ) );
			$option_values = get_option( $option_name, '' );
			$key           = key( $option_array[ $option_name ] );
			$option_value  = isset( $option_values[ $key ] ) ? $option_values[ $key ] : null;
		} else {
			$option_value = get_option( $option_name, null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = wp_unslash( $option_value );
		} elseif ( null !== $option_value ) {
			$option_value = stripslashes( $option_value );
		}

		return null === $option_value ? $default_value : $option_value;
	}

	/**
	 * Normalize a setting field.
	 *
	 * @param array $field      Field schema.
	 * @param bool  $load_value Whether to load the option value.
	 *
	 * @return array
	 */
	public static function normalize_field( $field, $load_value = true ) {
		$field = wp_parse_args(
			(array) $field,
			array(
				'id'          => '',
				'field_name'  => '',
				'class'       => '',
				'title'       => '',
				'desc'        => '',
				'default'     => '',
				'type'        => '',
				'placeholder' => '',
				'options'     => array(),
				'atts'        => array(),
				'value'       => null,
			)
		);

		if ( '' === $field['field_name'] ) {
			$field['field_name'] = $field['id'];
		}

		if ( $load_value && null === $field['value'] && ! self::is_structural_field( $field ) && 'image_size' !== $field['type'] ) {
			$field['value'] = self::get_option( $field['id'], $field['default'] );
		}

		$field['custom_attributes'] = self::get_custom_attributes( $field );

		return $field;
	}

	/**
	 * Build escaped custom attributes.
	 *
	 * @param array $field Field schema.
	 *
	 * @return string
	 */
	public static function get_custom_attributes( $field ) {
		$atts = isset( $field['atts'] ) && is_array( $field['atts'] ) ? $field['atts'] : array();

		foreach ( array( 'min', 'max', 'step', 'pattern' ) as $legacy_attribute ) {
			if ( isset( $field[ $legacy_attribute ] ) && ! isset( $atts[ $legacy_attribute ] ) ) {
				$atts[ $legacy_attribute ] = $field[ $legacy_attribute ];
			}
		}

		$custom_attributes = '';
		foreach ( $atts as $key => $value ) {
			if ( false === $value || null === $value ) {
				continue;
			}

			$key = sanitize_key( $key );
			if ( ! $key ) {
				continue;
			}

			$custom_attributes .= true === $value ? ' ' . $key : ' ' . $key . '="' . esc_attr( $value ) . '"';
		}

		return $custom_attributes;
	}

	/**
	 * Sanitize a posted setting value.
	 *
	 * @param array $field     Field schema.
	 * @param mixed $raw_value Raw value.
	 *
	 * @return mixed
	 */
	public static function sanitize_field_value( $field, $raw_value ) {
		$field = self::normalize_field( $field, false );
		$type  = $field['type'];

		if ( is_array( $raw_value ) && 'multiselect' !== $type ) {
			$raw_value = reset( $raw_value );
		}

		switch ( $type ) {
			case 'textarea':
				return wp_kses_post( $raw_value );
			case 'email':
				return sanitize_email( $raw_value );
			case 'number':
				$raw_value = sanitize_text_field( $raw_value );
				return is_numeric( $raw_value ) ? $raw_value : '';
			case 'select_page':
				return absint( $raw_value );
			case 'yes_no':
				return 'yes' === $raw_value ? 'yes' : 'no';
			case 'checkbox':
				return in_array( (string) $raw_value, array( '1', 'yes', 'on' ), true ) ? '1' : '0';
			case 'select':
			case 'radio':
				$value = sanitize_text_field( $raw_value );
				if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
					$allowed = array_map( 'strval', array_keys( $field['options'] ) );
					if ( in_array( $value, $allowed, true ) ) {
						return $value;
					}

					return '' !== (string) $field['default'] ? (string) $field['default'] : (string) reset( $allowed );
				}

				return $value;
			case 'multiselect':
				$values = array_map( 'sanitize_text_field', (array) $raw_value );
				if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
					$allowed = array_map( 'strval', array_keys( $field['options'] ) );
					$values  = array_map( 'strval', $values );

					return array_values( array_intersect( $values, $allowed ) );
				}

				return $values;
			case 'password':
				return is_scalar( $raw_value ) ? trim( (string) $raw_value ) : null;
			default:
				if ( is_array( $raw_value ) ) {
					return map_deep( $raw_value, 'sanitize_text_field' );
				}

				return sanitize_text_field( $raw_value );
		}
	}

	/**
	 * Register settings with sanitize callbacks.
	 *
	 * @return void
	 */
	public static function register_setting() {
		foreach ( self::get_registered_settings() as $field ) {
			if ( 'image_size' === $field['type'] ) {
				foreach ( array( 'width', 'height' ) as $dimension ) {
					if ( empty( $field['options'][ $dimension ] ) ) {
						continue;
					}

					register_setting(
						self::OPTION_GROUP,
						$field['id'] . '_' . $dimension,
						array(
							'sanitize_callback' => 'absint',
						)
					);
				}

				continue;
			}

			register_setting(
				self::OPTION_GROUP,
				$field['id'],
				array(
					'sanitize_callback' => function ( $value ) use ( $field ) {
						return self::sanitize_field_value( $field, $value );
					},
				)
			);
		}
	}

	/**
	 * Save a field.
	 *
	 * @param array $field Field schema.
	 * @param array $data  Posted data.
	 *
	 * @return void
	 */
	private static function save_field( $field, $data ) {
		if ( ! $field['id'] || self::is_structural_field( $field ) ) {
			return;
		}

		if ( 'image_size' === $field['type'] ) {
			self::save_image_size_field( $field, $data );
			return;
		}

		$option_name = $field['field_name'];
		if ( ! array_key_exists( $option_name, $data ) ) {
			return;
		}

		$value = self::sanitize_field_value( $field, wp_unslash( $data[ $option_name ] ) );
		if ( null === $value ) {
			return;
		}

		update_option( $field['id'], $value );
	}

	/**
	 * Save image size field.
	 *
	 * @param array $field Field schema.
	 * @param array $data  Posted data.
	 *
	 * @return void
	 */
	private static function save_image_size_field( $field, $data ) {
		foreach ( array( 'width', 'height' ) as $dimension ) {
			if ( empty( $field['options'][ $dimension ] ) ) {
				continue;
			}

			$key = $field['id'] . '_' . $dimension;
			if ( array_key_exists( $key, $data ) ) {
				update_option( $key, absint( wp_unslash( $data[ $key ] ) ) );
			}
		}
	}

	/**
	 * Determine if a field is layout-only.
	 *
	 * @param array $field Field schema.
	 *
	 * @return bool
	 */
	private static function is_structural_field( $field ) {
		return in_array( $field['type'], array( 'section_start', 'section_end' ), true );
	}

	/**
	 * Get all registered option fields.
	 *
	 * @return array
	 */
	private static function get_registered_settings() {
		if ( self::$setting_schema ) {
			return self::$setting_schema;
		}

		foreach ( self::get_setting_pages() as $page ) {
			if ( ! is_object( $page ) || ! method_exists( $page, 'get_settings' ) ) {
				continue;
			}

			foreach ( (array) $page->get_settings() as $field ) {
				$field = self::normalize_field( $field, false );
				if ( ! $field['id'] || self::is_structural_field( $field ) ) {
					continue;
				}

				self::$setting_schema[ $field['id'] ] = $field;
			}
		}

		return self::$setting_schema;
	}

	/**
	 * Get sanitized posted settings.
	 *
	 * @param array $data Posted data.
	 *
	 * @return array
	 */
	private static function get_posted_settings( $data ) {
		$posted_settings = array();

		foreach ( self::get_registered_settings() as $field ) {
			if ( 'image_size' === $field['type'] ) {
				foreach ( array( 'width', 'height' ) as $dimension ) {
					$key = $field['id'] . '_' . $dimension;
					if ( ! empty( $field['options'][ $dimension ] ) && array_key_exists( $key, $data ) ) {
						$posted_settings[ $key ] = absint( wp_unslash( $data[ $key ] ) );
					}
				}
				continue;
			}

			$option_name = $field['field_name'];
			if ( array_key_exists( $option_name, $data ) ) {
				$posted_settings[ $field['id'] ] = self::sanitize_field_value( $field, wp_unslash( $data[ $option_name ] ) );
			}
		}

		return $posted_settings;
	}

	/**
	 * Get first registered tab.
	 *
	 * @param array $tabs Tabs.
	 *
	 * @return string
	 */
	private static function get_default_tab( $tabs ) {
		$keys = array_keys( $tabs );

		return $keys ? current( $keys ) : '';
	}

	/**
	 * Get tab URL.
	 *
	 * @param string $tab Tab ID.
	 *
	 * @return string
	 */
	private static function get_tab_url( $tab ) {
		return add_query_arg(
			array(
				'page' => 'tp-event-setting',
				'tab'  => sanitize_key( $tab ),
			),
			admin_url( 'admin.php' )
		);
	}
}
