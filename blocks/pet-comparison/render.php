<?php
/**
 * Pet Comparison Block
 *
 * Displays a side-by-side comparison of selected pets.
 * Uses card layout on mobile, table layout on desktop.
 * Fully integrated with Interactivity API for reactive updates.
 *
 * @package Petstablished_Sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only render if compare parameter is in URL.
if ( ! isset( $_GET['compare'] ) || empty( $_GET['compare'] ) ) {
	return;
}

// Get comparison IDs from URL.
$comparison_ids = Petstablished_Helpers::get_comparison();

// If no valid pets to compare, show message and link back.
if ( empty( $comparison_ids ) ) {
	$wrapper_attributes = get_block_wrapper_attributes( array(
		'class'               => 'pet-comparison pet-comparison--empty',
		'data-wp-interactive' => 'petstablished',
	) );
	?>
	<div <?php echo $wrapper_attributes; ?>>
		<div class="pet-comparison__empty">
			<?php Petstablished_Icons::render( 'compare-grid', array( 'width' => 48, 'height' => 48, 'stroke-width' => 1.5 ) ); ?>
			<h3><?php esc_html_e( 'No pets to compare', 'petstablished-sync' ); ?></h3>
			<p><?php esc_html_e( 'The pets in this comparison link may no longer be available.', 'petstablished-sync' ); ?></p>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'pet' ) ); ?>" class="pet-comparison__browse-btn">
				<?php esc_html_e( 'Browse Available Pets', 'petstablished-sync' ); ?>
			</a>
		</div>
	</div>
	<?php
	return;
}

// Fetch the pets using the comparison hydration profile.
$pets_query = get_posts( array(
	'post_type'      => 'pet',
	'post__in'       => $comparison_ids,
	'posts_per_page' => 4,
	'orderby'        => 'post__in',
	'post_status'    => 'publish',
) );

$favorites = Petstablished_Helpers::get_favorites();

$pets = array_map( function( $post ) {
	$data = \Petstablished\Core\Pet_Hydrator::hydrate( $post, 'comparison' );
	return $data ?: [];
}, $pets_query );

if ( empty( $pets ) ) {
	return;
}

// Block attributes for display options.
$show_image         = $attributes['showImage'] ?? true;
$show_breed         = $attributes['showBreed'] ?? true;
$show_age           = $attributes['showAge'] ?? true;
$show_sex           = $attributes['showSex'] ?? true;
$show_size          = $attributes['showSize'] ?? true;
$show_compatibility = $attributes['showCompatibility'] ?? true;
$show_adoption_fee  = $attributes['showAdoptionFee'] ?? true;

// Normalize display values.
foreach ( $pets as &$pet ) {
	$pet['compatibility_display'] = $pet['compatibility'] ?: '—';
	$pet['fee_display']           = $pet['adoption_fee_formatted'] ?: '—';
	$pet['special_needs_display'] = ! empty( $pet['special_needs'] )
		? ( $pet['special_needs_detail'] ?: __( 'Yes', 'petstablished-sync' ) )
		: __( 'None', 'petstablished-sync' );
	$pet['shots_display']         = ! empty( $pet['shots_current'] ) ? __( 'Current', 'petstablished-sync' ) : '—';
	$pet['fixed_display']         = ! empty( $pet['spayed_neutered'] ) ? __( 'Yes', 'petstablished-sync' ) : '—';
	$pet['housebroken_display']   = ! empty( $pet['housebroken'] ) ? __( 'Yes', 'petstablished-sync' ) : '—';
	$pet['coat_display']          = $pet['coat_length'] ?? '—';
	$pet['shedding_display']      = $pet['shedding'] ?? '—';
}
unset( $pet );

// Build comparison attributes — each entry maps a key to a display label.
$comparison_attrs = array();
if ( $show_breed )         $comparison_attrs[] = array( 'key' => 'breed',               'label' => __( 'Breed', 'petstablished-sync' ) );
if ( $show_age )           $comparison_attrs[] = array( 'key' => 'age',                 'label' => __( 'Age', 'petstablished-sync' ) );
if ( $show_sex )           $comparison_attrs[] = array( 'key' => 'sex',                 'label' => __( 'Sex', 'petstablished-sync' ) );
if ( $show_size )          $comparison_attrs[] = array( 'key' => 'size',                'label' => __( 'Size', 'petstablished-sync' ) );
if ( $show_compatibility ) $comparison_attrs[] = array( 'key' => 'compatibility_display', 'label' => __( 'Good With', 'petstablished-sync' ) );
if ( $show_adoption_fee )  $comparison_attrs[] = array( 'key' => 'fee_display',         'label' => __( 'Adoption Fee', 'petstablished-sync' ) );
// Additional comparison attributes.
$comparison_attrs[] = array( 'key' => 'special_needs_display', 'label' => __( 'Special Needs', 'petstablished-sync' ) );
$comparison_attrs[] = array( 'key' => 'shots_display',         'label' => __( 'Vaccinations', 'petstablished-sync' ) );
$comparison_attrs[] = array( 'key' => 'fixed_display',         'label' => __( 'Spayed/Neutered', 'petstablished-sync' ) );
$comparison_attrs[] = array( 'key' => 'housebroken_display',   'label' => __( 'Housebroken', 'petstablished-sync' ) );

// Pre-compute difference highlighting — for each attribute, check if all
// pets have the same value. If values differ, the row gets a highlight class.
$attr_differs = array();
foreach ( $comparison_attrs as $attr ) {
	$values = array_map( fn( $p ) => strtolower( trim( (string) ( $p[ $attr['key'] ] ?? '—' ) ) ), $pets );
	$attr_differs[ $attr['key'] ] = count( array_unique( $values ) ) > 1;
}

// Context for Interactivity - include pets data for reactive updates.
$context = array(
	'comparisonPets' => $pets,
	'archiveUrl'     => get_post_type_archive_link( 'pet' ),
);

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class'                   => 'pet-comparison',
	'data-wp-interactive'     => 'petstablished/comparison',
	'data-wp-context'         => wp_json_encode( $context ),
	'data-wp-init'            => 'callbacks.init',
	'style'                   => '--comparison-columns: ' . count( $pets ),
) );
?>

<div <?php echo $wrapper_attributes; ?>>
	<!-- Header -->
	<div class="pet-comparison__header">
		<h2 class="pet-comparison__title">
			<?php esc_html_e( 'Compare Pets', 'petstablished-sync' ); ?>
			<span class="pet-comparison__count">(<span data-wp-text="petstablished::state.comparisonCount"><?php echo count( $pets ); ?></span>)</span>
		</h2>
		<div class="pet-comparison__actions">
			<button
				type="button"
				class="pet-comparison__action-btn pet-comparison__action-btn--share"
				data-wp-on--click="actions.copyCompareUrl"
			>
				<?php Petstablished_Icons::render( 'share', array( 'width' => 16, 'height' => 16 ) ); ?>
				<span><?php esc_html_e( 'Share', 'petstablished-sync' ); ?></span>
			</button>
			<button
				type="button"
				class="pet-comparison__action-btn pet-comparison__action-btn--clear"
				data-wp-on--click="actions.clearAndRedirect"
			>
				<?php Petstablished_Icons::render( 'trash', array( 'width' => 16, 'height' => 16 ) ); ?>
				<span><?php esc_html_e( 'Clear All', 'petstablished-sync' ); ?></span>
			</button>
		</div>
	</div>

	<!-- Mobile: Card Layout -->
	<div class="pet-comparison__cards">
		<?php foreach ( $pets as $pet ) : 
			$pet_context = array(
				'petId'   => $pet['id'],
				'petName' => $pet['name'],
			);
			$is_favorited = in_array( $pet['id'], $favorites, true );
		?>
			<article 
				class="pet-comparison__card"
				data-wp-context='<?php echo wp_json_encode( $pet_context ); ?>'
			>
				<button
					type="button"
					class="pet-comparison__card-remove"
					data-wp-on--click="actions.removeAndRefresh"
					aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s', 'petstablished-sync' ), $pet['name'] ) ); ?>"
				>
					<?php Petstablished_Icons::render( 'x', array( 'width' => 16, 'height' => 16 ) ); ?>
				</button>

				<?php if ( $show_image && $pet['image'] ) : ?>
					<a href="<?php echo esc_url( $pet['url'] ); ?>" class="pet-comparison__card-image-link">
						<img 
							src="<?php echo esc_url( $pet['image'] ); ?>" 
							alt="<?php echo esc_attr( $pet['name'] ); ?>"
							class="pet-comparison__card-image"
							loading="lazy"
						>
					</a>
				<?php endif; ?>

				<div class="pet-comparison__card-content">
					<h3 class="pet-comparison__card-name">
						<a href="<?php echo esc_url( $pet['url'] ); ?>">
							<?php echo esc_html( $pet['name'] ); ?>
						</a>
					</h3>

					<dl class="pet-comparison__card-attrs">
						<?php foreach ( $comparison_attrs as $attr ) : 
							$value   = $pet[ $attr['key'] ] ?? '—';
							$differs = $attr_differs[ $attr['key'] ] ?? false;
						?>
							<div class="pet-comparison__card-attr<?php echo $differs ? ' pet-comparison__card-attr--differs' : ''; ?>">
								<dt><?php echo esc_html( $attr['label'] ); ?></dt>
								<dd><?php echo esc_html( $value ?: '—' ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>

					<div class="pet-comparison__card-actions">
						<a href="<?php echo esc_url( $pet['url'] ); ?>" class="pet-comparison__card-btn pet-comparison__card-btn--primary">
							<?php esc_html_e( 'View Details', 'petstablished-sync' ); ?>
						</a>
						<button
							type="button"
							class="pet-comparison__card-btn pet-comparison__card-btn--favorite<?php echo $is_favorited ? ' is-favorited' : ''; ?>"
							data-wp-on--click="actions.toggleFavorite"
							data-wp-bind--aria-pressed="state.isFavorited"
							data-wp-class--is-favorited="state.isFavorited"
							aria-pressed="<?php echo $is_favorited ? 'true' : 'false'; ?>"
						>
							<?php
								echo Petstablished_Icons::get_heart_interactive(
									array( 'width' => 18, 'height' => 18 ),
									"state.isFavorited ? 'currentColor' : 'none'",
									$is_favorited ? 'currentColor' : 'none'
								);
							?>
						</button>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<!-- Desktop: Table Layout -->
	<div class="pet-comparison__table-wrapper">
		<table class="pet-comparison__table" role="table">
			<thead>
				<tr>
					<th class="pet-comparison__th-label">
						<span class="screen-reader-text"><?php esc_html_e( 'Attribute', 'petstablished-sync' ); ?></span>
					</th>
					<?php foreach ( $pets as $pet ) : 
						$pet_context = array(
							'petId'   => $pet['id'],
							'petName' => $pet['name'],
						);
					?>
						<th 
							class="pet-comparison__th-pet"
							data-wp-context='<?php echo wp_json_encode( $pet_context ); ?>'
						>
							<?php if ( $show_image && $pet['image'] ) : ?>
								<a href="<?php echo esc_url( $pet['url'] ); ?>" class="pet-comparison__th-image-link">
									<img 
										src="<?php echo esc_url( $pet['image'] ); ?>" 
										alt="<?php echo esc_attr( sprintf( __( 'Photo of %s', 'petstablished-sync' ), $pet['name'] ) ); ?>"
										class="pet-comparison__th-image"
									>
								</a>
							<?php endif; ?>
							<span class="pet-comparison__th-name">
								<a href="<?php echo esc_url( $pet['url'] ); ?>">
									<?php echo esc_html( $pet['name'] ); ?>
								</a>
							</span>
							<button
								type="button"
								class="pet-comparison__th-remove"
								data-wp-on--click="actions.removeAndRefresh"
								aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s', 'petstablished-sync' ), $pet['name'] ) ); ?>"
							>
								<?php Petstablished_Icons::render( 'x', array( 'width' => 12, 'height' => 12, 'stroke-width' => 2.5 ) ); ?>
							</button>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $comparison_attrs as $attr ) :
					$differs = $attr_differs[ $attr['key'] ] ?? false;
				?>
					<tr class="<?php echo $differs ? 'pet-comparison__tr--differs' : ''; ?>">
						<th class="pet-comparison__td-label" scope="row">
							<?php echo esc_html( $attr['label'] ); ?>
							<?php if ( $differs ) : ?>
								<span class="pet-comparison__diff-indicator" aria-hidden="true" title="<?php esc_attr_e( 'Values differ', 'petstablished-sync' ); ?>"></span>
							<?php endif; ?>
						</th>
						<?php foreach ( $pets as $pet ) : 
							$value = $pet[ $attr['key'] ] ?? '—';
						?>
							<td class="pet-comparison__td-value<?php echo $differs ? ' pet-comparison__td-value--differs' : ''; ?>">
								<?php echo esc_html( $value ?: '—' ); ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>

				<!-- Actions row -->
				<tr class="pet-comparison__tr-actions">
					<th class="pet-comparison__td-label" scope="row">
						<span class="screen-reader-text"><?php esc_html_e( 'Actions', 'petstablished-sync' ); ?></span>
					</th>
					<?php foreach ( $pets as $pet ) : 
						$pet_context = array(
							'petId'   => $pet['id'],
							'petName' => $pet['name'],
						);
						$is_favorited = in_array( $pet['id'], $favorites, true );
					?>
						<td 
							class="pet-comparison__td-actions"
							data-wp-context='<?php echo wp_json_encode( $pet_context ); ?>'
						>
							<a href="<?php echo esc_url( $pet['url'] ); ?>" class="pet-comparison__btn-view">
								<?php esc_html_e( 'View', 'petstablished-sync' ); ?>
							</a>
							<button
								type="button"
								class="pet-comparison__btn-fav<?php echo $is_favorited ? ' is-favorited' : ''; ?>"
								data-wp-on--click="actions.toggleFavorite"
								data-wp-bind--aria-pressed="state.isFavorited"
								data-wp-class--is-favorited="state.isFavorited"
								aria-pressed="<?php echo $is_favorited ? 'true' : 'false'; ?>"
								aria-label="<?php echo esc_attr( sprintf( __( 'Favorite %s', 'petstablished-sync' ), $pet['name'] ) ); ?>"
							>
								<?php
									echo Petstablished_Icons::get_heart_interactive(
										array( 'width' => 16, 'height' => 16 ),
										"state.isFavorited ? 'currentColor' : 'none'",
										$is_favorited ? 'currentColor' : 'none'
									);
								?>
							</button>
						</td>
					<?php endforeach; ?>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Footer -->
	<div class="pet-comparison__footer">
		<?php
		// If the user arrived from a single pet page, show a return link.
		// The compare bar passes ?from=<pet-url> when on a single-pet template.
		$from_url = isset( $_GET['from'] ) ? esc_url_raw( $_GET['from'] ) : '';
		if ( $from_url && wp_http_validate_url( $from_url ) ) :
			// Resolve the pet name from the URL — find the post by its permalink.
			$from_post_id = url_to_postid( $from_url );
			$from_pet_name = $from_post_id ? get_the_title( $from_post_id ) : '';
		?>
			<a href="<?php echo esc_url( $from_url ); ?>" class="pet-comparison__back-link pet-comparison__back-link--pet">
				<?php Petstablished_Icons::render( 'back', array( 'width' => 16, 'height' => 16 ) ); ?>
				<?php
				if ( $from_pet_name ) {
					printf(
						/* translators: %s: pet name */
						esc_html__( 'Continue viewing %s', 'petstablished-sync' ),
						esc_html( $from_pet_name )
					);
				} else {
					esc_html_e( 'Back to pet profile', 'petstablished-sync' );
				}
				?>
			</a>
		<?php endif; ?>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'pet' ) ); ?>" class="pet-comparison__back-link">
			<?php Petstablished_Icons::render( 'back', array( 'width' => 16, 'height' => 16 ) ); ?>
			<?php esc_html_e( 'Back to all pets', 'petstablished-sync' ); ?>
		</a>
	</div>

	<!-- Toast notification -->
	<div 
		class="pet-comparison__toast"
		data-wp-bind--hidden="petstablished::state.noNotification"
		data-wp-text="petstablished::state.notification"
		role="status"
		aria-live="polite"
		hidden
	></div>
</div>