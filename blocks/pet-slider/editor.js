/**
 * Pet Slider Block - Editor Controls
 * 
 * Adds inspector controls for customizing slider appearance.
 * Uses vanilla JS with wp.element.createElement (no JSX/build required).
 */

( function( wp ) {
	const { createElement: el, Fragment, useEffect, useRef } = wp.element;
	const { __ } = wp.i18n;
	const { addFilter } = wp.hooks;
	const { createHigherOrderComponent } = wp.compose;
	const { InspectorControls } = wp.blockEditor;
	const { 
		PanelBody, 
		RangeControl, 
		SelectControl,
		FontSizePicker,
		ToggleControl,
		TextControl,
	} = wp.components;
	const { useSelect } = wp.data;

	/**
	 * Add inspector controls to the block editor.
	 */
	const withInspectorControls = createHigherOrderComponent( function( BlockEdit ) {
		return function( props ) {
			if ( props.name !== 'petstablished/pet-slider' ) {
				return el( BlockEdit, props );
			}

			const { attributes, setAttributes, clientId } = props;
			const {
				cardBorderRadius = 12,
				cardGap = 16,
				nameFontSize = '',
				nameFontFamily = '',
				metaFontSize = '',
				metaFontFamily = '',
				similarPetsMode = false,
				filterAnimal = '',
				filterAge = '',
			} = attributes;

			// Track style element ref for cleanup.
			const styleRef = useRef( null );

			// Get theme font sizes and families.
			const editorSettings = useSelect( function( select ) {
				const settings = select( 'core/block-editor' ).getSettings();
				return {
					fontSizes: settings.fontSizes || [],
					fontFamilies: settings.__experimentalFontFamilies?.theme || 
						settings.fontFamilies || 
						[],
				};
			}, [] );

			const fontFamilyOptions = [
				{ label: __( 'Default', 'petstablished-sync' ), value: '' }
			].concat(
				editorSettings.fontFamilies.map( function( font ) {
					return {
						label: font.name,
						value: font.fontFamily || font.slug,
					};
				} )
			);

			// Inject scoped styles into the editor iframe.
			useEffect( function() {
				const styleId = 'pet-slider-editor-styles-' + clientId;
				
				// Try to find the editor iframe.
				const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
				const targetDoc = iframe ? iframe.contentDocument : document;
				
				let styleEl = targetDoc.getElementById( styleId );
				
				if ( ! styleEl ) {
					styleEl = targetDoc.createElement( 'style' );
					styleEl.id = styleId;
					targetDoc.head.appendChild( styleEl );
				}
				
				styleRef.current = styleEl;

				// Build scoped CSS targeting this specific block in the editor.
				// Uses !important to override the SSR-rendered <style> tag which
				// uses the block's ID selector and reflects the last-saved values.
				const selector = '[data-block="' + clientId + '"] .pet-slider';
				let css = '';
				css += selector + ' { --slider-gap: ' + cardGap + 'px !important; --slider-card-radius: ' + cardBorderRadius + 'px !important; }';
				
				if ( nameFontSize ) {
					css += selector + ' .pet-slider__name { font-size: ' + nameFontSize + ' !important; }';
				}
				if ( nameFontFamily ) {
					css += selector + ' .pet-slider__name { font-family: ' + nameFontFamily + ' !important; }';
				}
				if ( metaFontSize ) {
					css += selector + ' .pet-slider__meta { font-size: ' + metaFontSize + ' !important; }';
				}
				if ( metaFontFamily ) {
					css += selector + ' .pet-slider__meta { font-family: ' + metaFontFamily + ' !important; }';
				}

				styleEl.textContent = css;

				// Cleanup on unmount.
				return function() {
					if ( styleRef.current && styleRef.current.parentNode ) {
						styleRef.current.parentNode.removeChild( styleRef.current );
					}
				};
			}, [ clientId, cardBorderRadius, cardGap, nameFontSize, nameFontFamily, metaFontSize, metaFontFamily ] );

			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				el(
					InspectorControls,
					null,
					// Similar Pets Panel (show first when on single pet template)
					el(
						PanelBody,
						{
							title: __( 'Similar Pets', 'petstablished-sync' ),
							initialOpen: similarPetsMode,
						},
						el( ToggleControl, {
							label: __( 'Similar Pets Mode', 'petstablished-sync' ),
							help: similarPetsMode 
								? __( 'Shows pets filtered by current pet\'s type and age. Adapts to card layout for 1-3 results.', 'petstablished-sync' )
								: __( 'Enable to filter by current pet\'s animal type and age group.', 'petstablished-sync' ),
							checked: similarPetsMode,
							onChange: function( value ) {
								setAttributes( { similarPetsMode: value } );
							},
						} ),
						similarPetsMode && el(
							Fragment,
							null,
							el( 'p', {
								className: 'components-base-control__help',
								style: { marginTop: '8px', marginBottom: '16px', fontStyle: 'italic' }
							}, __( 'Leave fields empty to auto-detect from current pet. Override by entering a specific value.', 'petstablished-sync' ) ),
							el( TextControl, {
								label: __( 'Filter by Animal Type (slug)', 'petstablished-sync' ),
								help: __( 'e.g., "dog", "cat" - leave empty to use current pet\'s type', 'petstablished-sync' ),
								value: filterAnimal,
								onChange: function( value ) {
									setAttributes( { filterAnimal: value } );
								},
							} ),
							el( TextControl, {
								label: __( 'Filter by Age Group (slug)', 'petstablished-sync' ),
								help: __( 'e.g., "puppy", "adult", "senior" - leave empty to use current pet\'s age', 'petstablished-sync' ),
								value: filterAge,
								onChange: function( value ) {
									setAttributes( { filterAge: value } );
								},
							} )
						)
					),
					// Card Styling Panel
					el(
						PanelBody,
						{
							title: __( 'Card Styling', 'petstablished-sync' ),
							initialOpen: ! similarPetsMode,
						},
						el( RangeControl, {
							label: __( 'Card Border Radius', 'petstablished-sync' ),
							value: cardBorderRadius,
							onChange: function( value ) {
								setAttributes( { cardBorderRadius: value } );
							},
							min: 0,
							max: 32,
							step: 1,
							help: __( 'Rounded corners for pet cards (in pixels)', 'petstablished-sync' ),
						} ),
						el( RangeControl, {
							label: __( 'Card Gap / Spacing', 'petstablished-sync' ),
							value: cardGap,
							onChange: function( value ) {
								setAttributes( { cardGap: value } );
							},
							min: 0,
							max: 48,
							step: 4,
							help: __( 'Space between cards (in pixels)', 'petstablished-sync' ),
						} )
					),
					// Typography Panel
					el(
						PanelBody,
						{
							title: __( 'Typography', 'petstablished-sync' ),
							initialOpen: false,
						},
						// Pet Name Typography
						el( 'p', { 
							className: 'components-base-control__label',
							style: { marginBottom: '8px', fontWeight: '500' }
						}, __( 'Pet Name', 'petstablished-sync' ) ),
						el( FontSizePicker, {
							fontSizes: editorSettings.fontSizes,
							value: nameFontSize,
							onChange: function( value ) {
								setAttributes( { nameFontSize: value } );
							},
							fallbackFontSize: 18,
							withReset: true,
						} ),
						fontFamilyOptions.length > 1 && el( SelectControl, {
							label: __( 'Pet Name Font', 'petstablished-sync' ),
							value: nameFontFamily,
							options: fontFamilyOptions,
							onChange: function( value ) {
								setAttributes( { nameFontFamily: value } );
							},
						} ),
						// Pet Meta Typography
						el( 'div', { style: { marginTop: '24px' } },
							el( 'p', { 
								className: 'components-base-control__label',
								style: { marginBottom: '8px', fontWeight: '500' }
							}, __( 'Pet Meta (breed, age, etc.)', 'petstablished-sync' ) ),
							el( FontSizePicker, {
								fontSizes: editorSettings.fontSizes,
								value: metaFontSize,
								onChange: function( value ) {
									setAttributes( { metaFontSize: value } );
								},
								fallbackFontSize: 14,
								withReset: true,
							} ),
							fontFamilyOptions.length > 1 && el( SelectControl, {
								label: __( 'Pet Meta Font', 'petstablished-sync' ),
								value: metaFontFamily,
								options: fontFamilyOptions,
								onChange: function( value ) {
									setAttributes( { metaFontFamily: value } );
								},
							} )
						)
					)
				)
			);
		};
	}, 'withInspectorControls' );

	addFilter(
		'editor.BlockEdit',
		'petstablished/pet-slider/inspector-controls',
		withInspectorControls
	);

} )( window.wp );