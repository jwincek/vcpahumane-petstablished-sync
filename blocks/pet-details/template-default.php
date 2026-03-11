<?php
/**
 * Pet Details - Default Template
 *
 * This template is rendered when the pet-details block has no InnerBlocks content.
 * It provides a complete, functional layout using the same patterns that users
 * would compose with InnerBlocks + Block Bindings.
 *
 * Variables available from parent render.php:
 * - $pet (array) - Formatted pet data
 * - $gallery (array) - Gallery images
 * - $layout (string) - 'sidebar' or 'stacked'
 * - $show_gallery (bool)
 * - $show_actions (bool)
 *
 * @package Petstablished_Sync
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$archive_url = get_post_type_archive_link( 'pet' );

// Quick facts for tagline.
$quick_facts = array_filter( array(
	$pet['animal'],
	$pet['breed'],
	$pet['age'],
	$pet['sex'],
	$pet['size'],
) );

// About attributes — now sourced directly from hydrated entity (API JSON).
$about_attrs = array(
	array( 'label' => __( 'Breed', 'petstablished-sync' ), 'value' => $pet['breed'] ),
	array( 'label' => __( 'Age', 'petstablished-sync' ), 'value' => $pet['numerical_age'] ?? $pet['age'] ),
	array( 'label' => __( 'Sex', 'petstablished-sync' ), 'value' => $pet['sex'] ),
	array( 'label' => __( 'Size', 'petstablished-sync' ), 'value' => $pet['size'] ),
	array( 'label' => __( 'Color', 'petstablished-sync' ), 'value' => $pet['color'] ?? '' ),
	array( 'label' => __( 'Coat', 'petstablished-sync' ), 'value' => $pet['coat'] ?? '' ),
	array( 'label' => __( 'Coat Pattern', 'petstablished-sync' ), 'value' => $pet['coat_pattern'] ?? '' ),
	array( 'label' => __( 'Weight', 'petstablished-sync' ), 'value' => $pet['weight'] ?? '' ),
);

// Compatibility checks — values are tristate strings from API ("Yes"/"No"/"Not Sure").
$compatibility = array(
	array( 
		'key'   => 'dogs', 
		'label' => __( 'Dogs', 'petstablished-sync' ), 
		'value' => $pet['ok_with_dogs'] ?? '', 
		'icon'  => Petstablished_Icons::get( 'dog' ) 
	),
	array( 
		'key'   => 'cats', 
		'label' => __( 'Cats', 'petstablished-sync' ), 
		'value' => $pet['ok_with_cats'] ?? '', 
		'icon'  => Petstablished_Icons::get( 'cat' ) 
	),
	array( 
		'key'   => 'kids', 
		'label' => __( 'Children', 'petstablished-sync' ), 
		'value' => $pet['ok_with_kids'] ?? '', 
		'icon'  => Petstablished_Icons::get( 'child' ) 
	),
);

// Health items — tristate values from API.
$truthy_values = array( 'yes', '1', 'true' );
$falsy_values  = array( 'no', '0', 'false' );

$health_items = array(
	array( 
		'label' => __( 'Spayed/Neutered', 'petstablished-sync' ), 
		'value' => $pet['spayed_neutered'] ?? '',
	),
	array( 
		'label' => __( 'Vaccinations Current', 'petstablished-sync' ), 
		'value' => $pet['shots_current'] ?? '',
	),
	array( 
		'label' => __( 'House Trained', 'petstablished-sync' ), 
		'value' => $pet['housebroken'] ?? '',
	),
	array( 
		'label' => __( 'Special Needs', 'petstablished-sync' ), 
		'value' => $pet['special_needs'] ?? '',
	),
);

// Special needs detail.
$special_needs_detail = trim( $pet['special_needs_detail'] ?? '' );
$has_special_needs    = in_array( strtolower( (string) ( $pet['special_needs'] ?? '' ) ), $truthy_values, true );

$status_slug  = sanitize_title( $pet['status'] ?? 'unknown' );
$adoption_fee = $pet['adoption_fee'] ?? '';
$adoption_url = $pet['adoption_form_url'] ?? '';
?>

<!-- Breadcrumb Navigation -->
<nav class="pet-details__nav" aria-label="<?php esc_attr_e( 'Breadcrumb', 'petstablished-sync' ); ?>">
	<a href="<?php echo esc_url( $archive_url ); ?>" class="pet-details__back">
		<?php Petstablished_Icons::render( 'arrow-left', array( 'width' => 16, 'height' => 16 ) ); ?>
		<?php esc_html_e( 'Back to All Pets', 'petstablished-sync' ); ?>
	</a>
</nav>

<!-- Main Content Grid -->
<div class="pet-details__content">
	<!-- Gallery Column -->
	<?php if ( $show_gallery ) : ?>
	<div class="pet-details__gallery-col">
		<div class="pet-details__gallery">
			<?php if ( ! empty( $gallery ) ) : ?>
				<figure class="pet-details__main-image-wrap">
					<img 
						src="<?php echo esc_url( $gallery[0]['url'] ); ?>" 
						alt="<?php echo esc_attr( $pet['name'] ); ?>"
						class="pet-details__main-image"
						data-wp-bind--src="state.currentImageUrl"
						data-wp-on--click="actions.openGallery"
					>
					<button 
						type="button" 
						class="pet-details__expand-btn" 
						data-wp-on--click="actions.openGallery" 
						aria-label="<?php esc_attr_e( 'View full size', 'petstablished-sync' ); ?>"
					>
						<?php Petstablished_Icons::render( 'expand', array( 'width' => 20, 'height' => 20 ) ); ?>
					</button>
				</figure>
				<?php if ( count( $gallery ) > 1 ) : ?>
					<div class="pet-details__thumbs" role="listbox" aria-label="<?php esc_attr_e( 'Gallery thumbnails', 'petstablished-sync' ); ?>">
						<?php foreach ( $gallery as $idx => $img ) : ?>
							<button
								type="button"
								class="pet-details__thumb <?php echo $idx === 0 ? 'is-active' : ''; ?>"
								data-wp-on--click="actions.selectImage"
								data-wp-class--is-active="state.isActiveThumb"
								data-wp-context='<?php echo wp_json_encode( array( 'imageIndex' => $idx ) ); ?>'
								role="option"
								aria-selected="<?php echo $idx === 0 ? 'true' : 'false'; ?>"
							>
								<img src="<?php echo esc_url( $img['url'] ); ?>" alt="" loading="lazy">
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="pet-details__no-image">
					<?php Petstablished_Icons::render( 'image-placeholder', array( 'width' => 64, 'height' => 64, 'stroke-width' => 1 ) ); ?>
					<span><?php esc_html_e( 'No photo available', 'petstablished-sync' ); ?></span>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $show_actions ) : ?>
		<!-- Quick Actions -->
		<div class="pet-details__quick-actions">
			<button 
				type="button" 
				class="pet-details__action pet-details__action--favorite" 
				data-wp-on--click="actions.toggleFavorite" 
				data-wp-bind--aria-pressed="state.isFavorited" 
				data-wp-class--is-active="state.isFavorited"
			>
				<?php echo Petstablished_Icons::get_heart_interactive(); ?>
				<span data-wp-text="state.isFavorited ? '<?php esc_attr_e( 'Saved', 'petstablished-sync' ); ?>' : '<?php esc_attr_e( 'Save', 'petstablished-sync' ); ?>'">
					<?php esc_html_e( 'Save', 'petstablished-sync' ); ?>
				</span>
			</button>
			<button 
				type="button" 
				class="pet-details__action pet-details__action--compare" 
				data-wp-on--click="actions.toggleComparison" 
				data-wp-bind--aria-pressed="state.isInComparison" 
				data-wp-class--is-active="state.isInComparison" 
				data-wp-bind--disabled="state.isCompareDisabled"
			>
				<?php Petstablished_Icons::render( 'compare' ); ?>
				<span data-wp-text="state.isInComparison ? '<?php esc_attr_e( 'Comparing', 'petstablished-sync' ); ?>' : '<?php esc_attr_e( 'Compare', 'petstablished-sync' ); ?>'">
					<?php esc_html_e( 'Compare', 'petstablished-sync' ); ?>
				</span>
			</button>
			<button 
				type="button" 
				class="pet-details__action pet-details__action--share" 
				data-wp-on--click="actions.sharePet"
			>
				<?php Petstablished_Icons::render( 'share' ); ?>
				<span><?php esc_html_e( 'Share', 'petstablished-sync' ); ?></span>
			</button>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- Details Column -->
	<div class="pet-details__info-col">
		<!-- Header -->
		<header class="pet-details__header">
			<span class="pet-details__status pet-details__status--<?php echo esc_attr( $status_slug ); ?>">
				<?php echo esc_html( $pet['status'] ); ?>
			</span>
			<h1 class="pet-details__name"><?php echo esc_html( $pet['name'] ); ?></h1>
			<p class="pet-details__tagline"><?php echo esc_html( implode( ' · ', $quick_facts ) ); ?></p>
		</header>

		<!-- About Section -->
		<section class="pet-details__section pet-details__about">
			<h2 class="pet-details__section-title"><?php esc_html_e( 'About', 'petstablished-sync' ); ?></h2>
			<dl class="pet-details__attrs">
				<?php foreach ( $about_attrs as $attr ) : ?>
					<?php if ( ! empty( $attr['value'] ) ) : ?>
						<div class="pet-details__attr">
							<dt><?php echo esc_html( $attr['label'] ); ?></dt>
							<dd><?php echo esc_html( $attr['value'] ); ?></dd>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>
		</section>

		<!-- Compatibility Section -->
		<?php $has_compat = array_filter( $compatibility, fn( $c ) => $c['value'] !== '' && $c['value'] !== null ); ?>
		<?php if ( ! empty( $has_compat ) ) : ?>
		<section class="pet-details__section pet-details__compatibility">
			<h2 class="pet-details__section-title"><?php esc_html_e( 'Good With', 'petstablished-sync' ); ?></h2>
			<ul class="pet-details__compat-list">
				<?php foreach ( $compatibility as $compat ) : ?>
					<?php if ( $compat['value'] !== '' && $compat['value'] !== null ) : 
						$is_yes = is_bool( $compat['value'] ) ? $compat['value'] : ( strtolower( (string) $compat['value'] ) === 'yes' );
						$is_no = is_bool( $compat['value'] ) ? ! $compat['value'] : ( strtolower( (string) $compat['value'] ) === 'no' );
						$status_class = $is_yes ? 'yes' : ( $is_no ? 'no' : 'unknown' );
					?>
					<li class="pet-details__compat-item pet-details__compat-item--<?php echo esc_attr( $status_class ); ?>">
						<span class="pet-details__compat-icon"><?php echo $compat['icon']; ?></span>
						<span class="pet-details__compat-label"><?php echo esc_html( $compat['label'] ); ?></span>
						<span class="pet-details__compat-status">
							<?php if ( $status_class === 'yes' ) Petstablished_Icons::render( 'check', array( 'width' => 16, 'height' => 16, 'stroke-width' => 3 ) ); ?>
							<?php if ( $status_class === 'no' ) Petstablished_Icons::render( 'x', array( 'width' => 16, 'height' => 16, 'stroke-width' => 3 ) ); ?>
							<?php if ( $status_class === 'unknown' ) Petstablished_Icons::render( 'minus', array( 'width' => 16, 'height' => 16, 'stroke-width' => 3 ) ); ?>
						</span>
					</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>

		<!-- Health Section -->
		<?php $has_health = array_filter( $health_items, fn( $h ) => $h['value'] !== '' && $h['value'] !== null ); ?>
		<?php if ( ! empty( $has_health ) ) : ?>
		<section class="pet-details__section pet-details__health">
			<h2 class="pet-details__section-title"><?php esc_html_e( 'Health', 'petstablished-sync' ); ?></h2>
			<ul class="pet-details__health-list">
				<?php foreach ( $health_items as $item ) : ?>
					<?php 
					if ( $item['value'] === '' || $item['value'] === null ) continue;
					$val_lower = strtolower( (string) $item['value'] );
					$status_class = in_array( $val_lower, $truthy_values, true ) ? 'yes' 
						: ( in_array( $val_lower, $falsy_values, true ) ? 'no' : 'unknown' );
					?>
					<li class="pet-details__health-item pet-details__health-item--<?php echo esc_attr( $status_class ); ?>">
						<?php if ( $status_class === 'yes' ) Petstablished_Icons::render( 'check-circle', array( 'width' => 18, 'height' => 18, 'stroke-width' => 2.5 ) ); ?>
						<?php if ( $status_class === 'no' ) Petstablished_Icons::render( 'x-circle', array( 'width' => 18, 'height' => 18, 'stroke-width' => 2.5 ) ); ?>
						<?php if ( $status_class === 'unknown' ) Petstablished_Icons::render( 'info', array( 'width' => 18, 'height' => 18, 'stroke-width' => 2.5 ) ); ?>
						<span><?php echo esc_html( $item['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $has_special_needs && $special_needs_detail ) : ?>
				<div class="pet-details__special-needs-detail">
					<p class="pet-details__special-needs-text">
						<?php Petstablished_Icons::render( 'heart', array( 'width' => 16, 'height' => 16 ) ); ?>
						<?php echo esc_html( $special_needs_detail ); ?>
					</p>
				</div>
			<?php endif; ?>
		</section>
		<?php endif; ?>
	</div>
</div>

<!-- Story/Description Section -->
<?php if ( ! empty( $pet['description'] ) ) : ?>
<section class="pet-details__section pet-details__story">
	<h2 class="pet-details__story-title">
		<?php printf( esc_html__( 'Meet %s', 'petstablished-sync' ), esc_html( $pet['name'] ) ); ?>
	</h2>
	<div class="pet-details__story-content">
		<?php echo wp_kses_post( wpautop( $pet['description'] ) ); ?>
	</div>
</section>
<?php endif; ?>

<!-- Adoption CTA -->
<?php if ( $adoption_fee || $adoption_url ) : ?>
<section class="pet-details__section pet-details__adoption">
	<div class="pet-details__adoption-card">
		<div class="pet-details__adoption-content">
			<h2 class="pet-details__adoption-title">
				<?php printf( esc_html__( 'Adopt %s', 'petstablished-sync' ), esc_html( $pet['name'] ) ); ?>
			</h2>
			<?php if ( $adoption_fee ) : ?>
				<p class="pet-details__adoption-fee">
					<?php esc_html_e( 'Adoption Fee:', 'petstablished-sync' ); ?> 
					<strong>$<?php echo esc_html( number_format( (float) $adoption_fee, 0 ) ); ?></strong>
				</p>
			<?php endif; ?>
			<p class="pet-details__adoption-note">
				<?php esc_html_e( 'The adoption fee helps cover vaccinations, spay/neuter surgery, microchip, and initial veterinary care.', 'petstablished-sync' ); ?>
			</p>
		</div>
		<?php if ( $adoption_url ) : ?>
			<div class="pet-details__adoption-actions">
				<a href="<?php echo esc_url( $adoption_url ); ?>" class="pet-details__adopt-btn" target="_blank" rel="noopener">
					<?php esc_html_e( 'Start Adoption Application', 'petstablished-sync' ); ?>
					<?php Petstablished_Icons::render( 'external-link', array( 'width' => 18, 'height' => 18 ) ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>
