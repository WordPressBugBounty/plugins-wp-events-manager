<?php
/**
 * Template for displaying form to search events.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/search-form.php
 *
 * @version     2.1
 * @package     WPEMS/Templates
 * @category    Templates
 * @author      Thimpress, leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;
?>

<?php
if ( ! ( is_post_type_archive( 'tp_event' ) ) ) {
	return;
}
?>
<form method="get" name="search-events" class="search-events-form">
	<input type="text" name="s" class="search-events-input" value="<?php echo esc_attr( $s ); ?>" placeholder="<?php esc_attr_e( 'Search events...', 'wp-events-manager' ); ?>"/>
	<input type="hidden" name="ref" value="events"/>
	<button class="search-event-button"><?php esc_html_e( 'Search', 'wp-events-manager' ); ?></button>
</form>
