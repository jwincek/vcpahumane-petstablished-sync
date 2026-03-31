/**
 * Pet Favorites Modal Store
 *
 * Cards are rendered imperatively by the syncCards callback rather than
 * via data-wp-each, which has a context-scoping bug where all cloned
 * elements share the last item's context.pet value.
 *
 * SSR cards (from PHP) are plain HTML with no Interactivity directives.
 * On hydration, syncCards takes ownership and re-renders from the
 * reactive favorites signal. Event delegation on the grid handles
 * unfavorite clicks and bonded pair popover toggles.
 *
 * @package Petstablished_Sync
 * @since 4.3.0
 */

import { store, getContext, getElement } from '@wordpress/interactivity';
import {
	doToggleFavorite,
	doToggleComparison,
} from '../store.js';
import { announce, storage, executeAbility } from '../utils.js';

const getGlobalState = () => store( 'petstablished' ).state;
const getGlobalActions = () => store( 'petstablished' ).actions;

let triggerRef = null;

/* ─── Card HTML builder ─── */

const heartSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';

function esc( str ) {
	const div = document.createElement( 'div' );
	div.textContent = String( str ?? '' );
	return div.innerHTML;
}

function buildCardHtml( pet ) {
	const meta = [ pet.breed, pet.age, pet.sex ].filter( Boolean ).join( ' \u00b7 ' );
	const partners = pet.bonded_pair_names || pet.bondedPartners || [];
	const isNew = pet.is_new || pet.isNew || false;
	const specialNeeds = pet.special_needs || pet.specialNeeds || false;
	const isBondedPair = pet.is_bonded_pair || pet.isBondedPair || false;
	const name = pet.name || '';
	const url = pet.url || '#';
	const image = pet.image || '';
	const size = pet.size || '';

	let badges = '';
	if ( isNew ) {
		badges += '<span class="pet-favorites-modal__badge pet-favorites-modal__badge--new">New</span>';
	}
	if ( specialNeeds ) {
		badges += '<span class="pet-favorites-modal__badge pet-favorites-modal__badge--special">Special Needs</span>';
	}
	if ( isBondedPair && partners.length ) {
		const partnerItems = partners.map( p => {
			if ( p.url ) {
				return `<li><a href="${ esc( p.url ) }" class="pet-favorites-modal__bonded-popover-link">${ esc( p.name ) }</a></li>`;
			}
			return `<li><span>${ esc( p.name ) }</span></li>`;
		} ).join( '' );
		badges += `<span class="pet-favorites-modal__badge-popover-anchor">
			<button type="button" class="pet-favorites-modal__badge pet-favorites-modal__badge--bonded js-bonded-toggle" aria-expanded="false">Bonded Pair</button>
			<div class="pet-favorites-modal__bonded-popover" hidden role="tooltip">
				<div class="pet-favorites-modal__bonded-popover-arrow"></div>
				<p class="pet-favorites-modal__bonded-popover-label">Must adopt together with:</p>
				<ul class="pet-favorites-modal__bonded-popover-list">${ partnerItems }</ul>
			</div>
		</span>`;
	}

	const imageHtml = image
		? `<img src="${ esc( image ) }" alt="${ esc( name ) }" class="pet-favorites-modal__card-image" loading="lazy">`
		: '<div class="pet-favorites-modal__card-placeholder"></div>';

	return `<article class="pet-favorites-modal__card" data-pet-id="${ pet.id }">
		<a href="${ esc( url ) }" class="pet-favorites-modal__card-link js-card-nav">${ imageHtml }</a>
		<div class="pet-favorites-modal__card-content">
			<h3 class="pet-favorites-modal__card-name"><a href="${ esc( url ) }" class="js-card-nav">${ esc( name ) }</a></h3>
			${ meta ? `<p class="pet-favorites-modal__card-meta">${ esc( meta ) }</p>` : '' }
			${ size ? `<p class="pet-favorites-modal__card-detail">${ esc( size ) }</p>` : '' }
			<div class="pet-favorites-modal__card-badges">${ badges }</div>
			<div class="pet-favorites-modal__card-actions">
				<button type="button" class="pet-favorites-modal__card-unfavorite js-unfavorite" data-pet-id="${ pet.id }" data-pet-name="${ esc( name ) }" aria-label="Remove ${ esc( name ) } from favorites">${ heartSvg }</button>
			</div>
		</div>
	</article>`;
}

/* ─── Event delegation (attached once to the grid container) ─── */

let delegationBound = false;

function bindGridDelegation( grid ) {
	if ( delegationBound ) return;
	delegationBound = true;

	grid.addEventListener( 'click', ( e ) => {
		// ── Unfavorite button ──
		const unfavBtn = e.target.closest( '.js-unfavorite' );
		if ( unfavBtn ) {
			e.preventDefault();
			const petId = Number( unfavBtn.dataset.petId );
			const petName = unfavBtn.dataset.petName || '';
			if ( petId ) {
				// Drive the doToggleFavorite generator manually.
				const gen = doToggleFavorite( petId, petName );
				const step = ( result ) => {
					if ( result.done ) return;
					Promise.resolve( result.value ).then(
						v => step( gen.next( v ) ),
						err => step( gen.throw( err ) )
					);
				};
				step( gen.next() );
			}
			return;
		}

		// ── Bonded pair popover toggle ──
		const toggle = e.target.closest( '.js-bonded-toggle' );
		if ( toggle ) {
			e.stopPropagation();
			const anchor = toggle.closest( '.pet-favorites-modal__badge-popover-anchor' );
			const popover = anchor?.querySelector( '.pet-favorites-modal__bonded-popover' );
			if ( ! popover ) return;

			const wasOpen = ! popover.hidden;
			popover.hidden = wasOpen;
			toggle.setAttribute( 'aria-expanded', String( ! wasOpen ) );
			toggle.classList.toggle( 'is-expanded', ! wasOpen );

			if ( ! wasOpen ) {
				// Scroll the popover into view within the modal content area.
				requestAnimationFrame( () => {
					popover.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
				} );
				const close = () => {
					popover.hidden = true;
					toggle.setAttribute( 'aria-expanded', 'false' );
					toggle.classList.remove( 'is-expanded' );
					document.removeEventListener( 'click', close, true );
				};
				requestAnimationFrame( () => {
					document.addEventListener( 'click', close, true );
				} );
			}
			return;
		}

		// ── Card navigation link — close modal ──
		const nav = e.target.closest( '.js-card-nav' );
		if ( nav ) {
			const ctx = getContext();
			ctx.isOpen = false;
			document.body.style.overflow = '';
			return;
		}

		// ── Bonded partner link — close modal on navigation ──
		const bondedLink = e.target.closest( '.pet-favorites-modal__bonded-popover-link' );
		if ( bondedLink ) {
			const ctx = getContext();
			ctx.isOpen = false;
			document.body.style.overflow = '';
		}
	} );
}

/* ─── Store ─── */

const { state, actions, callbacks } = store( 'petstablished/favorites-modal', {
	state: {
		get triggerFill() {
			return getGlobalState().favorites.length > 0 ? 'currentColor' : 'none';
		},
	},

	actions: {
		toggleModal() {
			const ctx = getContext();
			ctx.isOpen = ! ctx.isOpen;

			if ( ctx.isOpen ) {
				document.body.style.overflow = 'hidden';
				actions.refreshFavorites();
				requestAnimationFrame( () => {
					const close = document.querySelector( '.pet-favorites-modal__close' );
					if ( close ) close.focus();
				} );
			} else {
				document.body.style.overflow = '';
				if ( triggerRef ) triggerRef.focus();
			}
		},

		closeModal() {
			const ctx = getContext();
			ctx.isOpen = false;
			document.body.style.overflow = '';
			if ( triggerRef ) triggerRef.focus();
		},

		closeAndNavigate() {
			const ctx = getContext();
			ctx.isOpen = false;
			document.body.style.overflow = '';
		},

		handleOverlayClick( event ) {
			if ( event.target.classList.contains( 'pet-favorites-modal__overlay' ) ) {
				actions.closeModal();
			}
		},

		handleKeydown( event ) {
			if ( event.key === 'Escape' ) {
				actions.closeModal();
			}
		},

		*refreshFavorites() {
			try {
				const result = yield executeAbility(
					'petstablished/get-favorites', null, { method: 'GET' }
				);
				getGlobalState().favorites = result.favorites;
				storage.set( 'favorites', getGlobalState().favorites );
				if ( result.pets?.length ) {
					getGlobalActions().cachePets( result.pets );
				}
			} catch ( error ) {
				console.error( 'Failed to refresh favorites:', error );
			}
		},

		handleClearClick() {
			const btn = document.querySelector( '.pet-favorites-modal__clear-btn' );
			if ( ! btn ) return;

			// If already in confirm state, execute the clear.
			if ( btn.dataset.confirming === 'true' ) {
				delete btn.dataset.confirming;
				btn.classList.remove( 'is-confirming' );
				// Text is restored by the generator after clearing.
				const { actions } = store( 'petstablished/favorites-modal' );
				actions.clearAllFavorites();
				return;
			}

			// Enter confirm state — revert after 3 seconds if not tapped again.
			const originalText = btn.textContent;
			btn.dataset.confirming = 'true';
			btn.classList.add( 'is-confirming' );
			btn.textContent = btn.dataset.confirmText || 'Tap again to confirm';

			clearTimeout( btn._confirmTimer );
			btn._confirmTimer = setTimeout( () => {
				delete btn.dataset.confirming;
				btn.classList.remove( 'is-confirming' );
				btn.textContent = originalText;
			}, 3000 );
		},

		*clearAllFavorites() {
			const oldFavorites = [ ...getGlobalState().favorites ];
			if ( ! oldFavorites.length ) return;

			getGlobalState().favorites = [];
			oldFavorites.forEach( id => {
				if ( getGlobalState().pets[ id ] ) {
					getGlobalState().pets[ id ].favorited = false;
				}
			} );
			announce( 'All favorites cleared' );

			try {
				for ( const id of oldFavorites ) {
					yield executeAbility( 'petstablished/toggle-favorite', { id } );
				}
				storage.set( 'favorites', [] );
			} catch ( error ) {
				console.error( 'Failed to clear favorites:', error );
				getGlobalState().favorites = oldFavorites;
				oldFavorites.forEach( id => {
					if ( getGlobalState().pets[ id ] ) {
						getGlobalState().pets[ id ].favorited = true;
					}
				} );
				announce( 'Failed to clear favorites' );
			}
		},
	},

	callbacks: {
		init() {
			triggerRef = document.querySelector( '.pet-favorites-modal__trigger' );
			storage.set( 'favorites', getGlobalState().favorites );
		},

		syncBadgeVisibility() {
			const { ref } = getElement();
			if ( getGlobalState().favorites.length > 0 ) {
				ref.removeAttribute( 'hidden' );
			} else {
				ref.setAttribute( 'hidden', '' );
			}
		},

		syncEmptyVisibility() {
			const { ref } = getElement();
			if ( getGlobalState().favorites.length > 0 ) {
				ref.setAttribute( 'hidden', '' );
			} else {
				ref.removeAttribute( 'hidden' );
			}
		},

		syncGridVisibility() {
			const { ref } = getElement();
			if ( getGlobalState().favorites.length > 0 ) {
				ref.removeAttribute( 'hidden' );
			} else {
				ref.setAttribute( 'hidden', '' );
			}
		},

		/**
		 * Imperatively sync the card DOM to match the current favorites.
		 *
		 * Reads getGlobalState().favorites and getGlobalState().pets to
		 * create Preact signal subscriptions. When either changes, this
		 * callback re-runs and reconciles the DOM:
		 *
		 * - New favorites → card created via buildCardHtml
		 * - Removed favorites → card removed from DOM
		 * - Existing cards → left untouched (stable DOM)
		 *
		 * This replaces data-wp-each which had a context-scoping bug
		 * where all cloned template elements shared the last item's
		 * context.pet value.
		 */
		syncCards() {
			const { ref } = getElement();

			// Read reactive signals to create subscriptions.
			const gs = getGlobalState();
			const favoriteIds = [ ...gs.favorites ];
			const petsCache = gs.pets;

			// Bind event delegation once.
			bindGridDelegation( ref );

			// Build desired pet list (only pets that exist in the cache).
			const desiredPets = favoriteIds
				.map( id => petsCache[ id ] )
				.filter( Boolean );

			// Index current cards by data-pet-id.
			const currentCards = new Map();
			ref.querySelectorAll( '.pet-favorites-modal__card[data-pet-id]' ).forEach( el => {
				currentCards.set( Number( el.dataset.petId ), el );
			} );

			// Desired IDs set.
			const desiredIds = new Set( desiredPets.map( p => p.id ) );

			// Remove cards no longer in favorites.
			for ( const [ id, el ] of currentCards ) {
				if ( ! desiredIds.has( id ) ) {
					el.remove();
					currentCards.delete( id );
				}
			}

			// Add missing cards and ensure correct order.
			let prevNode = null;
			for ( const pet of desiredPets ) {
				let card = currentCards.get( pet.id );
				if ( ! card ) {
					const wrapper = document.createElement( 'div' );
					wrapper.innerHTML = buildCardHtml( pet );
					card = wrapper.firstElementChild;
				}

				// Ensure correct position.
				const expectedNext = prevNode ? prevNode.nextElementSibling : ref.firstElementChild;
				if ( card !== expectedNext ) {
					if ( prevNode ) {
						prevNode.after( card );
					} else {
						ref.prepend( card );
					}
				}

				prevNode = card;
			}
		},
	},
} );

export { state, actions, callbacks };
