<?php
/**
 * Pet Compatibility Block — Good with dogs, cats, children
 *
 * Displays tristate compatibility indicators (yes/no/unknown) with
 * animal/child icons and colored status badges.
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
			'class' => 'pet-compat pet-compat--placeholder',
		) );
		?>
		<div <?php echo $wrapper_attributes; ?>>
			<div class="pet-compat__placeholder">
				<p><?php esc_html_e( 'Pet Compatibility', 'petstablished-sync' ); ?></p>
				<small><?php esc_html_e( 'Good with dogs, cats, and children. Requires pet context.', 'petstablished-sync' ); ?></small>
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

// Build compatibility list — entity field names, not raw API keys.
$compat_defs = array(
	array( 'toggle' => 'showDogs', 'label' => __( 'Dogs', 'petstablished-sync' ), 'key' => 'ok_with_dogs', 'icon' => 'dog' ),
	array( 'toggle' => 'showCats', 'label' => __( 'Cats', 'petstablished-sync' ), 'key' => 'ok_with_cats', 'icon' => 'cat' ),
	array( 'toggle' => 'showKids', 'label' => __( 'Children', 'petstablished-sync' ), 'key' => 'ok_with_kids', 'icon' => 'child' ),
);

/**
 * Normalize a tristate value to 'yes', 'no', or 'unknown'.
 */
$resolve_tristate = function ( $value ): ?string {
	if ( $value === '' || $value === null ) {
		return null; // No data — skip entirely.
	}
	if ( is_bool( $value ) ) {
		return $value ? 'yes' : 'no';
	}
	$lower = strtolower( (string) $value );
	if ( $lower === 'yes' || $lower === 'true' || $lower === '1' ) {
		return 'yes';
	}
	if ( $lower === 'no' || $lower === 'false' || $lower === '0' ) {
		return 'no';
	}
	return 'unknown';
};

$items = array();
foreach ( $compat_defs as $def ) {
	if ( ! ( $attributes[ $def['toggle'] ] ?? true ) ) {
		continue;
	}
	$status = $resolve_tristate( $pet[ $def['key'] ] ?? '' );
	if ( $status === null ) {
		continue; // No data for this field.
	}
	$items[] = array(
		'label'  => $def['label'],
		'icon'   => $def['icon'],
		'status' => $status,
	);
}

if ( empty( $items ) ) {
	return;
}

// Status labels for screen readers.
$status_labels = array(
	'yes'     => __( 'Yes', 'petstablished-sync' ),
	'no'      => __( 'No', 'petstablished-sync' ),
	'unknown' => __( 'Unknown', 'petstablished-sync' ),
);

// Status icons.
$status_icons = array(
	'yes'     => array( 'name' => 'check',  'attrs' => array( 'width' => 16, 'height' => 16, 'stroke-width' => 3 ) ),
	'no'      => array( 'name' => 'x',      'attrs' => array( 'width' => 16, 'height' => 16, 'stroke-width' => 3 ) ),
	'unknown' => array( 'name' => 'minus',  'attrs' => array( 'width' => 16, 'height' => 16, 'stroke-width' => 3 ) ),
);

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'pet-compat',
) );
?>
<ul <?php echo $wrapper_attributes; ?> role="list">
	<?php foreach ( $items as $item ) : ?>
		<li class="pet-compat__item pet-compat__item--<?php echo esc_attr( $item['status'] ); ?>">
			<span class="pet-compat__icon" aria-hidden="true">
				<?php Petstablished_Icons::render( $item['icon'], array( 'width' => 20, 'height' => 20 ) ); ?>
			</span>
			<span class="pet-compat__label"><?php echo esc_html( $item['label'] ); ?></span>
			<span class="pet-compat__status" aria-label="<?php echo esc_attr( $status_labels[ $item['status'] ] ); ?>">
				<?php
				$icon = $status_icons[ $item['status'] ];
				Petstablished_Icons::render( $icon['name'], $icon['attrs'] );
				?>
			</span>
		</li>
	<?php endforeach; ?>
</ul>
