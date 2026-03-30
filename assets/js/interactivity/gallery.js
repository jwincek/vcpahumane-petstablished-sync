/**
 * Pet Gallery Interactivity Store
 *
 * Sole owner of lightbox functionality for pet images.
 * Each gallery instance maintains its own state via context:
 *   - images[]       : Array of { url, alt } objects
 *   - currentIndex   : Currently displayed image index
 *   - isOpen         : Whether the lightbox is visible
 *
 * Keyboard navigation (Escape, ArrowLeft, ArrowRight) is handled via
 * the data-wp-on--keydown directive on the lightbox element itself,
 * which naturally scopes to the focused lightbox without needing
 * document-level listeners.
 *
 * @package Petstablished_Sync
 * @since 3.0.0
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store( 'petstablished/gallery', {
	state: {
		get currentImageUrl() {
			const ctx = getContext();
			return ctx.images?.[ ctx.currentIndex ]?.url || '';
		},

		get currentImageAlt() {
			const ctx = getContext();
			return ctx.images?.[ ctx.currentIndex ]?.alt || '';
		},

		get currentNumber() {
			const ctx = getContext();
			return ( ctx.currentIndex || 0 ) + 1;
		},

		get totalImages() {
			const ctx = getContext();
			return ctx.images?.length || 0;
		},

		get hasMultipleImages() {
			const ctx = getContext();
			return ( ctx.images?.length || 0 ) > 1;
		},
	},

	actions: {
		open() {
			const ctx = getContext();
			const { ref } = getElement();

			// Read the clicked image index from the data-index attribute.
			const clickedIndex = parseInt( ref?.dataset?.index || '0', 10 );
			ctx.currentIndex = clickedIndex;
			ctx.isOpen = true;

			// Remember the trigger so close() can return focus to it.
			ctx._triggerElement = ref;

			// Prevent body scroll while lightbox is open.
			document.body.style.overflow = 'hidden';

			// Focus the lightbox so keyboard events reach its keydown handler.
			requestAnimationFrame( () => {
				ref
					?.closest( '[data-wp-interactive]' )
					?.querySelector( '.pet-gallery__lightbox' )
					?.focus();
			} );
		},

		close() {
			const ctx = getContext();
			const trigger = ctx._triggerElement;
			ctx.isOpen = false;
			ctx._triggerElement = null;
			document.body.style.overflow = '';

			// Return focus to the element that opened the lightbox.
			if ( trigger ) {
				requestAnimationFrame( () => trigger.focus() );
			}
		},

		next() {
			const ctx = getContext();
			if ( ! ctx.images?.length || ctx.images.length <= 1 ) return;
			ctx.currentIndex = ( ctx.currentIndex + 1 ) % ctx.images.length;
		},

		prev() {
			const ctx = getContext();
			if ( ! ctx.images?.length || ctx.images.length <= 1 ) return;
			const len = ctx.images.length;
			ctx.currentIndex = ( ctx.currentIndex - 1 + len ) % len;
		},

		/**
		 * Keyboard handler — bound via data-wp-on--keydown on the lightbox.
		 * Because the lightbox receives focus when opened, this handles
		 * all keyboard navigation without needing document-level listeners.
		 */
		handleKeydown( event ) {
			const ctx = getContext();
			if ( ! ctx.isOpen ) return;

			switch ( event.key ) {
				case 'Escape':
					actions.close();
					event.preventDefault();
					break;
				case 'ArrowRight':
					actions.next();
					event.preventDefault();
					break;
				case 'ArrowLeft':
					actions.prev();
					event.preventDefault();
					break;
			}
		},

		handleBackdropClick( event ) {
			// Only close if clicking the backdrop itself, not the image.
			if ( event.target === event.currentTarget ) {
				actions.close();
			}
		},
	},
} );

export { state, actions };
