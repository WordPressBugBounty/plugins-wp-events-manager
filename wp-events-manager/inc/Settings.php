<?php
/**
 * Settings storage helper.
 *
 * @package WPEMS
 */

namespace WPEMS;

defined( 'ABSPATH' ) || exit;

/**
 * WP Events Manager settings helper.
 */
class Settings {

	/**
	 * Legacy array option cache.
	 *
	 * @var array
	 */
	public $_options = array();

	/**
	 * Base option prefix.
	 *
	 * @var string
	 */
	public $_prefix = 'thimpress_events';

	/**
	 * Instances by prefix.
	 *
	 * @var array
	 */
	public static $_instance = array();

	/**
	 * Constructor.
	 *
	 * @param string|null $prefix Option prefix.
	 */
	public function __construct( $prefix = null ) {
		if ( is_string( $prefix ) && $prefix ) {
			$this->_prefix = $prefix;
		}

		$this->_options = $this->options();
	}

	/**
	 * Read dynamic setting metadata.
	 *
	 * @param string|null $id Setting ID.
	 *
	 * @return mixed|null
	 */
	public function get_setting_field( $id = null ) {
		$settings = apply_filters( 'tp_event_settings_field', array() );

		if ( isset( $settings[ $id ] ) ) {
			return $settings[ $id ];
		}

		return null;
	}

	/**
	 * Load the legacy array option.
	 *
	 * @return array
	 */
	protected function options() {
		$options = get_option( $this->_prefix, array() );

		return is_array( $options ) ? $options : array();
	}

	/**
	 * Normalize a logical key to the per-option name used by WPEMS.
	 *
	 * @param string $name Setting key or full option name.
	 *
	 * @return string
	 */
	protected function get_option_name( $name = '' ) {
		if ( ! is_scalar( $name ) ) {
			return '';
		}

		$name = (string) $name;
		if ( '' === $name ) {
			return '';
		}

		$option_prefix = $this->_prefix . '_';
		if ( 0 === strpos( $name, $option_prefix ) ) {
			return $name;
		}

		return $option_prefix . $name;
	}

	/**
	 * Normalize a key for the legacy array option.
	 *
	 * @param string $name Setting key or full option name.
	 *
	 * @return string
	 */
	protected function get_cache_key( $name = '' ) {
		$name          = (string) $name;
		$option_prefix = $this->_prefix . '_';

		if ( 0 === strpos( $name, $option_prefix ) ) {
			return substr( $name, strlen( $option_prefix ) );
		}

		return $name;
	}

	/**
	 * Get a form field name.
	 *
	 * @param string|null $name Field key.
	 *
	 * @return string|null
	 */
	public function get_field_name( $name = null ) {
		if ( ! $this->_prefix || ! $name ) {
			return null;
		}

		return $this->_prefix . '[' . $this->get_cache_key( $name ) . ']';
	}

	/**
	 * Get a form field ID.
	 *
	 * @param string|null $name          Field key.
	 * @param mixed       $default_value Unused legacy parameter.
	 *
	 * @return string|null
	 */
	public function get_field_id( $name = null, $default_value = null ) {
		if ( ! $this->_prefix || ! $name ) {
			return null;
		}

		return $this->get_option_name( $name );
	}

	/**
	 * Get option value.
	 *
	 * @param string|null $name          Setting key.
	 * @param mixed       $default_value Default value.
	 *
	 * @return mixed
	 */
	public function get( $name = null, $default_value = null ) {
		if ( ! $name ) {
			return $default_value;
		}

		$option_name  = $this->get_option_name( $name );
		$option_value = $option_name ? get_option( $option_name, null ) : null;

		if ( null !== $option_value ) {
			return $option_value;
		}

		$cache_key = $this->get_cache_key( $name );
		if ( array_key_exists( $cache_key, $this->_options ) ) {
			return $this->_options[ $cache_key ];
		}

		return $default_value;
	}

	/**
	 * Set option value.
	 *
	 * @param string|null $name  Setting key.
	 * @param mixed       $value Setting value.
	 *
	 * @return bool
	 */
	public function set( $name = null, $value = null ) {
		if ( ! $name ) {
			return false;
		}

		$option_name = $this->get_option_name( $name );
		if ( ! $option_name ) {
			return false;
		}

		$this->_options[ $this->get_cache_key( $name ) ] = $value;

		return update_option( $option_name, $value );
	}

	/**
	 * Get instance.
	 *
	 * @param string|null $prefix Option prefix.
	 *
	 * @return static
	 */
	public static function instance( $prefix = null ) {
		$instance_key = is_string( $prefix ) && $prefix ? $prefix : 'thimpress_events';

		if ( ! empty( self::$_instance[ $instance_key ] ) && self::$_instance[ $instance_key ] instanceof static ) {
			$GLOBALS['event_auth_settings'] = self::$_instance[ $instance_key ];

			return self::$_instance[ $instance_key ];
		}

		self::$_instance[ $instance_key ] = new static( $prefix );
		$GLOBALS['event_auth_settings']   = self::$_instance[ $instance_key ];

		return self::$_instance[ $instance_key ];
	}
}
