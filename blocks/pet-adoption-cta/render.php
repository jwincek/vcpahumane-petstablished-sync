<?php
/**
 * Pet Adoption CTA Block — Adoption fee + application action
 *
 * Supports two form modes:
 *   - petstablished: links to the Petstablished adoption form URL (per-pet, from API)
 *   - pdf: renders a download link for an editor-selected PDF from the media library
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

// Shared helper: Abilities API → Hydrator fallback (per-request cached).
$pet = petstablished_get_pet( (int) $post_id );
if ( ! $pet ) {
	return;
}

$pet_name  = $pet['name'] ?? get_the_title( $post_id );
$form_mode = $attributes['formMode'] ?? 'petstablished';
$show_fee  = $attributes['showFee'] ?? true;
$show_note = $attributes['showNote'] ?? true;

// Adoption fee.
$fee_formatted = $pet['adoption_fee_formatted'] ?? '';
$raw_fee       = $pet['adoption_fee'] ?? '';
$has_fee       = $show_fee && ! empty( $raw_fee );

// Adoption action — depends on form mode.
$has_action   = false;
$action_html  = '';

if ( $form_mode === 'pdf' ) {
	$pdf_id = (int) ( $attributes['pdfAttachmentId'] ?? 0 );
	if ( $pdf_id && get_post( $pdf_id ) ) {
		$pdf_url      = wp_get_attachment_url( $pdf_id );
		$pdf_meta     = wp_get_attachment_metadata( $pdf_id );
		$pdf_filename = basename( get_attached_file( $pdf_id ) );
		$pdf_filesize = size_format( filesize( get_attached_file( $pdf_id ) ), 1 );
		$pdf_text     = $attributes['pdfButtonText'] ?? __( 'Download Adoption Application', 'petstablished-sync' );

		if ( $pdf_url ) {
			$has_action = true;
			ob_start();
			?>
			<div class="pet-adoption-cta__file">
				<a
					href="<?php echo esc_url( $pdf_url ); ?>"
					class="pet-adoption-cta__action-btn"
					download
				>
					<?php Petstablished_Icons::render( 'download', array( 'width' => 18, 'height' => 18 ) ); ?>
					<span><?php echo esc_html( $pdf_text ); ?></span>
				</a>
				<span class="pet-adoption-cta__file-meta">
					<?php echo esc_html( $pdf_filename ); ?>
					<span class="pet-adoption-cta__file-size">(<?php echo esc_html( $pdf_filesize ); ?>)</span>
				</span>
			</div>
			<?php
			$action_html = ob_get_clean();
		}
	}
} else {
	// Petstablished mode — link to external adoption form.
	$adoption_url = $pet['adoption_form_url'] ?? '';
	$button_text  = $attributes['buttonText'] ?? __( 'Start Adoption Application', 'petstablished-sync' );

	if ( $adoption_url ) {
		$has_action = true;
		ob_start();
		?>
		<a
			href="<?php echo esc_url( $adoption_url ); ?>"
			class="pet-adoption-cta__action-btn"
			target="_blank"
			rel="noopener noreferrer"
		>
			<span><?php echo esc_html( $button_text ); ?></span>
			<?php Petstablished_Icons::render( 'external-link', array( 'width' => 18, 'height' => 18 ) ); ?>
		</a>
		<?php
		$action_html = ob_get_clean();
	}
}

// Don't render if nothing to show.
if ( ! $has_fee && ! $has_action ) {
	return;
}

// Note text.
$note_text = '';
if ( $show_note ) {
	$note_text = $attributes['noteText'] ?? __( 'The adoption fee helps cover vaccinations, spay/neuter surgery, microchip, and initial veterinary care.', 'petstablished-sync' );
}

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'pet-adoption-cta',
) );
?>
<section <?php echo $wrapper_attributes; ?>>
	<div class="pet-adoption-cta__card">
		<div class="pet-adoption-cta__content">
			<h2 class="pet-adoption-cta__title">
				<?php echo esc_html( $pet['adoption_title'] ?? sprintf( __( 'Adopt %s', 'petstablished-sync' ), $pet_name ) ); ?>
			</h2>

			<?php if ( $has_fee ) : ?>
				<p class="pet-adoption-cta__fee">
					<?php esc_html_e( 'Adoption Fee:', 'petstablished-sync' ); ?>
					<strong><?php echo esc_html( $fee_formatted ); ?></strong>
				</p>
			<?php endif; ?>

			<?php if ( $note_text ) : ?>
				<p class="pet-adoption-cta__note">
					<?php echo esc_html( $note_text ); ?>
				</p>
			<?php endif; ?>
		</div>

		<?php if ( $has_action ) : ?>
			<div class="pet-adoption-cta__actions">
				<?php echo $action_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above with proper escaping. ?>
			</div>
		<?php endif; ?>
	</div>
</section>