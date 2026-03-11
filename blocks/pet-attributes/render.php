<?php
/**
 * Pet Attributes Block — Definition list of pet characteristics
 *
 * Displays breed, age, sex, size, color, coat, coat pattern, and weight
 * in a responsive grid of label/value pairs.
 *
 * @package Petstablished_Sync
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id   = $block->context['postId'] ?? get_the_ID();
$is_editor = defined( 'REST_REQUEST' ) && REST_REQUEST;

if ( ! $post_id || 'pet' !== get_post_type( $post_id ) ) {
	if ( $is_editor ) {
		$wrapper_attributes = get_block_wrapper_attributes( array(
			'class' => 'pet-attributes pet-attributes--placeholder',
		) );
		?>
		<div <?php echo $wrapper_attributes; ?>>
			<div class="pet-attributes__placeholder">
				<p><?php esc_html_e( 'Pet Attributes', 'petstablished-sync' ); ?></p>
				<small><?php esc_html_e( 'Breed, age, size, and more. Requires pet context.', 'petstablished-sync' ); ?></small>
			</div>
		</div>
		<?php
	}
	return;
}

// Load pet data via Abilities API.
$pet = null;
$ability = function_exists( 'wp_get_ability' ) ? wp_get_ability( 'petstablished/get-pet' ) : null;
if ( $ability ) {
	$result = $ability->execute( [ 'id' => (int) $post_id ] );
	if ( ! is_wp_error( $result ) ) {
		$pet = $result;
	}
}
if ( ! $pet ) {
	$pet = \Petstablished\Core\Pet_Hydrator::get( $post_id );
}
if ( ! $pet ) {
	return;
}

// Build attribute list — each entry references an entity field key.
// Only attributes whose toggle is enabled are included.
$attr_defs = array(
	array( 'toggle' => 'showBreed',       'label' => __( 'Breed', 'petstablished-sync' ),        'key' => 'breed' ),
	array( 'toggle' => 'showAge',         'label' => __( 'Age', 'petstablished-sync' ),          'key' => 'numerical_age', 'fallback' => 'age' ),
	array( 'toggle' => 'showSex',         'label' => __( 'Sex', 'petstablished-sync' ),          'key' => 'sex' ),
	array( 'toggle' => 'showSize',        'label' => __( 'Size', 'petstablished-sync' ),         'key' => 'size' ),
	array( 'toggle' => 'showColor',       'label' => __( 'Color', 'petstablished-sync' ),        'key' => 'color' ),
	array( 'toggle' => 'showCoat',        'label' => __( 'Coat', 'petstablished-sync' ),         'key' => 'coat' ),
	array( 'toggle' => 'showCoatPattern', 'label' => __( 'Coat Pattern', 'petstablished-sync' ), 'key' => 'coat_pattern' ),
	array( 'toggle' => 'showWeight',      'label' => __( 'Weight', 'petstablished-sync' ),       'key' => 'weight' ),
);

$items = array();
foreach ( $attr_defs as $def ) {
	if ( ! ( $attributes[ $def['toggle'] ] ?? true ) ) {
		continue;
	}
	$value = $pet[ $def['key'] ] ?? '';
	if ( empty( $value ) && ! empty( $def['fallback'] ) ) {
		$value = $pet[ $def['fallback'] ] ?? '';
	}
	if ( ! empty( $value ) ) {
		$items[] = array(
			'label' => $def['label'],
			'value' => $value,
		);
	}
}

if ( empty( $items ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'pet-attributes',
) );
?>
<dl <?php echo $wrapper_attributes; ?>>
	<?php foreach ( $items as $item ) : ?>
		<div class="pet-attributes__item">
			<dt class="pet-attributes__label"><?php echo esc_html( $item['label'] ); ?></dt>
			<dd class="pet-attributes__value"><?php echo esc_html( $item['value'] ); ?></dd>
		</div>
	<?php endforeach; ?>
</dl>
