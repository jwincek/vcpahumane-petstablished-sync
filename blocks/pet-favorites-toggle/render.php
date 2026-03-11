<?php
/**
 * Pet Favorites Toggle Block
 *
 * v4.2.0: Now opens the favorites modal instead of toggling a grid filter.
 * The block is kept for backward compatibility with existing templates.
 * For new templates, use the pet-favorites-modal block instead.
 *
 * @package Petstablished_Sync
 * @deprecated 4.2.0 Use pet-favorites-modal instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_count = $attributes['showCount'] ?? true;
$favorites  = Petstablished_Helpers::get_favorites();

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class'               => 'pet-favorites-toggle',
	'data-wp-interactive' => 'petstablished',
) );
?>

<div <?php echo $wrapper_attributes; ?>>
	<button
		type="button"
		class="pet-favorites-toggle__btn"
		data-wp-on--click="actions.openFavoritesModal"
		aria-label="<?php esc_attr_e( 'View favorites', 'petstablished-sync' ); ?>"
	>
		<?php echo Petstablished_Icons::get_heart_interactive( 
			array( 'width' => 20, 'height' => 20, 'class' => 'pet-favorites-toggle__icon' ), 
			"petstablished::state.favoritesCount > 0 ? 'currentColor' : 'none'" 
		); ?>

		<span class="pet-favorites-toggle__label">
			<?php esc_html_e( 'Favorites', 'petstablished-sync' ); ?>
		</span>

		<?php if ( $show_count ) : ?>
			<span class="pet-favorites-toggle__count" data-wp-text="state.favoritesCount">
				<?php echo count( $favorites ); ?>
			</span>
		<?php endif; ?>
	</button>
</div>
