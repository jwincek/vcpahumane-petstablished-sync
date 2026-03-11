<?php
/**
 * Pet Health Block — Vaccinations, spay/neuter, housebroken, special needs
 *
 * Displays tristate health/care indicators with check/x/info icons.
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
			'class' => 'pet-health pet-health--placeholder',
		) );
		?>
		<div <?php echo $wrapper_attributes; ?>>
			<div class="pet-health__placeholder">
				<p><?php esc_html_e( 'Pet Health', 'petstablished-sync' ); ?></p>
				<small><?php esc_html_e( 'Vaccinations, spay/neuter, and more. Requires pet context.', 'petstablished-sync' ); ?></small>
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

// Health items — uses entity field names (not raw API keys).
$health_defs = array(
	array( 'toggle' => 'showSpayedNeutered', 'label' => __( 'Spayed/Neutered', 'petstablished-sync' ),   'key' => 'spayed_neutered' ),
	array( 'toggle' => 'showVaccinations',   'label' => __( 'Vaccinations Current', 'petstablished-sync' ), 'key' => 'shots_current' ),
	array( 'toggle' => 'showHousebroken',    'label' => __( 'House Trained', 'petstablished-sync' ),      'key' => 'housebroken' ),
	array( 'toggle' => 'showSpecialNeeds',   'label' => __( 'Special Needs', 'petstablished-sync' ),      'key' => 'special_needs' ),
);

/**
 * Normalize a tristate value to 'yes', 'no', or 'unknown'.
 */
$resolve_tristate = function ( $value ): ?string {
	if ( $value === '' || $value === null ) {
		return null;
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
foreach ( $health_defs as $def ) {
	if ( ! ( $attributes[ $def['toggle'] ] ?? true ) ) {
		continue;
	}
	$status = $resolve_tristate( $pet[ $def['key'] ] ?? '' );
	if ( $status === null ) {
		continue;
	}

	// For special needs, append detail text if available.
	$label = $def['label'];
	if ( $def['key'] === 'special_needs' && $status === 'yes' ) {
		$detail = trim( $pet['special_needs_detail'] ?? '' );
		if ( $detail ) {
			$label = sprintf(
				/* translators: 1: "Special Needs" label, 2: detail text (e.g. "FeLV+") */
				__( '%1$s: %2$s', 'petstablished-sync' ),
				$label,
				$detail
			);
		}
	}

	$items[] = array(
		'label'  => $label,
		'status' => $status,
	);
}

if ( empty( $items ) ) {
	return;
}

// Status icons — different from compatibility (uses filled circle variants).
$status_icons = array(
	'yes'     => array( 'name' => 'check-circle', 'attrs' => array( 'width' => 18, 'height' => 18, 'stroke-width' => 2.5 ) ),
	'no'      => array( 'name' => 'x-circle',     'attrs' => array( 'width' => 18, 'height' => 18, 'stroke-width' => 2.5 ) ),
	'unknown' => array( 'name' => 'info',          'attrs' => array( 'width' => 18, 'height' => 18, 'stroke-width' => 2.5 ) ),
);

$status_labels = array(
	'yes'     => __( 'Yes', 'petstablished-sync' ),
	'no'      => __( 'No', 'petstablished-sync' ),
	'unknown' => __( 'Unknown', 'petstablished-sync' ),
);

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'pet-health',
) );
?>
<ul <?php echo $wrapper_attributes; ?> role="list">
	<?php foreach ( $items as $item ) : ?>
		<li class="pet-health__item pet-health__item--<?php echo esc_attr( $item['status'] ); ?>">
			<span class="pet-health__icon" aria-hidden="true">
				<?php
				$icon = $status_icons[ $item['status'] ];
				Petstablished_Icons::render( $icon['name'], $icon['attrs'] );
				?>
			</span>
			<span class="pet-health__label"><?php echo esc_html( $item['label'] ); ?></span>
			<span class="screen-reader-text"><?php echo esc_html( $status_labels[ $item['status'] ] ); ?></span>
		</li>
	<?php endforeach; ?>
</ul>
