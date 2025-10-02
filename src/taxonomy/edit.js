import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes } ) {
	const {
		taxonomy,
		emptyLabel,
		label,
		showLabel,
		showResetButton = true,
		operator = 'IN',
		resetPosition = 'before',
		hideZeroCountTerms = true,
		showCounts = false,
	} = attributes;

	const taxonomies = useSelect(
		( select ) => {
			const results = (
				select( 'core' ).getTaxonomies( { per_page: 100 } ) || []
			).filter( ( taxonomy ) => taxonomy.visibility.publicly_queryable );

			if ( results && results.length > 0 && ! taxonomy ) {
				setAttributes( {
					taxonomy: results[ 0 ].slug,
					label: results[ 0 ].name,
				} );
			}

			return results;
		},
		[ taxonomy ]
	);
	const terms = useSelect(
		( select ) => {
			return (
				select( 'core' ).getEntityRecords( 'taxonomy', taxonomy, {
					number: 50,
				} ) || []
			);
		},
		[ taxonomy ]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Paramètres de taxonomie', 'up-gutenberg-query-filter' ) }>
					<SelectControl
						label={ __( 'Choisir une taxonomie', 'up-gutenberg-query-filter' ) }
						value={ taxonomy }
						options={ ( taxonomies || [] ).map( ( taxonomy ) => ( {
							label: taxonomy.name,
							value: taxonomy.slug,
						} ) ) }
						onChange={ ( nextTax ) =>
							setAttributes( {
								taxonomy: nextTax,
								label:
									( taxonomies || [] ).find( ( t ) => t.slug === nextTax )?.name || label,
							} )
						}
					/>
					<TextControl
						label={ __( 'Libellé', 'up-gutenberg-query-filter' ) }
						value={ label }
						help={ __( 'Si vide, aucun libellé ne sera affiché', 'up-gutenberg-query-filter' ) }
						onChange={ ( next ) => setAttributes( { label: next } ) }
					/>
					<ToggleControl
						label={ __( 'Afficher le libellé', 'up-gutenberg-query-filter' ) }
						checked={ !! showLabel }
						onChange={ ( next ) => setAttributes( { showLabel: next } ) }
					/>
					<ToggleControl
						label={ __( 'Afficher le bouton "Tous"', 'up-gutenberg-query-filter' ) }
						checked={ !! showResetButton }
						help={ __( 'Afficher ou masquer le bouton "Tous"', 'up-gutenberg-query-filter' ) }
						onChange={ ( next ) => setAttributes( { showResetButton: next } ) }
					/>
					{ showResetButton && (
						<SelectControl
							label={ __( 'Position du bouton de réinitialisation', 'up-gutenberg-query-filter' ) }
							value={ resetPosition }
							options={ [
								{ label: __( 'Avant les termes', 'up-gutenberg-query-filter' ), value: 'before' },
								{ label: __( 'Après les termes', 'up-gutenberg-query-filter' ), value: 'after' },
							] }
							onChange={ ( next ) => setAttributes( { resetPosition: next } ) }
						/>
					) }
					<SelectControl
						label={ __( 'Opérateur (multi-sélection)', 'up-gutenberg-query-filter' ) }
						value={ operator }
						options={ [
							{ label: __( 'OU (IN)', 'up-gutenberg-query-filter' ), value: 'IN' },
							{ label: __( 'ET', 'up-gutenberg-query-filter' ), value: 'AND' },
						] }
						help={ __( 'Comment combiner plusieurs termes : OU (IN) ou ET (AND).', 'up-gutenberg-query-filter' ) }
						onChange={ ( next ) => setAttributes( { operator: next } ) }
					/>
					{ showResetButton && (
						<TextControl
							label={ __( 'Libellé pour "Tous"', 'up-gutenberg-query-filter' ) }
							value={ emptyLabel }
							placeholder={ __( 'Tous', 'up-gutenberg-query-filter' ) }
							onChange={ ( next ) => setAttributes( { emptyLabel: next } ) }
						/>
					) }
					<ToggleControl
						label={ __( 'Masquer les termes à 0 résultat', 'up-gutenberg-query-filter' ) }
						checked={ !! hideZeroCountTerms }
						help={ __( 'Masquer les termes qui retourneraient 0 résultats (hors cases cochées).', 'up-gutenberg-query-filter' ) }
						onChange={ ( next ) => setAttributes( { hideZeroCountTerms: next } ) }
					/>
					<ToggleControl
						label={ __( 'Afficher les compteurs à côté des termes', 'up-gutenberg-query-filter' ) }
						checked={ !! showCounts }
						help={ __( 'Afficher le nombre d’éléments correspondant pour chaque terme.', 'up-gutenberg-query-filter' ) }
						onChange={ ( next ) => setAttributes( { showCounts: next } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'wp-block-query-filter' } ) }>
				{ showLabel && (
					<label className="wp-block-query-filter-taxonomy__label wp-block-query-filter__label">
						{ label }
					</label>
				) }
				<select
					className="wp-block-query-filter-taxonomy__select wp-block-query-filter__select"
					inert
				>
					{ showResetButton && (
						<option>
							{ emptyLabel || __( 'Tous', 'up-gutenberg-query-filter' ) }
						</option>
					) }
					{ terms.map( ( term ) => (
						<option key={ term.slug } value={ term.slug }>{ term.name }</option>
					) ) }
				</select>
			</div>
		</>
	);
}
