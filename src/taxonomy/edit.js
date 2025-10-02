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
				<PanelBody title={ __( 'Taxonomy Settings', 'query-filter' ) }>
					<SelectControl
						label={ __( 'Select Taxonomy', 'query-filter' ) }
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
						label={ __( 'Label', 'query-filter' ) }
						value={ label }
						help={ __( 'If empty then no label will be shown', 'query-filter' ) }
						onChange={ ( next ) => setAttributes( { label: next } ) }
					/>
					<ToggleControl
						label={ __( 'Show Label', 'query-filter' ) }
						checked={ !! showLabel }
						onChange={ ( next ) => setAttributes( { showLabel: next } ) }
					/>
					<ToggleControl
						label={ __( 'Show Reset Button', 'query-filter' ) }
						checked={ !! showResetButton }
						help={ __( 'Show or hide the "All" reset button', 'query-filter' ) }
						onChange={ ( next ) => setAttributes( { showResetButton: next } ) }
					/>
					{ showResetButton && (
						<SelectControl
							label={ __( 'Reset Button Position', 'query-filter' ) }
							value={ resetPosition }
							options={ [
								{ label: __( 'Before terms', 'query-filter' ), value: 'before' },
								{ label: __( 'After terms', 'query-filter' ), value: 'after' },
							] }
							onChange={ ( next ) => setAttributes( { resetPosition: next } ) }
						/>
					) }
					<SelectControl
						label={ __( 'Operator (multi-select)', 'query-filter' ) }
						value={ operator }
						options={ [
							{ label: __( 'OR (IN)', 'query-filter' ), value: 'IN' },
							{ label: __( 'AND', 'query-filter' ), value: 'AND' },
						] }
						help={ __( 'Comment combiner plusieurs termes: OU (IN) ou ET (AND).', 'query-filter' ) }
						onChange={ ( next ) => setAttributes( { operator: next } ) }
					/>
					{ showResetButton && (
						<TextControl
							label={ __( 'Empty Choice Label', 'query-filter' ) }
							value={ emptyLabel }
							placeholder={ __( 'All', 'query-filter' ) }
							onChange={ ( next ) => setAttributes( { emptyLabel: next } ) }
						/>
					) }
					<ToggleControl
						label={ __( 'Hide zero-result terms', 'query-filter' ) }
						checked={ !! hideZeroCountTerms }
						help={ __( 'Hide terms that would currently return 0 posts (unchecked terms only).', 'query-filter' ) }
						onChange={ ( next ) => setAttributes( { hideZeroCountTerms: next } ) }
					/>
					<ToggleControl
						label={ __( 'Show counts next to terms', 'query-filter' ) }
						checked={ !! showCounts }
						help={ __( 'Display the number of matching posts next to each term.', 'query-filter' ) }
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
							{ emptyLabel || __( 'All', 'query-filter' ) }
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
