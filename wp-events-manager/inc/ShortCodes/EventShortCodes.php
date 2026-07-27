<?php
/**
 * Event shortcode wrapper.
 *
 * @package WPEMS/ShortCodes
 */

namespace WPEMS\ShortCodes;

defined( 'ABSPATH' ) || exit;

class EventShortCodes {

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'tp_event_shortcode_wrapper_start', array( '\WPEMS_Shortcodes', 'shortcode_wrapper_start' ) );
		add_action( 'tp_event_shortcode_wrapper_end', array( '\WPEMS_Shortcodes', 'shortcode_wrapper_end' ) );

		ListEventShortcode::instance();
		RegisterShortcode::instance();
		LoginShortcode::instance();
		ForgotPasswordShortcode::instance();
		ResetPasswordShortcode::instance();
		AccountShortcode::instance();
		CountdownShortcode::instance();

		add_action( 'template_redirect', array( '\WPEMS_Shortcodes', 'auto_shortcode' ) );
	}

	/**
	 * Render event list shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function list_event( $atts ): string {
		return ListEventShortcode::render_shortcode( $atts );
	}

	/**
	 * Render register shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function register( $atts ): string {
		return RegisterShortcode::render_shortcode( $atts );
	}

	/**
	 * Render login shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function login( $atts ): string {
		return LoginShortcode::render_shortcode( $atts );
	}

	/**
	 * Render forgot password shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function forgot_password( $atts ): string {
		return ForgotPasswordShortcode::render_shortcode( $atts );
	}

	/**
	 * Render reset password shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function reset_password( $atts ): string {
		return ResetPasswordShortcode::render_shortcode( $atts );
	}

	/**
	 * Render account shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function account( $atts ): string {
		return AccountShortcode::render_shortcode( $atts );
	}

	/**
	 * Render countdown shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function countdown( $atts ): string {
		return CountdownShortcode::render_shortcode( $atts );
	}
}
