<?php
/**
 * Pet Adoption CTA Block — Card container with InnerBlocks
 *
 * Renders the outer card wrapper. Content (heading, fee, note, action button)
 * is composed from InnerBlocks — primarily native core blocks with Block Bindings
 * and the petstablished/adoption-action child block.
 *
 * @package Petstablished_Sync
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id   = $block->context['postId'] ?? get_the_ID();
$is_editor = defined( 'REST_REQUEST' ) && REST_REQUEST;

if ( ! $post_id || 'pet' !== get_post_type( $post_id ) ) {
	if ( $is_editor ) {
		$wrapper_attributes = get_block_wrapper_attributes( array(
			'class' => 'pet-adoption-cta pet-adoption-cta--placeholder',
		) );
		?>
		<div <?php echo $wrapper_attributes; ?>>
			<div class="pet-adoption-cta__placeholder-inner">
				<p><?php esc_html_e( 'Pet Adoption CTA', 'petstablished-sync' ); ?></p>
				<small><?php esc_html_e( 'Adoption fee and application action. Requires pet context.', 'petstablished-sync' ); ?></small>
			</div>
		</div>
		<?php
	}
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'pet-adoption-cta',
) );
?>
<section <?php echo $wrapper_attributes; ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- InnerBlocks rendered output. ?>
</section>
