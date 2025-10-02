import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes, context } ) {
	const { emptyLabel, label, showLabel } = attributes;

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
				<PanelBody title={ __( 'Paramètres du type de contenu', 'up-gutenberg-query-filter' ) }>
					<TextControl
						label={ __( 'Libellé', 'up-gutenberg-query-filter' ) }
						value={ label }
						defaultValue={ __( 'Type de contenu', 'up-gutenberg-query-filter' ) }
						help={ __( 'Si vide, aucun libellé ne sera affiché', 'up-gutenberg-query-filter' ) }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>
					<ToggleControl
						label={ __( 'Afficher le libellé', 'up-gutenberg-query-filter' ) }
						checked={ showLabel }
						onChange={ ( showLabel ) =>
							setAttributes( { showLabel } )
						}
					/>
					<TextControl
						label={ __( 'Libellé pour "Tous"', 'up-gutenberg-query-filter' ) }
						value={ emptyLabel }
						placeholder={ __( 'Tous', 'up-gutenberg-query-filter' ) }
						onChange={ ( emptyLabel ) =>
							setAttributes( { emptyLabel } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'wp-block-query-filter' } ) }>
				{ showLabel && (
					<label className="wp-block-query-filter-post-type__label wp-block-query-filter__label">
						{ label || __( 'Type de contenu', 'up-gutenberg-query-filter' ) }
					</label>
				) }
				<select
					className="wp-block-query-filter-post-type__select wp-block-query-filter__select"
					inert
				>
					<option>
						{ emptyLabel || __( 'Tous', 'up-gutenberg-query-filter' ) }
					</option>
					{ postTypes.map( ( type ) => (
						<option key={ type.slug }>{ type.name }</option>
					) ) }
				</select>
			</div>
		</>
	);
}
