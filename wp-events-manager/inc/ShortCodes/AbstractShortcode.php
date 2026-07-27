<?php
/**
 * Base shortcode class.
 *
 * @package WPEMS/ShortCodes
 */

namespace WPEMS\ShortCodes;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractShortcode
 */
abstract class AbstractShortcode {
	/**
	 * Shortcode prefix.
	 *
	 * @var string
	 */
	protected $prefix = 'wp_event_';

	/**
	 * Shortcode name without prefix.
	 *
	 * @var string
	 */
	protected $shortcode_name = '';

	/**
	 * Singleton instances.
	 *
	 * @var array<string, self>
	 */
	private static $instances = array();

	/**
	 * Get singleton instance and register the shortcode.
	 *
	 * @return self
	 */
	final public static function instance(): self {
		$class = static::class;
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static();
		}

		return self::$instances[ $class ];
	}

	/**
	 * Render without registering. Used by compatibility facades.
	 *
	 * @param string|array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	final public static function render_shortcode( $attrs ): string {
		$shortcode = new static( false );

		return $shortcode->render( $attrs );
	}

	/**
	 * Constructor.
	 *
	 * @param bool $register Whether to register the shortcode.
	 */
	final protected function __construct( bool $register = true ) {
		if ( $register ) {
			$this->init();
		}
	}

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	protected function init() {
		add_shortcode( $this->get_shortcode_tag(), array( $this, 'render' ) );
	}

	/**
	 * Get filtered shortcode tag.
	 *
	 * @return string
	 */
	protected function get_shortcode_tag(): string {
		return apply_filters( "wp_event_{$this->shortcode_name}_shortcode_tag", $this->prefix . $this->shortcode_name );
	}

	/**
	 * Get sanitized request value.
	 *
	 * @param string $key  Request key.
	 * @param string $type Value type.
	 *
	 * @return string
	 */
	protected static function request_value( string $key, string $type = 'text' ): string {
		if ( ! isset( $_REQUEST[ $key ] ) ) {
			return '';
		}

		$value = wp_unslash( $_REQUEST[ $key ] );
		if ( 'email' === $type ) {
			return sanitize_email( $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Render shortcode content.
	 *
	 * @param string|array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	abstract public function render( $attrs ): string;
}
