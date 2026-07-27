<?php
/**
 * WP Events Manager Yes No setting view
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/View
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$val = $field['value'];
?>
<tr valign="top" <?php echo $field['class'] ? 'class="' . esc_attr( $field['class'] ) . '"' : ''; ?>>
	<th scope="row">
		<?php if ( isset( $field['title'] ) ) : ?>
			<label for="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : ''; ?>">
				<?php echo esc_html( $field['title'] ); ?>
			</label>
		<?php endif; ?>
	</th>
	<td class="event-form-field event-form-field-<?php echo esc_attr( $field['type'] ); ?>">
		<input type="hidden" name="<?php echo isset( $field['field_name'] ) ? esc_attr( $field['field_name'] ) : ''; ?>" value="no" />
		<input type="checkbox" id="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : ''; ?>" name="<?php echo isset( $field['field_name'] ) ? esc_attr( $field['field_name'] ) : ''; ?>" value="yes"
			<?php checked( $val, 'yes' ); ?>
		/>
		<?php if ( isset( $field['desc'] ) ) : ?>
			<div class="description"><?php echo esc_html( $field['desc'] ); ?></div>
		<?php endif; ?>
	</td>
</tr>
