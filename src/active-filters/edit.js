import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { showClearAll = true, clearAllLabel = 'Effacer tout' } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Paramètres des filtres actifs', 'up-gutenberg-query-filter' ) }>
					<ToggleControl
						label={ __( 'Afficher « Effacer tout »', 'up-gutenberg-query-filter' ) }
						checked={ showClearAll }
						onChange={ ( showClearAll ) => setAttributes( { showClearAll } ) }
					/>
					{ showClearAll && (
						<TextControl
							label={ __( 'Libellé « Effacer tout »', 'up-gutenberg-query-filter' ) }
							value={ clearAllLabel }
							onChange={ ( clearAllLabel ) => setAttributes( { clearAllLabel } ) }
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'wp-block-query-filter-active-filters' } ) }>
				<div className="wp-block-query-filter-active-filters__chips" aria-hidden>
					<span className="wp-block-query-filter-chip is-placeholder">{ __( 'Les filtres actifs s\'afficheront ici…', 'up-gutenberg-query-filter' ) }</span>
					{ showClearAll && (
						<span className="wp-block-query-filter-chip is-clear">{ clearAllLabel }</span>
					) }
				</div>
			</div>
		</>
	);
}
