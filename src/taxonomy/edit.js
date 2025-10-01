import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
export default function Edit( { attributes, setAttributes } ) {
    const {
        taxonomy,
        controlType = 'checkbox',
        emptyLabel,
        label,
        showLabel,
        showAllButton = false,
        hideZeroResults = false,
        operator = 'IN',
    } = attributes;

    const taxonomies = useSelect( ( select ) => {
        const results = ( select( 'core' ).getTaxonomies( { per_page: 100 } ) || [] )
            .filter( ( t ) => t?.visibility?.publicly_queryable );
        if ( results && results.length > 0 && ! taxonomy ) {
            setAttributes( { taxonomy: results[0].slug, label: results[0].name } );
        }
        return results;
    }, [ taxonomy ] );

    const terms = useSelect( ( select ) => (
        select( 'core' ).getEntityRecords( 'taxonomy', taxonomy, { number: 50 } ) || []
    ), [taxonomy ] );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Taxonomy Settings', 'query-filter' ) }>
                    <SelectControl
                        label={ __( 'Select Taxonomy', 'query-filter' ) }
                        value={ taxonomy }
                        options={ ( taxonomies || [] ).map( ( t ) => ( { label: t.name, value: t.slug } ) ) }
                        onChange={ ( value ) => setAttributes( { taxonomy: value, label: ( taxonomies || [] ).find( ( t ) => t.slug === value )?.name } ) }
                    />
                    <TextControl
                        label={ __( 'Label', 'query-filter' ) }
                        value={ label }
                        help={ __( 'If empty then no label will be shown', 'query-filter' ) }
                        onChange={ ( value ) => setAttributes( { label: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Label', 'query-filter' ) }
                        checked={ !! showLabel }
                        onChange={ ( value ) => setAttributes( { showLabel: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show "All" button', 'query-filter' ) }
                        checked={ !! showAllButton }
                        onChange={ ( value ) => setAttributes( { showAllButton: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Hide terms with 0 results (based on other filters)', 'query-filter' ) }
                        checked={ !! hideZeroResults }
                        onChange={ ( value ) => setAttributes( { hideZeroResults: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Control Type', 'query-filter' ) }
                        value={ controlType }
                        options={ [
                            { label: __( 'Checkboxes (multiple)', 'query-filter' ), value: 'checkbox' },
                            { label: __( 'Tag Buttons (multiple)', 'query-filter' ), value: 'tag-buttons' },
                            { label: __( 'Search Multi (multiple)', 'query-filter' ), value: 'search-multi' },
                            { label: __( 'Radio (single)', 'query-filter' ), value: 'radio' },
                            { label: __( 'Select (single)', 'query-filter' ), value: 'select' },
                        ] }
                        onChange={ ( value ) => setAttributes( { controlType: value } ) }
                    />
                    { controlType === 'checkbox' && (
                        <SelectControl
                            label={ __( 'Operator (multi-select)', 'query-filter' ) }
                            value={ operator }
                            options={ [
                                { label: __( 'OR (IN)', 'query-filter' ), value: 'IN' },
                                { label: __( 'AND', 'query-filter' ), value: 'AND' },
                            ] }
                            onChange={ ( value ) => setAttributes( { operator: value } ) }
                        />
                    ) }
                </PanelBody>
            </InspectorControls>

            <div { ...useBlockProps( { className: 'wp-block-query-filter' } ) }>
                { showLabel && (
                    <label className="wp-block-query-filter-taxonomy__label wp-block-query-filter__label">
                        { label }
                    </label>
                ) }
                { controlType === 'select' && (
                    <select className="wp-block-query-filter-taxonomy__select wp-block-query-filter__select" inert>
                        { !! showAllButton && (
                            <option>{ emptyLabel || __( 'All', 'query-filter' ) }</option>
                        ) }
                        { ( terms || [] ).map( ( term ) => (
                            <option key={ term.slug }>{ term.name }</option>
                        ) ) }
                    </select>
                ) }
                { controlType === 'radio' && (
                    <div className="wp-block-query-filter__terms" inert>
                        { !! showAllButton && (
                            <button type="button" className="wp-block-query-filter__reset">
                                { emptyLabel || __( 'All', 'query-filter' ) }
                            </button>
                        ) }
                        { ( terms || [] ).map( ( term ) => (
                            <div key={ term.slug } className="wp-block-query-filter__term">
                                <input type="radio" className="wp-block-query-filter__radio" />
                                <label>{ term.name }</label>
                            </div>
                        ) ) }
                    </div>
                ) }
                { controlType === 'checkbox' && (
                    <div className="wp-block-query-filter__terms" inert>
                        { !! showAllButton && (
                            <button type="button" className="wp-block-query-filter__reset">
                                { emptyLabel || __( 'All', 'query-filter' ) }
                            </button>
                        ) }
                        { ( terms || [] ).map( ( term ) => (
                            <div key={ term.slug } className="wp-block-query-filter__term">
                                <input type="checkbox" className="wp-block-query-filter__checkbox" />
                                <label>{ term.name }</label>
                            </div>
                        ) ) }
                    </div>
                ) }
                { controlType === 'tag-buttons' && (
                    <div className="wp-block-query-filter__terms" inert>
                        { !! showAllButton && (
                            <button type="button" className="wp-block-query-filter__reset">
                                { emptyLabel || __( 'All', 'query-filter' ) }
                            </button>
                        ) }
                        { ( terms || [] ).map( ( term ) => (
                            <button key={ term.slug } type="button" className={`tag-btn__${taxonomy } tag-btn__${taxonomy }--${ term.slug }`}>
                                { term.name }
                            </button>
                        ) ) }
                    </div>
                ) }
                { controlType === 'search-multi' && (
                    <div className="wp-block-query-filter__typeahead" inert>
                        <div className="typeahead__tokens">{/* tokens preview */}</div>
                        <input className="typeahead__input" placeholder={ __( 'Rechercher…', 'query-filter' ) } />
                        <ul className="wp-block-query-filter__suggestions"></ul>
                        { !! showAllButton && (
                            <button type="button" className="wp-block-query-filter__reset">
                                { emptyLabel || __( 'All', 'query-filter' ) }
                            </button>
                        ) }
                    </div>
                ) }
            </div>
        </>
    );
}
