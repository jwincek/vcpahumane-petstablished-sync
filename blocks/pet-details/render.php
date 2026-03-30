<?php
/**
 * Pet Details Block - InnerBlocks + Block Bindings Container
 *
 * Serves as the Interactivity API context provider for pet data.
 * All visual content comes from InnerBlocks — core blocks with
 * bindings (headings, paragraphs, images, buttons) and plugin
 * sub-blocks (pet-gallery, pet-actions, pet-attributes, etc.).
 *
 * The lightbox is owned exclusively by the pet-gallery child block.
 *
 * Example bound content in the template:
 * <!-- wp:heading {"metadata":{"bindings":{"content":{"source":"petstablished/pet-data","args":{"key":"name"}}}}} -->
 * <h1 class="wp-block-heading">Pet Name</h1>
 * <!-- /wp:heading -->
 *
 * @package Petstablished_Sync
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = $block->context['postId'] ?? get_the_ID();

// Editor context detection.
$is_editor = defined( 'REST_REQUEST' ) && REST_REQUEST;

// Check if we have a valid pet.
$has_valid_pet = $post_id && 'pet' === get_post_type( $post_id );

// Editor placeholder when no pet context.
if ( ! $has_valid_pet ) {
	$wrapper_attributes = get_block_wrapper_attributes( array(
		'class' => 'pet-details pet-details--preview',
	) );
	?>
	<article <?php echo $wrapper_attributes; ?>>
		<div class="pet-details__placeholder">
			<?php Petstablished_Icons::render( 'paw', array( 'width' => 48, 'height' => 48, 'stroke-width' => 1.5, 'class' => 'pet-details__placeholder-icon' ) ); ?>
			<p class="pet-details__placeholder-title"><?php esc_html_e( 'Pet Details', 'petstablished-sync' ); ?></p>
			<p class="pet-details__placeholder-text"><?php esc_html_e( 'Add inner blocks and bind them to pet data using the block bindings panel.', 'petstablished-sync' ); ?></p>
			<p class="pet-details__placeholder-hint">
				<?php esc_html_e( 'Source:', 'petstablished-sync' ); ?> <code>petstablished/pet-data</code>
			</p>
		</div>
	</article>
	<?php
	return;
}

// Shared helper: Abilities API → Hydrator fallback (per-request cached).
$pet = petstablished_get_pet( (int) $post_id );

if ( ! $pet ) {
	return;
}

$layout      = $attributes['layout'] ?? 'sidebar';
$archive_url = get_post_type_archive_link( 'pet' );

// Interactivity context — provides reactive state to inner blocks.
// Pet-gallery, pet-actions, and other child blocks read petId from this context.
$context = array(
	'petId'      => $pet['id'],
	'petUrl'     => $pet['url'],
	'petName'    => $pet['name'],
	'archiveUrl' => $archive_url,
);

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class'                     => 'pet-details pet-details--' . $layout,
	'data-wp-interactive'       => 'petstablished',
	'data-wp-context'           => wp_json_encode( $context ),
	'data-wp-class--is-loading' => 'state.isLoading',
	'data-pets-archive-url'     => esc_url( $archive_url ),
) );

?>
<article <?php echo $wrapper_attributes; ?>>
	<?php
	// Render InnerBlocks content. Core blocks with block bindings
	// automatically resolve pet data via the petstablished/pet-data source.
	// Plugin sub-blocks (pet-gallery, pet-actions, pet-attributes, etc.)
	// render via their own render.php, reading postId from block context.
	//
	// The breadcrumb link now uses a Block Binding on the core/paragraph
	// 'url' attribute (source: petstablished/pet-data, key: archive_url)
	// instead of the previous href="#pet-archive" str_replace approach.
	echo $content;
	?>
</article>
