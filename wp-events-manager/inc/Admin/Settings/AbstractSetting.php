<?php
/**
 * Base admin setting page.
 *
 * @package WPEMS\Admin\Settings
 */

namespace WPEMS\Admin\Settings;

use WPEMS\Admin\SettingsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Base settings page class.
 */
abstract class AbstractSetting {

	/**
	 * Setting page ID.
	 *
	 * @var string|null
	 */
	protected $id = null;

	/**
	 * Setting page label.
	 *
	 * @var string|null
	 */
	protected $label = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'event_admin_settings_tabs_array', array( $this, 'add_setting_tab' ) );
		add_action( 'event_admin_setting_sections_' . $this->id, array( $this, 'output_section' ) );
		add_action( 'event_admin_setting_update_' . $this->id, array( $this, 'save' ) );
		add_action( 'event_admin_setting_' . $this->id, array( $this, 'output' ) );
	}

	/**
	 * Get settings fields.
	 *
	 * @return array
	 */
	public function get_settings() {
		return apply_filters( 'event_admin_setting_page_' . $this->id, array() );
	}

	/**
	 * Get settings sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		return apply_filters( 'event_admin_setting_page_' . $this->id . '_section', array() );
	}

	/**
	 * Add settings tab.
	 *
	 * @param array $tabs Tabs.
	 *
	 * @return array
	 */
	public function add_setting_tab( $tabs ) {
		$tabs[ $this->id ] = $this->label;

		return $tabs;
	}

	/**
	 * Output section links.
	 *
	 * @param string $tab Current tab.
	 *
	 * @return void
	 */
	public function output_section( $tab ) {
		global $current_section;

		$sections = $this->get_sections();
		if ( ! $sections ) {
			return;
		}

		echo '<ul class="subsubsub">';
		$array_keys = array_keys( $sections );
		foreach ( $sections as $id => $label ) {
			$url   = admin_url( 'admin.php?page=tp-event-setting&tab=' . rawurlencode( $this->id ) . '&section=' . sanitize_title( $id ) );
			$class = $current_section === $id ? 'current' : '';

			echo '<li><a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</a> ' . ( end( $array_keys ) === $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}

	/**
	 * Output settings fields.
	 *
	 * @param string $tab Current tab.
	 *
	 * @return void
	 */
	public function output( $tab ) {
		SettingsManager::output_fields( $this->get_settings() );
	}

	/**
	 * Save settings fields.
	 *
	 * @return void
	 */
	public function save() {
		SettingsManager::save_fields( $this->get_settings() );
	}
}
