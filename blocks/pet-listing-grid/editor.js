/**
 * Pet Listing Grid Block - Editor Controls
 * 
 * Adds InspectorControls for configuring which filters to display.
 * Uses vanilla JS with wp.element.createElement (no JSX/build required).
 */

( function( wp ) {
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { addFilter } = wp.hooks;
	const { createHigherOrderComponent } = wp.compose;
	const { InspectorControls } = wp.blockEditor;
	const { 
		PanelBody, 
		ToggleControl,
		RangeControl,
		SelectControl,
	} = wp.components;

	/**
	 * Add inspector controls to the block editor.
	 */
	const withInspectorControls = createHigherOrderComponent( function( BlockEdit ) {
		return function( props ) {
			if ( props.name !== 'petstablished/pet-listing-grid' ) {
				return el( BlockEdit, props );
			}

			const { attributes, setAttributes } = props;
			const {
				columns = 3,
				showFilters = true,
				showSearch = true,
				showResultsCount = true,
				badgeType = 'animal',
				// Taxonomy filters
				filterAnimal = true,
				filterBreed = true,
				filterAge = true,
				filterSex = true,
				filterSize = true,
				// Compatibility filters
				showCompatibilityFilters = true,
				filterGoodWithDogs = true,
				filterGoodWithCats = true,
				filterGoodWithKids = true,
				filterShotsCurrent = true,
				filterSpayedNeutered = true,
				filterHousebroken = true,
				filterSpecialNeeds = false,
				compatibilityStyle = 'chips',
			} = attributes;

			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				el(
					InspectorControls,
					null,
					// Display Settings
					el(
						PanelBody,
						{
							title: __( 'Display Settings', 'petstablished-sync' ),
							initialOpen: true,
						},
						el( RangeControl, {
							label: __( 'Columns', 'petstablished-sync' ),
							value: columns,
							onChange: function( value ) {
								setAttributes( { columns: value } );
							},
							min: 1,
							max: 6,
							step: 1,
						} ),
						el( SelectControl, {
							label: __( 'Badge Type', 'petstablished-sync' ),
							value: badgeType,
							options: [
								{ label: __( 'Animal Type', 'petstablished-sync' ), value: 'animal' },
								{ label: __( 'Age', 'petstablished-sync' ), value: 'age' },
								{ label: __( 'New (7 days)', 'petstablished-sync' ), value: 'new' },
								{ label: __( 'None', 'petstablished-sync' ), value: 'none' },
							],
							onChange: function( value ) {
								setAttributes( { badgeType: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show Results Count', 'petstablished-sync' ),
							checked: showResultsCount,
							onChange: function( value ) {
								setAttributes( { showResultsCount: value } );
							},
						} )
					),
					// Filter Controls
					el(
						PanelBody,
						{
							title: __( 'Filter Settings', 'petstablished-sync' ),
							initialOpen: false,
						},
						el( ToggleControl, {
							label: __( 'Show Search', 'petstablished-sync' ),
							help: __( 'Search pets by name or breed', 'petstablished-sync' ),
							checked: showSearch,
							onChange: function( value ) {
								setAttributes( { showSearch: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show Filters', 'petstablished-sync' ),
							checked: showFilters,
							onChange: function( value ) {
								setAttributes( { showFilters: value } );
							},
						} ),
						showFilters && el(
							Fragment,
							null,
							el( 'p', {
								className: 'components-base-control__label',
								style: { marginTop: '16px', marginBottom: '8px', fontWeight: '600' }
							}, __( 'Basic Filters', 'petstablished-sync' ) ),
							el( ToggleControl, {
								label: __( 'Animal Type', 'petstablished-sync' ),
								checked: filterAnimal,
								onChange: function( value ) {
									setAttributes( { filterAnimal: value } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Breed', 'petstablished-sync' ),
								checked: filterBreed,
								onChange: function( value ) {
									setAttributes( { filterBreed: value } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Age', 'petstablished-sync' ),
								checked: filterAge,
								onChange: function( value ) {
									setAttributes( { filterAge: value } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Sex', 'petstablished-sync' ),
								checked: filterSex,
								onChange: function( value ) {
									setAttributes( { filterSex: value } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Size', 'petstablished-sync' ),
								checked: filterSize,
								onChange: function( value ) {
									setAttributes( { filterSize: value } );
								},
							} )
						)
					),
					// Compatibility Filters
					showFilters && el(
						PanelBody,
						{
							title: __( 'Compatibility Filters', 'petstablished-sync' ),
							initialOpen: false,
						},
						el( ToggleControl, {
							label: __( 'Show Compatibility Filters', 'petstablished-sync' ),
							help: __( 'Filters for "good with" and health status', 'petstablished-sync' ),
							checked: showCompatibilityFilters,
							onChange: function( value ) {
								setAttributes( { showCompatibilityFilters: value } );
							},
						} ),
						showCompatibilityFilters && el(
							Fragment,
							null,
							el( SelectControl, {
								label: __( 'Filter Style', 'petstablished-sync' ),
								value: compatibilityStyle,
								options: [
									{ label: __( 'Chips (pill buttons)', 'petstablished-sync' ), value: 'chips' },
									{ label: __( 'Checkboxes (grouped)', 'petstablished-sync' ), value: 'checkboxes' },
								],
								onChange: function( value ) {
									setAttributes( { compatibilityStyle: value } );
								},
							} ),
							el( 'p', {
								className: 'components-base-control__label',
								style: { marginTop: '16px', marginBottom: '8px', fontWeight: '600' }
							}, __( 'Good With', 'petstablished-sync' ) ),
							el( ToggleControl, {
								label: __( 'Dogs', 'petstablished-sync' ),
								checked: filterGoodWithDogs,
								onChange: function( value ) {
									setAttributes( { filterGoodWithDogs: value } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Cats', 'petstablished-sync' ),
								checked: filterGoodWithCats,
								onChange: function( value ) {
									setAttributes( { filterGoodWithCats: value } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Kids', 'petstablished-sync' ),
								checked: filterGoodWithKids,
								onChange: function( value ) {
									setAttributes( { filterGoodWithKids: value } );
								},
							} ),
							el( 'p', {
								className: 'components-base-control__label',
								style: { marginTop: '16px', marginBottom: '8px', fontWeight: '600' }
							}, __( 'Health & Training', 'petstablished-sync' ) ),
							el( ToggleControl, {
								label: __( 'Shots Current', 'petstablished-sync' ),
								checked: filterShotsCurrent,
								onChange: function( value ) {
									setAttributes( { filterShotsCurrent: value } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Spayed/Neutered', 'petstablished-sync' ),
								checked: filterSpayedNeutered,
								onChange: function( value ) {
									setAttributes( { filterSpayedNeutered: value } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Housebroken', 'petstablished-sync' ),
								checked: filterHousebroken,
								onChange: function( value ) {
									setAttributes( { filterHousebroken: value } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Special Needs', 'petstablished-sync' ),
								help: __( 'Show pets with special needs', 'petstablished-sync' ),
								checked: filterSpecialNeeds,
								onChange: function( value ) {
									setAttributes( { filterSpecialNeeds: value } );
								},
							} )
						)
					)
				)
			);
		};
	}, 'withListingGridInspectorControls' );

	addFilter(
		'editor.BlockEdit',
		'petstablished/pet-listing-grid/inspector-controls',
		withInspectorControls
	);

} )( window.wp );
