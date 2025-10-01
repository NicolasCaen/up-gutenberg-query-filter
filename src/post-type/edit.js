import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes, context } ) {
	const { controlType = 'select', emptyLabel, label, showLabel } = attributes;

	const allPostTypes = useSelect( ( select ) => {
		return (
			( select( 'core' ).getPostTypes( { per_page: 100 } ) || [] ).filter(
				( type ) => type.viewable
			) || []
		);
	}, [] );

	let contextPostTypes = ( context.query.postType || '' )
		.split( ',' )
		.map( ( type ) => type.trim() );

	// Support for enhanced query loop block plugin.
	if ( Array.isArray( context.query.multiple_posts ) ) {
		contextPostTypes = contextPostTypes.concat(
			context.query.multiple_posts
		);
	}

	const postTypes = contextPostTypes.map( ( postType ) => {
		return (
			allPostTypes.find( ( type ) => type.slug === postType ) || {
				slug: postType,
				name: postType,
			}
		);
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Post Type Settings', 'query-filter' ) }>
					<TextControl
						label={ __( 'Label', 'query-filter' ) }
						value={ label }
						defaultValue={ __( 'Content Type', 'query-filter' ) }
						help={ __(
							'If empty then no label will be shown',
							'query-filter'
						) }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>
					<ToggleControl
						label={ __( 'Show Label', 'query-filter' ) }
						checked={ showLabel }
						onChange={ ( showLabel ) =>
							setAttributes( { showLabel } )
						}
					/>
					// Control Type selector
					<SelectControl
						label={ __( 'Control Type', 'query-filter' ) }
						value={ controlType }
						options={ [
							{ label: __( 'Select (single)', 'query-filter' ), value: 'select' },
							{ label: __( 'Radio (single)', 'query-filter' ), value: 'radio' },
							{ label: __( 'Checkboxes (multiple)', 'query-filter' ), value: 'checkbox' },
						] }
						onChange={ ( value ) => setAttributes( { controlType: value } ) }
					/>
					<TextControl
						label={ __( 'Empty Choice Label', 'query-filter' ) }
						value={ emptyLabel }
						placeholder={ __( 'All', 'query-filter' ) }
						onChange={ ( emptyLabel ) =>
							setAttributes( { emptyLabel } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'wp-block-query-filter' } ) }>
				{ showLabel && (
					<label className="wp-block-query-filter-post-type__label wp-block-query-filter__label">
						{ label || __( 'Content Type', 'query-filter' ) }
					</label>
				) }
				{ controlType === 'select' && (
					<select
						className="wp-block-query-filter-post-type__select wp-block-query-filter__select"
						inert
					>
						<option>
							{ emptyLabel || __( 'All', 'query-filter' ) }
						</option>
						{ postTypes.map( ( type ) => (
							<option key={ type.slug }>{ type.name }</option>
						) ) }
					</select>
				) }
				{ controlType === 'radio' && (
					<div className="wp-block-query-filter__terms" inert>
						<button type="button" className="wp-block-query-filter__reset">
							{ emptyLabel || __( 'All', 'query-filter' ) }
						</button>
						{ postTypes.map( ( type ) => (
							<div key={ type.slug } className="wp-block-query-filter__term">
								<input type="radio" className="wp-block-query-filter__radio" />
								<label>{ type.name }</label>
							</div>
						) ) }
					</div>
				) }
				{ controlType === 'checkbox' && (
					<div className="wp-block-query-filter__terms" inert>
						<button type="button" className="wp-block-query-filter__reset">
							{ emptyLabel || __( 'All', 'query-filter' ) }
						</button>
						{ postTypes.map( ( type ) => (
							<div key={ type.slug } className="wp-block-query-filter__term">
								<input type="checkbox" className="wp-block-query-filter__checkbox" />
								<label>{ type.name }</label>
							</div>
						) ) }
					</div>
				) }
			</div>
		</>
	);
}
