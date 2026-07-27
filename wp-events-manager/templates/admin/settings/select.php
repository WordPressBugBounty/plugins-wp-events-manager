<?php
/**
 * WP Events Manager Select setting view
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/View
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$selected = $field['value'];
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
		<?php if ( isset( $field['options'] ) ) : ?>
			<select name="<?php echo isset( $field['field_name'] ) ? esc_attr( $field['field_name'] ) : ''; ?><?php echo $field['type'] === 'multiselect' ? '[]' : ''; ?>"
					id="<?php echo isset( $field['id'] ) ? esc_attr( $field['id'] ) : ''; ?>"
				<?php echo ( $field['type'] === 'multiselect' ) ? 'multiple="multiple"' : ''; ?>
			>
				<?php foreach ( $field['options'] as $val => $text ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>"
						<?php echo ( is_array( $selected ) && in_array( $val, $selected, true ) ) || $selected === $val ? ' selected' : ''; ?>
					>
						<?php echo esc_html( $text ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( isset( $field['desc'] ) ) : ?>
				<div class="description"><?php echo esc_html( $field['desc'] ); ?></div>
			<?php endif; ?>
		<?php endif; ?>
	</td>
</tr>
