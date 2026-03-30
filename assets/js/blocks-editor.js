/**
 * Petstablished Blocks - Editor Registration
 *
 * Registers server-side rendered blocks in the block editor.
 *
 * @package Petstablished_Sync
 * @since 2.0.0
 */

( function( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, InnerBlocks, MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { PanelBody, ToggleControl, SelectControl, RangeControl, TextControl, Button } = wp.components;
	const { createElement: el } = wp.element;
	const { __ } = wp.i18n;
	const ServerSideRender = wp.serverSideRender;

	// === SVG Icon Library ===
	const icons = {
		paw: el( 'svg', {
			width: 24,
			height: 24,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 2,
		},
			el( 'path', { d: 'M10 5.172C10 3.782 8.423 2.679 6.5 3c-2.823.47-4.113 6.006-4 7 .08.703 1.725 1.722 3.656 1 1.261-.472 1.96-1.45 2.344-2.5M14 5.172c0-1.39 1.577-2.493 3.5-2.172 2.823.47 4.113 6.006 4 7-.08.703-1.725 1.722-3.656 1-1.261-.472-1.96-1.45-2.344-2.5' }),
			el( 'path', { d: 'M8 14v.5M16 14v.5M11.25 16.25h1.5L12 17l-.75-.75Z' }),
			el( 'path', { d: 'M4.42 11.247A13.152 13.152 0 0 0 4 14.556C4 18.728 7.582 21 12 21s8-2.272 8-6.444c0-1.061-.162-2.2-.493-3.309' })
		),
		dog: el( 'svg', {
			width: 20,
			height: 20,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 2,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'path', { d: 'M10 5.172C10 3.782 8.423 2.679 6.5 3c-2.823.47-4.113 6.006-4 7 .08.703 1.725 1.722 3.656 1 1.261-.472 1.96-1.45 2.344-2.5M14.267 5.172c0-1.39 1.577-2.493 3.5-2.172 2.823.47 4.113 6.006 4 7-.08.703-1.725 1.722-3.656 1-1.261-.472-1.855-1.45-2.239-2.5' }),
			el( 'path', { d: 'M8 14v.5M16 14v.5M11.25 16.25h1.5L12 17l-.75-.75z' }),
			el( 'path', { d: 'M4.42 11.247A13.152 13.152 0 0 0 4 14.556C4 18.728 7.582 21 12 21s8-2.272 8-6.444a11.702 11.702 0 0 0-.493-3.309' })
		),
		cat: el( 'svg', {
			width: 20,
			height: 20,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 2,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'path', { d: 'M12 5c.67 0 1.35.09 2 .26 1.78-2 5.03-2.84 6.42-2.26 1.4.58-.42 7-.42 7 .57 1.07 1 2.24 1 3.44C21 17.9 16.97 21 12 21s-9-3.1-9-7.56c0-1.25.5-2.4 1-3.44 0 0-1.89-6.42-.5-7 1.39-.58 4.72.23 6.5 2.23A9.04 9.04 0 0 1 12 5z' }),
			el( 'path', { d: 'M8 14v.5M16 14v.5M11.25 16.25h1.5L12 17l-.75-.75z' })
		),
		child: el( 'svg', {
			width: 20,
			height: 20,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 2,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'circle', { cx: 12, cy: 4, r: 2 }),
			el( 'path', { d: 'M12 8v4m0 0-3 7m3-7 3 7M9 12h6' })
		),
		check: el( 'svg', {
			width: 16,
			height: 16,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 3,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'path', { d: 'M20 6L9 17l-5-5' })
		),
		x: el( 'svg', {
			width: 16,
			height: 16,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 3,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'line', { x1: 18, y1: 6, x2: 6, y2: 18 }),
			el( 'line', { x1: 6, y1: 6, x2: 18, y2: 18 })
		),
		question: el( 'svg', {
			width: 16,
			height: 16,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 2,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'circle', { cx: 12, cy: 12, r: 10 }),
			el( 'path', { d: 'M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3' }),
			el( 'line', { x1: 12, y1: 17, x2: 12.01, y2: 17 })
		),
		heart: el( 'svg', {
			width: 20,
			height: 20,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 2,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'path', { d: 'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z' })
		),
		columns: el( 'svg', {
			width: 20,
			height: 20,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 2,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'rect', { x: 3, y: 3, width: 18, height: 18, rx: 2, ry: 2 }),
			el( 'line', { x1: 12, y1: 3, x2: 12, y2: 21 })
		),
		share: el( 'svg', {
			width: 20,
			height: 20,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 2,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'circle', { cx: 18, cy: 5, r: 3 }),
			el( 'circle', { cx: 6, cy: 12, r: 3 }),
			el( 'circle', { cx: 18, cy: 19, r: 3 }),
			el( 'line', { x1: 8.59, y1: 13.51, x2: 15.42, y2: 17.49 }),
			el( 'line', { x1: 15.41, y1: 6.51, x2: 8.59, y2: 10.49 })
		),
		image: el( 'svg', {
			width: 48,
			height: 48,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 1.5,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
		},
			el( 'rect', { x: 3, y: 3, width: 18, height: 18, rx: 2, ry: 2 }),
			el( 'circle', { cx: 8.5, cy: 8.5, r: 1.5 }),
			el( 'path', { d: 'M21 15l-5-5L5 21' })
		),
	};

	// Common icon for pet blocks.
	const pawIcon = icons.paw;

	// === Pet Card ===
	registerBlockType( 'petstablished/pet-card', {
		title: __( 'Pet Card', 'petstablished-sync' ),
		description: __( 'Display a single pet card with image and details.', 'petstablished-sync' ),
		category: 'widgets',
		icon: pawIcon,
		keywords: [ 'pet', 'animal', 'adoption', 'card' ],
		supports: { html: false },
		attributes: {
			showFavorite: { type: 'boolean', default: true },
			showCompare: { type: 'boolean', default: true },
			showStatus: { type: 'boolean', default: true },
		},
		usesContext: [ 'postId', 'postType' ],
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Display Options', 'petstablished-sync' ) },
						el( ToggleControl, {
							label: __( 'Show Favorite Button', 'petstablished-sync' ),
							checked: attributes.showFavorite,
							onChange: ( val ) => setAttributes( { showFavorite: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Compare Button', 'petstablished-sync' ),
							checked: attributes.showCompare,
							onChange: ( val ) => setAttributes( { showCompare: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Status Badge', 'petstablished-sync' ),
							checked: attributes.showStatus,
							onChange: ( val ) => setAttributes( { showStatus: val } ),
						})
					)
				),
				el( ServerSideRender, {
					block: 'petstablished/pet-card',
					attributes: attributes,
				})
			);
		},
		save: () => null,
	});

	// === Pet Listing Grid ===
	registerBlockType( 'petstablished/pet-listing-grid', {
		title: __( 'Pet Listing Grid', 'petstablished-sync' ),
		description: __( 'Display a filterable grid of adoptable pets with instant client-side filtering.', 'petstablished-sync' ),
		category: 'widgets',
		icon: pawIcon,
		keywords: [ 'pet', 'listing', 'grid', 'archive', 'adoption' ],
		supports: { html: false, align: [ 'wide', 'full' ] },
		attributes: {
			columns: { type: 'number', default: 3 },
			showFilters: { type: 'boolean', default: true },
			showResultsCount: { type: 'boolean', default: true },
			badgeType: { type: 'string', default: 'animal' },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Layout', 'petstablished-sync' ) },
						el( RangeControl, {
							label: __( 'Columns', 'petstablished-sync' ),
							value: attributes.columns,
							onChange: ( val ) => setAttributes( { columns: val } ),
							min: 1,
							max: 4,
						}),
						el( SelectControl, {
							label: __( 'Badge Display', 'petstablished-sync' ),
							value: attributes.badgeType,
							options: [
								{ label: __( 'Animal Type (Cat, Dog)', 'petstablished-sync' ), value: 'animal' },
								{ label: __( 'Age (Puppy, Adult)', 'petstablished-sync' ), value: 'age' },
								{ label: __( 'New Arrivals Only', 'petstablished-sync' ), value: 'new' },
								{ label: __( 'No Badge', 'petstablished-sync' ), value: 'none' },
							],
							onChange: ( val ) => setAttributes( { badgeType: val } ),
						})
					),
					el( PanelBody, { title: __( 'Features', 'petstablished-sync' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show Filters', 'petstablished-sync' ),
							checked: attributes.showFilters,
							onChange: ( val ) => setAttributes( { showFilters: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Results Count', 'petstablished-sync' ),
							checked: attributes.showResultsCount,
							onChange: ( val ) => setAttributes( { showResultsCount: val } ),
						})
					)
				),
				el( ServerSideRender, {
					block: 'petstablished/pet-listing-grid',
					attributes: attributes,
				})
			);
		},
		save: () => null,
	});

	// === Pet Details ===
	registerBlockType( 'petstablished/pet-details', {
		title: __( 'Pet Details', 'petstablished-sync' ),
		description: __( 'Display comprehensive pet profile information.', 'petstablished-sync' ),
		category: 'widgets',
		icon: pawIcon,
		keywords: [ 'pet', 'details', 'profile', 'single' ],
		supports: { html: false, align: [ 'wide', 'full' ] },
		attributes: {
			layout: { type: 'string', default: 'sidebar' },
			showImage: { type: 'boolean', default: true },
			showStatus: { type: 'boolean', default: true },
			showAttributes: { type: 'boolean', default: true },
			showCompatibility: { type: 'boolean', default: true },
			showHealth: { type: 'boolean', default: true },
			showDescription: { type: 'boolean', default: true },
			showAdoptionInfo: { type: 'boolean', default: true },
			showActions: { type: 'boolean', default: true },
		},
		usesContext: [ 'postId', 'postType' ],
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Layout', 'petstablished-sync' ) },
						el( SelectControl, {
							label: __( 'Layout', 'petstablished-sync' ),
							value: attributes.layout,
							options: [
								{ label: __( 'Sidebar', 'petstablished-sync' ), value: 'sidebar' },
								{ label: __( 'Stacked', 'petstablished-sync' ), value: 'stacked' },
							],
							onChange: ( val ) => setAttributes( { layout: val } ),
						})
					),
					el( PanelBody, { title: __( 'Sections', 'petstablished-sync' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show Image', 'petstablished-sync' ),
							checked: attributes.showImage,
							onChange: ( val ) => setAttributes( { showImage: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Status', 'petstablished-sync' ),
							checked: attributes.showStatus,
							onChange: ( val ) => setAttributes( { showStatus: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Attributes', 'petstablished-sync' ),
							checked: attributes.showAttributes,
							onChange: ( val ) => setAttributes( { showAttributes: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Compatibility', 'petstablished-sync' ),
							checked: attributes.showCompatibility,
							onChange: ( val ) => setAttributes( { showCompatibility: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Health Info', 'petstablished-sync' ),
							checked: attributes.showHealth,
							onChange: ( val ) => setAttributes( { showHealth: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Description', 'petstablished-sync' ),
							checked: attributes.showDescription,
							onChange: ( val ) => setAttributes( { showDescription: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Adoption Info', 'petstablished-sync' ),
							checked: attributes.showAdoptionInfo,
							onChange: ( val ) => setAttributes( { showAdoptionInfo: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Actions', 'petstablished-sync' ),
							checked: attributes.showActions,
							onChange: ( val ) => setAttributes( { showActions: val } ),
						})
					)
				),
				el( ServerSideRender, {
					block: 'petstablished/pet-details',
					attributes: attributes,
				})
			);
		},
		save: () => null,
	});

	// === Pet Compare Bar ===
	registerBlockType( 'petstablished/pet-compare-bar', {
		title: __( 'Pet Compare Bar', 'petstablished-sync' ),
		description: __( 'Sticky bar showing pets selected for comparison.', 'petstablished-sync' ),
		category: 'widgets',
		icon: 'columns',
		keywords: [ 'pet', 'compare', 'comparison' ],
		supports: { html: false, multiple: false },
		attributes: {
			position: { type: 'string', default: 'bottom' },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Settings', 'petstablished-sync' ) },
						el( SelectControl, {
							label: __( 'Position', 'petstablished-sync' ),
							value: attributes.position,
							options: [
								{ label: __( 'Bottom', 'petstablished-sync' ), value: 'bottom' },
								{ label: __( 'Top', 'petstablished-sync' ), value: 'top' },
							],
							onChange: ( val ) => setAttributes( { position: val } ),
						})
					)
				),
				el( 'div', { className: 'petstablished-editor-placeholder' },
					el( 'p', {}, __( 'Pet Compare Bar', 'petstablished-sync' ) ),
					el( 'small', {}, __( 'This will appear as a sticky bar when pets are added to comparison.', 'petstablished-sync' ) )
				)
			);
		},
		save: () => null,
	});

	// === Pet Filters ===
	registerBlockType( 'petstablished/pet-filters', {
		title: __( 'Pet Filters', 'petstablished-sync' ),
		description: __( 'Filter form for pet listings.', 'petstablished-sync' ),
		category: 'widgets',
		icon: 'filter',
		keywords: [ 'pet', 'filter', 'search' ],
		supports: { html: false, align: [ 'wide', 'full' ] },
		attributes: {
			showAnimal: { type: 'boolean', default: true },
			showBreed: { type: 'boolean', default: true },
			showAge: { type: 'boolean', default: true },
			showSex: { type: 'boolean', default: true },
			showSize: { type: 'boolean', default: true },
			layout: { type: 'string', default: 'horizontal' },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Layout', 'petstablished-sync' ) },
						el( SelectControl, {
							label: __( 'Layout', 'petstablished-sync' ),
							value: attributes.layout,
							options: [
								{ label: __( 'Horizontal', 'petstablished-sync' ), value: 'horizontal' },
								{ label: __( 'Vertical', 'petstablished-sync' ), value: 'vertical' },
							],
							onChange: ( val ) => setAttributes( { layout: val } ),
						})
					),
					el( PanelBody, { title: __( 'Filters', 'petstablished-sync' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Animal Type', 'petstablished-sync' ),
							checked: attributes.showAnimal,
							onChange: ( val ) => setAttributes( { showAnimal: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Breed', 'petstablished-sync' ),
							checked: attributes.showBreed,
							onChange: ( val ) => setAttributes( { showBreed: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Age', 'petstablished-sync' ),
							checked: attributes.showAge,
							onChange: ( val ) => setAttributes( { showAge: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Sex', 'petstablished-sync' ),
							checked: attributes.showSex,
							onChange: ( val ) => setAttributes( { showSex: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Size', 'petstablished-sync' ),
							checked: attributes.showSize,
							onChange: ( val ) => setAttributes( { showSize: val } ),
						})
					)
				),
				el( ServerSideRender, {
					block: 'petstablished/pet-filters',
					attributes: attributes,
				})
			);
		},
		save: () => null,
	});

	// === Pet Favorites Toggle ===
	registerBlockType( 'petstablished/pet-favorites-toggle', {
		title: __( 'Favorites Toggle', 'petstablished-sync' ),
		description: __( 'Button to show only favorited pets.', 'petstablished-sync' ),
		category: 'widgets',
		icon: 'heart',
		keywords: [ 'pet', 'favorites', 'filter' ],
		supports: { html: false },
		attributes: {
			showCount: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Settings', 'petstablished-sync' ) },
						el( ToggleControl, {
							label: __( 'Show Count', 'petstablished-sync' ),
							checked: attributes.showCount,
							onChange: ( val ) => setAttributes( { showCount: val } ),
						})
					)
				),
				el( ServerSideRender, {
					block: 'petstablished/pet-favorites-toggle',
					attributes: attributes,
				})
			);
		},
		save: () => null,
	});

	// === Pet Slider ===
	registerBlockType( 'petstablished/pet-slider', {
		title: __( 'Pet Slider', 'petstablished-sync' ),
		description: __( 'A carousel slider showcasing available pets. Perfect for home pages, 404 pages, or anywhere you want to highlight adoptable pets.', 'petstablished-sync' ),
		category: 'widgets',
		icon: pawIcon,
		keywords: [ 'pet', 'slider', 'carousel', 'featured', 'hero', '404' ],
		supports: { html: false, align: [ 'wide', 'full' ] },
		attributes: {
			title: { type: 'string', default: 'Meet Our Pets' },
			showTitle: { type: 'boolean', default: true },
			count: { type: 'number', default: 8 },
			orderBy: { type: 'string', default: 'random' },
			autoplay: { type: 'boolean', default: false },
			autoplaySpeed: { type: 'number', default: 5000 },
			showNavigation: { type: 'boolean', default: true },
			showDots: { type: 'boolean', default: true },
			cardStyle: { type: 'string', default: 'default' },
			displayMode: { type: 'string', default: 'carousel' },
			showQuickActions: { type: 'boolean', default: true },
			showBadges: { type: 'boolean', default: true },
			badgePosition: { type: 'string', default: 'image-top' },
			ctaText: { type: 'string', default: 'Find Your New Best Friend' },
			showCta: { type: 'boolean', default: false },
			linkToArchive: { type: 'boolean', default: true },
			archiveLinkText: { type: 'string', default: 'View All Pets' },
			// Card styling (managed by editor.js inspector controls).
			cardBorderRadius: { type: 'number', default: 12 },
			cardGap: { type: 'number', default: 16 },
			// Typography (managed by editor.js inspector controls).
			nameFontSize: { type: 'string', default: '' },
			nameFontFamily: { type: 'string', default: '' },
			metaFontSize: { type: 'string', default: '' },
			metaFontFamily: { type: 'string', default: '' },
			// Similar pets mode (managed by editor.js inspector controls).
			similarPetsMode: { type: 'boolean', default: false },
			filterAnimal: { type: 'string', default: '' },
			filterAge: { type: 'string', default: '' },
			excludePostId: { type: 'number', default: 0 },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Display Mode', 'petstablished-sync' ) },
						el( SelectControl, {
							label: __( 'Mode', 'petstablished-sync' ),
							value: attributes.displayMode,
							options: [
								{ label: __( 'Carousel', 'petstablished-sync' ), value: 'carousel' },
								{ label: __( 'Hero (Featured)', 'petstablished-sync' ), value: 'hero' },
							],
							onChange: ( val ) => setAttributes( { displayMode: val } ),
							help: attributes.displayMode === 'hero' 
								? __( 'Large featured pet with thumbnail strip. Great for home and 404 pages.', 'petstablished-sync' )
								: __( 'Traditional sliding carousel of pet cards.', 'petstablished-sync' ),
						}),
						el( SelectControl, {
							label: __( 'Card Style', 'petstablished-sync' ),
							value: attributes.cardStyle,
							options: [
								{ label: __( 'Default', 'petstablished-sync' ), value: 'default' },
								{ label: __( 'Minimal', 'petstablished-sync' ), value: 'minimal' },
								{ label: __( 'Overlay', 'petstablished-sync' ), value: 'overlay' },
							],
							onChange: ( val ) => setAttributes( { cardStyle: val } ),
						})
					),
					el( PanelBody, { title: __( 'Content', 'petstablished-sync' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show Title', 'petstablished-sync' ),
							checked: attributes.showTitle,
							onChange: ( val ) => setAttributes( { showTitle: val } ),
						}),
						attributes.showTitle && el( 'div', { style: { marginBottom: '16px' } },
							el( 'label', { style: { display: 'block', marginBottom: '8px' } }, __( 'Title', 'petstablished-sync' ) ),
							el( 'input', {
								type: 'text',
								value: attributes.title,
								onChange: ( e ) => setAttributes( { title: e.target.value } ),
								style: { width: '100%' },
							})
						),
						attributes.displayMode === 'hero' && el( ToggleControl, {
							label: __( 'Show CTA Subtitle', 'petstablished-sync' ),
							checked: attributes.showCta,
							onChange: ( val ) => setAttributes( { showCta: val } ),
						}),
						attributes.displayMode === 'hero' && attributes.showCta && el( 'div', { style: { marginBottom: '16px' } },
							el( 'label', { style: { display: 'block', marginBottom: '8px' } }, __( 'CTA Text', 'petstablished-sync' ) ),
							el( 'input', {
								type: 'text',
								value: attributes.ctaText,
								onChange: ( e ) => setAttributes( { ctaText: e.target.value } ),
								style: { width: '100%' },
							})
						),
						el( RangeControl, {
							label: __( 'Number of Pets', 'petstablished-sync' ),
							value: attributes.count,
							onChange: ( val ) => setAttributes( { count: val } ),
							min: 1,
							max: 20,
						}),
						el( SelectControl, {
							label: __( 'Order By', 'petstablished-sync' ),
							value: attributes.orderBy,
							options: [
								{ label: __( 'Random', 'petstablished-sync' ), value: 'random' },
								{ label: __( 'Newest First', 'petstablished-sync' ), value: 'newest' },
								{ label: __( 'Name (A-Z)', 'petstablished-sync' ), value: 'name' },
							],
							onChange: ( val ) => setAttributes( { orderBy: val } ),
						})
					),
					el( PanelBody, { title: __( 'Navigation', 'petstablished-sync' ), initialOpen: false },
						attributes.displayMode === 'carousel' && el( ToggleControl, {
							label: __( 'Show Navigation Arrows', 'petstablished-sync' ),
							checked: attributes.showNavigation,
							onChange: ( val ) => setAttributes( { showNavigation: val } ),
						}),
						attributes.displayMode === 'carousel' && el( ToggleControl, {
							label: __( 'Show Dots', 'petstablished-sync' ),
							checked: attributes.showDots,
							onChange: ( val ) => setAttributes( { showDots: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Autoplay', 'petstablished-sync' ),
							checked: attributes.autoplay,
							onChange: ( val ) => setAttributes( { autoplay: val } ),
						}),
						attributes.autoplay && el( RangeControl, {
							label: __( 'Autoplay Speed (ms)', 'petstablished-sync' ),
							value: attributes.autoplaySpeed,
							onChange: ( val ) => setAttributes( { autoplaySpeed: val } ),
							min: 2000,
							max: 10000,
							step: 500,
						})
					),
					el( PanelBody, { title: __( 'Features', 'petstablished-sync' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show Quick Actions (Favorite/Compare)', 'petstablished-sync' ),
							checked: attributes.showQuickActions,
							onChange: ( val ) => setAttributes( { showQuickActions: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Link to Archive', 'petstablished-sync' ),
							checked: attributes.linkToArchive,
							onChange: ( val ) => setAttributes( { linkToArchive: val } ),
						}),
						attributes.linkToArchive && el( 'div', { style: { marginTop: '8px' } },
							el( 'label', { style: { display: 'block', marginBottom: '8px' } }, __( 'Archive Link Text', 'petstablished-sync' ) ),
							el( 'input', {
								type: 'text',
								value: attributes.archiveLinkText,
								onChange: ( e ) => setAttributes( { archiveLinkText: e.target.value } ),
								style: { width: '100%' },
							})
						),
						el( ToggleControl, {
							label: __( 'Show Badges (Bonded Pair, Special Needs)', 'petstablished-sync' ),
							checked: attributes.showBadges,
							onChange: ( val ) => setAttributes( { showBadges: val } ),
						}),
						attributes.showBadges && el( SelectControl, {
							label: __( 'Badge Position', 'petstablished-sync' ),
							value: attributes.badgePosition,
							options: [
								{ label: __( 'Top of image', 'petstablished-sync' ), value: 'image-top' },
								{ label: __( 'Bottom overlay', 'petstablished-sync' ), value: 'overlay-bottom' },
								{ label: __( 'Above pet name', 'petstablished-sync' ), value: 'above-name' },
							],
							onChange: ( val ) => setAttributes( { badgePosition: val } ),
						})
					)
				),
				el( ServerSideRender, {
					block: 'petstablished/pet-slider',
					attributes: attributes,
				})
			);
		},
		save: () => null,
	});

	// === Pet Comparison ===
	registerBlockType( 'petstablished/pet-comparison', {
		title: __( 'Pet Comparison', 'petstablished-sync' ),
		description: __( 'Display side-by-side comparison of selected pets.', 'petstablished-sync' ),
		category: 'widgets',
		icon: 'columns',
		keywords: [ 'pet', 'comparison', 'compare' ],
		supports: { html: false, align: [ 'wide', 'full' ] },
		attributes: {},
		edit: function( props ) {
			const blockProps = useBlockProps();

			return el( 'div', blockProps,
				el( ServerSideRender, {
					block: 'petstablished/pet-comparison',
					attributes: props.attributes,
				})
			);
		},
		save: () => null,
	});

	// === Child Blocks for Pet Details (Server-Side Rendered) ===

	// Pet Gallery
	registerBlockType( 'petstablished/pet-gallery', {
		title: __( 'Pet Gallery', 'petstablished-sync' ),
		description: __( 'Display pet photo gallery with lightbox.', 'petstablished-sync' ),
		category: 'petstablished',
		icon: 'format-gallery',
		keywords: [ 'pet', 'gallery', 'photos', 'images' ],
		parent: [ 'petstablished/pet-details' ],
		usesContext: [ 'postId', 'postType' ],
		supports: { html: false, reusable: false },
		attributes: {
			showThumbnails: { type: 'boolean', default: true },
			showLightbox: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const { attributes, setAttributes, context } = props;
			const blockProps = useBlockProps( { className: 'pet-gallery-editor' } );

			// Check if we have a pet context.
			const postId = context?.postId;
			const postType = context?.postType;
			const hasPetContext = postId && postType === 'pet';

			if ( ! hasPetContext ) {
				return el( 'div', blockProps,
					el( 'div', { className: 'components-placeholder' },
						el( 'div', { className: 'components-placeholder__label' },
							el( 'span', { className: 'dashicons dashicons-format-gallery', style: { marginRight: '8px' } } ),
							__( 'Pet Gallery', 'petstablished-sync' )
						),
						el( 'div', { className: 'components-placeholder__instructions' },
							__( 'Displays the pet\'s photo gallery. This block requires a pet context to display content.', 'petstablished-sync' )
						)
					)
				);
			}

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Gallery Settings', 'petstablished-sync' ) },
						el( ToggleControl, {
							label: __( 'Show Thumbnails', 'petstablished-sync' ),
							checked: attributes.showThumbnails,
							onChange: ( val ) => setAttributes( { showThumbnails: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Enable Lightbox', 'petstablished-sync' ),
							checked: attributes.showLightbox,
							onChange: ( val ) => setAttributes( { showLightbox: val } ),
						})
					)
				),
				el( ServerSideRender, {
					block: 'petstablished/pet-gallery',
					attributes: attributes,
					urlQueryArgs: { post_id: postId },
				})
			);
		},
		save: () => null,
	});

	// Pet Actions (Favorite, Compare, Share buttons)
	registerBlockType( 'petstablished/pet-actions', {
		title: __( 'Pet Actions', 'petstablished-sync' ),
		description: __( 'Display favorite, compare, and share action buttons.', 'petstablished-sync' ),
		category: 'petstablished',
		icon: 'heart',
		keywords: [ 'pet', 'actions', 'favorite', 'compare', 'share' ],
		parent: [ 'petstablished/pet-details' ],
		usesContext: [ 'postId', 'postType' ],
		supports: { html: false, reusable: false },
		attributes: {
			showFavorite: { type: 'boolean', default: true },
			showCompare: { type: 'boolean', default: true },
			showShare: { type: 'boolean', default: true },
			labelDisplay: { type: 'string', default: 'icon-and-text', enum: [ 'icon-and-text', 'icon-only', 'text-only' ] },
			displayMode: { type: 'string', default: 'inline', enum: [ 'inline', 'overlay' ] },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const isOverlay = attributes.displayMode === 'overlay';
			const blockProps = useBlockProps( { className: 'pet-actions-editor' + ( isOverlay ? ' pet-actions-editor--overlay' : '' ) } );

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Action Buttons', 'petstablished-sync' ) },
						el( ToggleControl, {
							label: __( 'Show Favorite Button', 'petstablished-sync' ),
							checked: attributes.showFavorite,
							onChange: ( val ) => setAttributes( { showFavorite: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Compare Button', 'petstablished-sync' ),
							checked: attributes.showCompare,
							onChange: ( val ) => setAttributes( { showCompare: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Share Button', 'petstablished-sync' ),
							checked: attributes.showShare,
							onChange: ( val ) => setAttributes( { showShare: val } ),
						})
					),
					el( PanelBody, { title: __( 'Display', 'petstablished-sync' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Display Mode', 'petstablished-sync' ),
							value: attributes.displayMode,
							options: [
								{ label: __( 'Inline (segmented bar)', 'petstablished-sync' ), value: 'inline' },
								{ label: __( 'Overlay (floating on gallery)', 'petstablished-sync' ), value: 'overlay' },
							],
							onChange: ( val ) => setAttributes( { displayMode: val } ),
							help: isOverlay
								? __( 'Circular icon buttons positioned over the gallery image.', 'petstablished-sync' )
								: __( 'Segmented button bar below the gallery.', 'petstablished-sync' ),
						}),
						! isOverlay && el( SelectControl, {
							label: __( 'Desktop Label Style', 'petstablished-sync' ),
							value: attributes.labelDisplay,
							options: [
								{ label: __( 'Icon & Text', 'petstablished-sync' ), value: 'icon-and-text' },
								{ label: __( 'Icon Only', 'petstablished-sync' ), value: 'icon-only' },
								{ label: __( 'Text Only', 'petstablished-sync' ), value: 'text-only' },
							],
							onChange: ( val ) => setAttributes( { labelDisplay: val } ),
							help: __( 'Mobile always shows icons only.', 'petstablished-sync' ),
						})
					)
				),
							el( 'div', { className: 'pet-actions-preview' },
					el( 'div', { className: 'components-placeholder is-small' },
						el( 'div', { className: 'components-placeholder__label', style: { display: 'flex', gap: '8px', alignItems: 'center' } },
							icons.heart,
							icons.columns,
							icons.share
						),
						el( 'div', { className: 'components-placeholder__instructions', style: { fontSize: '12px' } },
							__( 'Pet Actions: Favorite, Compare, Share', 'petstablished-sync' )
						)
					)
				)
			);
		},
		save: () => null,
	});

	// Pet Attributes (breed, age, sex, size, etc.)
	registerBlockType( 'petstablished/pet-attributes', {
		title: __( 'Pet Attributes', 'petstablished-sync' ),
		description: __( 'Display pet attributes like breed, age, sex, size in a definition list.', 'petstablished-sync' ),
		category: 'petstablished',
		icon: 'list-view',
		keywords: [ 'pet', 'attributes', 'breed', 'age', 'sex', 'size' ],
		parent: [ 'petstablished/pet-details' ],
		usesContext: [ 'postId', 'postType' ],
		supports: { html: false, reusable: false },
		attributes: {
			showBreed: { type: 'boolean', default: true },
			showAge: { type: 'boolean', default: true },
			showSex: { type: 'boolean', default: true },
			showSize: { type: 'boolean', default: true },
			showColor: { type: 'boolean', default: true },
			showCoat: { type: 'boolean', default: true },
			showWeight: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const { attributes, setAttributes, context } = props;
			const blockProps = useBlockProps( { className: 'pet-attributes-editor' } );

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Visible Attributes', 'petstablished-sync' ) },
						el( ToggleControl, {
							label: __( 'Breed', 'petstablished-sync' ),
							checked: attributes.showBreed,
							onChange: ( val ) => setAttributes( { showBreed: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Age', 'petstablished-sync' ),
							checked: attributes.showAge,
							onChange: ( val ) => setAttributes( { showAge: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Sex', 'petstablished-sync' ),
							checked: attributes.showSex,
							onChange: ( val ) => setAttributes( { showSex: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Size', 'petstablished-sync' ),
							checked: attributes.showSize,
							onChange: ( val ) => setAttributes( { showSize: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Color', 'petstablished-sync' ),
							checked: attributes.showColor,
							onChange: ( val ) => setAttributes( { showColor: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Coat', 'petstablished-sync' ),
							checked: attributes.showCoat,
							onChange: ( val ) => setAttributes( { showCoat: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Weight', 'petstablished-sync' ),
							checked: attributes.showWeight,
							onChange: ( val ) => setAttributes( { showWeight: val } ),
						})
					)
				),
				el( 'div', { className: 'pet-attributes-preview' },
					el( 'dl', { className: 'pet-attributes-list', style: { margin: 0, padding: '12px', background: '#f0f0f0', borderRadius: '4px' } },
						el( 'div', { style: { display: 'flex', gap: '8px', marginBottom: '4px' } },
							el( 'dt', { style: { fontWeight: 'bold', minWidth: '60px' } }, __( 'Breed', 'petstablished-sync' ) ),
							el( 'dd', { style: { margin: 0, color: '#666' } }, '[Breed]' )
						),
						el( 'div', { style: { display: 'flex', gap: '8px', marginBottom: '4px' } },
							el( 'dt', { style: { fontWeight: 'bold', minWidth: '60px' } }, __( 'Age', 'petstablished-sync' ) ),
							el( 'dd', { style: { margin: 0, color: '#666' } }, '[Age]' )
						),
						el( 'div', { style: { display: 'flex', gap: '8px' } },
							el( 'dt', { style: { fontWeight: 'bold', minWidth: '60px' } }, __( 'Sex', 'petstablished-sync' ) ),
							el( 'dd', { style: { margin: 0, color: '#666' } }, '[Sex]' )
						)
					)
				)
			);
		},
		save: () => null,
	});

	// Pet Compatibility (good with dogs, cats, kids)
	registerBlockType( 'petstablished/pet-compatibility', {
		title: __( 'Pet Compatibility', 'petstablished-sync' ),
		description: __( 'Display pet compatibility with dogs, cats, and children.', 'petstablished-sync' ),
		category: 'petstablished',
		icon: 'groups',
		keywords: [ 'pet', 'compatibility', 'good with', 'dogs', 'cats', 'kids' ],
		parent: [ 'petstablished/pet-details' ],
		usesContext: [ 'postId', 'postType' ],
		supports: { html: false, reusable: false },
		attributes: {
			showDogs: { type: 'boolean', default: true },
			showCats: { type: 'boolean', default: true },
			showKids: { type: 'boolean', default: true },
			displayStyle: { type: 'string', default: 'cards', enum: [ 'cards', 'stacked', 'pills' ] },
			headingText: { type: 'string', default: 'Good with' },
			positiveHeadingText: { type: 'string', default: 'Plays nicely with' },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( { className: 'pet-compatibility-editor' } );

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Compatibility Options', 'petstablished-sync' ) },
						el( SelectControl, {
							label: __( 'Display Style', 'petstablished-sync' ),
							value: attributes.displayStyle,
							options: [
								{ label: __( 'Cards (grid)', 'petstablished-sync' ), value: 'cards' },
								{ label: __( 'Stacked (vertical cards)', 'petstablished-sync' ), value: 'stacked' },
								{ label: __( 'Pills (compact strip)', 'petstablished-sync' ), value: 'pills' },
							],
							onChange: ( val ) => setAttributes( { displayStyle: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Dogs', 'petstablished-sync' ),
							checked: attributes.showDogs,
							onChange: ( val ) => setAttributes( { showDogs: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Cats', 'petstablished-sync' ),
							checked: attributes.showCats,
							onChange: ( val ) => setAttributes( { showCats: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Kids', 'petstablished-sync' ),
							checked: attributes.showKids,
							onChange: ( val ) => setAttributes( { showKids: val } ),
						})
					),
					el( PanelBody, { title: __( 'Heading', 'petstablished-sync' ), initialOpen: false },
						el( TextControl, {
							label: __( 'All-Positive Heading', 'petstablished-sync' ),
							value: attributes.positiveHeadingText,
							onChange: ( val ) => setAttributes( { positiveHeadingText: val } ),
							help: __( 'Shown when all visible items are "yes".', 'petstablished-sync' ),
						}),
						el( TextControl, {
							label: __( 'Mixed/General Heading', 'petstablished-sync' ),
							value: attributes.headingText,
							onChange: ( val ) => setAttributes( { headingText: val } ),
							help: __( 'Shown when any item is "no" or "unknown".', 'petstablished-sync' ),
						})
					)
				),
				el( 'div', { className: 'pet-compatibility-preview' },
					el( 'ul', { style: { display: 'flex', gap: '12px', listStyle: 'none', margin: 0, padding: '12px', background: '#f0f0f0', borderRadius: '4px', flexWrap: 'wrap' } },
						el( 'li', { style: { display: 'flex', alignItems: 'center', gap: '6px', padding: '6px 10px', background: '#ecfdf5', borderRadius: '6px', border: '1px solid #a7f3d0' } },
							el( 'span', { style: { display: 'flex', color: '#065f46' } }, icons.dog ),
							el( 'span', { style: { fontSize: '13px', fontWeight: 500, color: '#065f46' } }, __( 'Dogs', 'petstablished-sync' ) ),
							el( 'span', { style: { display: 'flex', color: '#10b981' } }, icons.check )
						),
						el( 'li', { style: { display: 'flex', alignItems: 'center', gap: '6px', padding: '6px 10px', background: '#f5f5f5', borderRadius: '6px', border: '1px solid #e0e0e0' } },
							el( 'span', { style: { display: 'flex', color: '#666' } }, icons.cat ),
							el( 'span', { style: { fontSize: '13px', fontWeight: 500, color: '#666' } }, __( 'Cats', 'petstablished-sync' ) ),
							el( 'span', { style: { display: 'flex', color: '#999' } }, icons.question )
						),
						el( 'li', { style: { display: 'flex', alignItems: 'center', gap: '6px', padding: '6px 10px', background: '#ecfdf5', borderRadius: '6px', border: '1px solid #a7f3d0' } },
							el( 'span', { style: { display: 'flex', color: '#065f46' } }, icons.child ),
							el( 'span', { style: { fontSize: '13px', fontWeight: 500, color: '#065f46' } }, __( 'Kids', 'petstablished-sync' ) ),
							el( 'span', { style: { display: 'flex', color: '#10b981' } }, icons.check )
						)
					)
				)
			);
		},
		save: () => null,
	});

	// Pet Health (vaccinations, spayed/neutered, etc.)
	registerBlockType( 'petstablished/pet-health', {
		title: __( 'Pet Health', 'petstablished-sync' ),
		description: __( 'Display pet health information like vaccinations and spay/neuter status.', 'petstablished-sync' ),
		category: 'petstablished',
		icon: 'plus-alt',
		keywords: [ 'pet', 'health', 'vaccinations', 'spayed', 'neutered' ],
		parent: [ 'petstablished/pet-details' ],
		usesContext: [ 'postId', 'postType' ],
		supports: { html: false, reusable: false },
		attributes: {
			showVaccinations: { type: 'boolean', default: true },
			showSpayedNeutered: { type: 'boolean', default: true },
			showHousebroken: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( { className: 'pet-health-editor' } );

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Health Information', 'petstablished-sync' ) },
						el( ToggleControl, {
							label: __( 'Show Vaccinations', 'petstablished-sync' ),
							checked: attributes.showVaccinations,
							onChange: ( val ) => setAttributes( { showVaccinations: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Spayed/Neutered', 'petstablished-sync' ),
							checked: attributes.showSpayedNeutered,
							onChange: ( val ) => setAttributes( { showSpayedNeutered: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Housebroken', 'petstablished-sync' ),
							checked: attributes.showHousebroken,
							onChange: ( val ) => setAttributes( { showHousebroken: val } ),
						})
					)
				),
				el( 'div', { className: 'pet-health-preview' },
					el( 'ul', { style: { listStyle: 'none', margin: 0, padding: '12px', background: '#f0f0f0', borderRadius: '4px' } },
						el( 'li', { style: { marginBottom: '4px' } }, '✅ ', __( 'Spayed/Neutered', 'petstablished-sync' ) ),
						el( 'li', { style: { marginBottom: '4px' } }, '✅ ', __( 'Vaccinations Current', 'petstablished-sync' ) ),
						el( 'li', null, '✅ ', __( 'House Trained', 'petstablished-sync' ) )
					)
				)
			);
		},
		save: () => null,
	});

	// Pet Adoption CTA — card container with InnerBlocks.
	// Content (heading, fee row, note, action button) is composed from native
	// core blocks with Block Bindings + the petstablished/adoption-action child block.
	registerBlockType( 'petstablished/pet-adoption-cta', {
		title: __( 'Pet Adoption CTA', 'petstablished-sync' ),
		description: __( 'Display adoption fee and application link or downloadable PDF form.', 'petstablished-sync' ),
		category: 'petstablished',
		icon: 'heart',
		keywords: [ 'pet', 'adoption', 'cta', 'apply', 'fee', 'pdf' ],
		parent: [ 'petstablished/pet-details' ],
		usesContext: [ 'postId', 'postType' ],
		supports: { html: false, reusable: false, spacing: { margin: true, padding: true } },
		attributes: {},
		edit: function() {
			const blockProps = useBlockProps( { className: 'pet-adoption-cta-editor' } );

			// Default InnerBlocks template:
			//   core/group (card — navy gradient, white text)
			//     core/group (content column — vertical flex)
			//       core/heading (bound to adoption_title)
			//       core/group (fee row — horizontal flex)
			//         core/paragraph (static label)
			//         core/paragraph (bound to adoption_fee_formatted)
			//       core/paragraph (note — freeform)
			//     petstablished/adoption-action (button)
			const TEMPLATE = [
				[ 'core/group', {
					className: 'pet-adoption-cta__card',
					style: {
						color: {
							gradient: 'linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%)',
							text: '#ffffff',
						},
					},
				}, [
					[ 'core/group', {
						className: 'pet-adoption-cta__content',
						layout: { type: 'flex', orientation: 'vertical', flexWrap: 'nowrap' },
					}, [
						[ 'core/heading', {
							level: 2,
							className: 'pet-adoption-cta__title',
							metadata: {
								bindings: {
									content: {
										source: 'petstablished/pet-data',
										args: { key: 'adoption_title' },
									},
								},
							},
						} ],
						[ 'core/group', {
							className: 'pet-adoption-cta__fee-row',
							layout: { type: 'flex', flexWrap: 'nowrap', verticalAlignment: 'center' },
						}, [
							[ 'core/paragraph', {
								content: __( 'Adoption Fee:', 'petstablished-sync' ),
								className: 'pet-adoption-cta__fee-label',
							} ],
							[ 'core/paragraph', {
								className: 'pet-adoption-cta__fee-amount',
								metadata: {
									bindings: {
										content: {
											source: 'petstablished/pet-data',
											args: { key: 'adoption_fee_formatted' },
										},
									},
								},
							} ],
						] ],
						[ 'core/paragraph', {
							content: __( 'The adoption fee helps cover vaccinations, spay/neuter surgery, microchip, and initial veterinary care.', 'petstablished-sync' ),
							className: 'pet-adoption-cta__note',
						} ],
					] ],
					[ 'petstablished/adoption-action', {} ],
				] ],
			];

			return el( 'div', blockProps,
				el( InnerBlocks, {
					template: TEMPLATE,
					templateLock: false,
					allowedBlocks: [
						'core/group',
						'core/heading',
						'core/paragraph',
						'core/spacer',
						'petstablished/adoption-action',
					],
				} )
			);
		},
		save: () => null,
	} );

	// Pet Adoption Fee — SSR fee row, auto-hidden when no fee is set.
	registerBlockType( 'petstablished/adoption-fee', {
		title: __( 'Pet Adoption Fee', 'petstablished-sync' ),
		description: __( 'Displays the adoption fee. Hidden automatically if no fee is set.', 'petstablished-sync' ),
		category: 'petstablished',
		icon: 'tag',
		keywords: [ 'pet', 'adoption', 'fee', 'price' ],
		ancestor: [ 'petstablished/pet-adoption-cta' ],
		usesContext: [ 'postId', 'postType' ],
		supports: { html: false, reusable: false },
		attributes: {},
		edit: function() {
			const blockProps = useBlockProps( { className: 'pet-adoption-fee-editor' } );
			return el( 'div', blockProps,
				el( 'span', { className: 'pet-adoption-cta__fee-label' },
					__( 'Adoption Fee:', 'petstablished-sync' )
				),
				el( 'span', { className: 'pet-adoption-cta__fee-amount' },
					' $[Fee]'
				)
			);
		},
		save: () => null,
	} );

	// Pet Adoption Action — application button (Petstablished link or PDF download).
	// Lives inside petstablished/pet-adoption-cta as a child block.
	registerBlockType( 'petstablished/adoption-action', {
		title: __( 'Pet Adoption Action', 'petstablished-sync' ),
		description: __( 'Adoption application button — links to Petstablished form or provides a PDF download.', 'petstablished-sync' ),
		category: 'petstablished',
		icon: 'download',
		keywords: [ 'pet', 'adoption', 'button', 'apply', 'pdf' ],
		ancestor: [ 'petstablished/pet-adoption-cta' ],
		usesContext: [ 'postId', 'postType' ],
		supports: { html: false, reusable: false },
		attributes: {
			formMode: { type: 'string', default: 'petstablished', enum: [ 'petstablished', 'pdf' ] },
			pdfAttachmentId: { type: 'integer', default: 0 },
			pdfButtonText: { type: 'string', default: 'Download Adoption Application' },
			buttonText: { type: 'string', default: 'Start Adoption Application' },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( { className: 'pet-adoption-action-editor' } );
			const isPdf = attributes.formMode === 'pdf';

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Application Mode', 'petstablished-sync' ) },
						el( SelectControl, {
							label: __( 'Form Mode', 'petstablished-sync' ),
							value: attributes.formMode,
							options: [
								{ label: __( 'Petstablished (link to adoption form)', 'petstablished-sync' ), value: 'petstablished' },
								{ label: __( 'PDF Download', 'petstablished-sync' ), value: 'pdf' },
							],
							onChange: ( val ) => setAttributes( { formMode: val } ),
						} ),
						! isPdf && el( TextControl, {
							label: __( 'Button Text', 'petstablished-sync' ),
							value: attributes.buttonText,
							onChange: ( val ) => setAttributes( { buttonText: val } ),
						} ),
						isPdf && el( 'div', {},
							el( MediaUploadCheck, {},
								el( MediaUpload, {
									onSelect: ( media ) => setAttributes( { pdfAttachmentId: media.id } ),
									allowedTypes: [ 'application/pdf' ],
									value: attributes.pdfAttachmentId,
									render: ( { open } ) => el( Button, {
										onClick: open,
										variant: 'secondary',
										style: { marginBottom: '12px', width: '100%', justifyContent: 'center' },
									},
										attributes.pdfAttachmentId
											? __( 'Replace PDF', 'petstablished-sync' )
											: __( 'Select PDF from Media Library', 'petstablished-sync' )
									),
								} )
							),
							el( TextControl, {
								label: __( 'PDF Button Text', 'petstablished-sync' ),
								value: attributes.pdfButtonText,
								onChange: ( val ) => setAttributes( { pdfButtonText: val } ),
							} ),
						),
					),
				),
				isPdf && ! attributes.pdfAttachmentId
					? el( 'div', { className: 'pet-adoption-cta-preview__empty-state' },
						el( 'p', {}, __( 'No PDF selected — choose a file in the Application Mode panel.', 'petstablished-sync' ) )
					  )
					: el( 'button', { className: 'pet-adoption-cta__action-btn', disabled: true },
						isPdf ? attributes.pdfButtonText : attributes.buttonText
					  )
			);
		},
		save: () => null,
	} );

	// === Pet Favorites Modal ===
	registerBlockType( 'petstablished/pet-favorites-modal', {
		title: __( 'Pet Favorites Modal', 'petstablished-sync' ),
		description: __( 'Floating heart button that opens a modal showing all favorited pets.', 'petstablished-sync' ),
		category: 'petstablished',
		icon: 'heart',
		keywords: [ 'pet', 'favorites', 'heart', 'modal' ],
		supports: { html: false, multiple: false },
		attributes: {
			position: {
				type: 'string',
				default: 'bottom-right',
				enum: [ 'bottom-right', 'bottom-left' ],
			},
			showCompare: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Settings', 'petstablished-sync' ) },
						el( SelectControl, {
							label: __( 'Position', 'petstablished-sync' ),
							value: attributes.position,
							options: [
								{ label: __( 'Bottom Right', 'petstablished-sync' ), value: 'bottom-right' },
								{ label: __( 'Bottom Left', 'petstablished-sync' ), value: 'bottom-left' },
							],
							onChange: ( val ) => setAttributes( { position: val } ),
						}),
						el( ToggleControl, {
							label: __( 'Show Compare Button', 'petstablished-sync' ),
							checked: attributes.showCompare,
							onChange: ( val ) => setAttributes( { showCompare: val } ),
						})
					)
				),
				el( 'div', {
					style: {
						display: 'flex',
						alignItems: 'center',
						gap: '12px',
						padding: '16px 20px',
						background: '#f9f9f9',
						borderRadius: '8px',
						border: '1px dashed #ddd',
					},
				},
					el( 'span', {
						className: 'dashicons dashicons-heart',
						style: { fontSize: '24px', color: '#cf2e2e' },
					} ),
					el( 'div', {},
						el( 'strong', {}, __( 'Favorites Modal', 'petstablished-sync' ) ),
						el( 'p', {
							style: { margin: '4px 0 0', fontSize: '12px', color: '#757575' },
						}, __( 'Floating heart button appears on the frontend. Click to open a modal with all favorited pets.', 'petstablished-sync' ) )
					)
				)
			);
		},
		save: () => null,
	});

} )( window.wp );