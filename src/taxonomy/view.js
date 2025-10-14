import { store, getElement } from '@wordpress/interactivity';

const updateURL = async ( action, value, name ) => {
    const url = new URL( action );
    if ( value || name === 's' ) {
        url.searchParams.set( name, value );
    } else {
        url.searchParams.delete( name );
    }
    const { actions } = await import( '@wordpress/interactivity-router' );
    await actions.navigate( url.toString() );
};

const navigateTaxonomy = async ( baseUrl, queryVar, pageVar, value, operator ) => {
    const url = new URL( baseUrl, window.location.origin );
    if ( value && value.length ) {
        url.searchParams.set( queryVar, value.join( ',' ) );
        if ( operator ) {
            url.searchParams.set( `${ queryVar }-op`, operator );
        }
    } else {
        url.searchParams.delete( queryVar );
        url.searchParams.delete( `${ queryVar }-op` );
    }
    // Reset pagination when changing filters
    url.searchParams.delete( pageVar );
    // Debug navigation params
    // eslint-disable-next-line no-console
    console.debug('[qf] navigateTaxonomy', { baseUrl, queryVar, pageVar, value, operator, url: url.toString() });
    const { actions } = await import( '@wordpress/interactivity-router' );
    await actions.navigate( url.toString() );
};

const getContainer = ( node ) => node.closest( '[data-base-url]' );
const getSelectedValues = ( container ) =>
    Array.from(
        container.querySelectorAll( '.wp-block-query-filter__checkbox:checked' )
    ).map( ( el ) => el.value );

const getAllFilters = () => {
    const filters = {};
    const allContainers = document.querySelectorAll( '[data-taxonomy]' );
    
    allContainers.forEach( ( container ) => {
        const taxonomy = container.dataset.taxonomy;
        const operator = container.dataset.operator || 'IN';
        const values = getSelectedValues( container );
        
        if ( values.length > 0 ) {
            filters[ taxonomy ] = {
                values,
                operator,
            };
        }
    } );
    
    return filters;
};

const updateTermsVisibility = async ( changedContainer ) => {
    const allContainers = document.querySelectorAll( '[data-taxonomy]' );
    const currentFilters = getAllFilters();
    // Update each filter container
    for ( const container of allContainers ) {
        const taxonomy = container.dataset.taxonomy;
        const queryId = container.dataset.queryId || 0;
        const postType = container.dataset.postType || 'post';
        const hideZero = (container.dataset.hideZeroTerms || 'true') === 'true';
        const markInactive = (container.dataset.markZeroInactive || 'false') === 'true';
        const showCounts = (container.dataset.showCounts || 'false') === 'true';
        const useArchive = (container.dataset.useArchiveTerm || 'false') === 'true';
        const archiveTermId = parseInt(container.dataset.archiveTermId || '0', 10);
        const archiveTaxonomy = container.dataset.archiveTaxonomy || '';
        
        // Build filters excluding current taxonomy
        const filtersForRequest = { ...currentFilters };
        delete filtersForRequest[ taxonomy ];
        
        try {
            const fetchUrl = `/wp-json/query-filter/v1/available-terms?taxonomy=${ taxonomy }&query_id=${ queryId }&post_type=${ postType }&use_archive=${ useArchive ? '1' : '0' }&archive_term_id=${ archiveTermId }&archive_taxonomy=${ encodeURIComponent( archiveTaxonomy ) }&filters=${ encodeURIComponent( JSON.stringify( filtersForRequest ) ) }`;
            // eslint-disable-next-line no-console
            console.debug('[qf] fetch available-terms', { taxonomy, queryId, postType, useArchive, archiveTermId, archiveTaxonomy, filtersForRequest, fetchUrl });
            const response = await fetch( fetchUrl );
            
            if ( ! response.ok ) {
                // eslint-disable-next-line no-console
                console.debug('[qf] fetch not ok', response.status, response.statusText);
                continue;
            }
            
            const data = await response.json();
            // eslint-disable-next-line no-console
            console.debug('[qf] available-terms data', data);
            
            // Update visibility of terms
            const termElements = container.querySelectorAll( '.wp-block-query-filter__term' );
            termElements.forEach( ( termEl ) => {
                const checkbox = termEl.querySelector( '.wp-block-query-filter__checkbox' );
                if ( ! checkbox ) return;
                
                const termSlug = checkbox.value;
                const termData = data.terms.find( ( t ) => t.slug === termSlug );
                
                if ( termData ) {
                    // Update count span FIRST (always update, visibility controlled by showCounts)
                    let countSpan = termEl.querySelector( '.term-count' );
                    if ( ! countSpan ) {
                        // Ensure a count span exists to avoid stale server markup
                        const label = termEl.querySelector( 'label' );
                        countSpan = document.createElement( 'span' );
                        countSpan.className = 'term-count';
                        if ( label ) {
                            label.appendChild( countSpan );
                        } else {
                            termEl.appendChild( countSpan );
                        }
                    }
                    countSpan.textContent = showCounts ? ` (${ termData.count })` : '';
                    // eslint-disable-next-line no-console
                    console.debug('[qf] update count', { termSlug, showCounts, count: termData.count, text: countSpan.textContent });
                    
                    // Update data-count attribute (always in sync with visual count)
                    termEl.setAttribute( 'data-count', termData.count );
                    
                    const isZeroAndUnchecked = termData.count === 0 && ! checkbox.checked;

                    // Apply inactive class: never mark as inactive if the term is checked
                    if ( termData.count === 0 && ! checkbox.checked ) {
                        termEl.classList.add( 'inactive' );
                    } else {
                        termEl.classList.remove( 'inactive' );
                    }

                    // Handle visibility (inactive takes precedence over hide)
                    if ( isZeroAndUnchecked ) {
                        if ( markInactive ) {
                            termEl.style.display = '';
                        } else if ( hideZero ) {
                            termEl.style.display = 'none';
                        } else {
                            termEl.style.display = '';
                        }
                    } else {
                        termEl.style.display = '';
                    }
                }
            } );
        } catch ( error ) {
            console.error( 'Error updating terms visibility:', error );
        }
    }
};

const { state } = store( 'query-filter', {
    actions: {
        *navigate( e ) {
            e.preventDefault();
            const { actions } = yield import(
                '@wordpress/interactivity-router'
            );
            yield actions.navigate( e.target.value );
        },
        *search( e ) {
            e.preventDefault();
            const { ref } = getElement();
            let action, name, value;
            if ( ref.tagName === 'FORM' ) {
                const input = ref.querySelector( 'input[type="search"]' );
                action = ref.action;
                name = input.name;
                value = input.value;
            } else {
                action = ref.closest( 'form' ).action;
                name = ref.name;
                value = ref.value;
            }

            // Don't navigate if the search didn't really change.
            if ( value === state.searchValue ) return;

            state.searchValue = value;

            yield updateURL( action, value, name );
        },
        *toggleTerm( e ) {
            const { ref } = getElement();
            const container = getContainer( ref );
            const values = getSelectedValues( container );
            const baseUrl = container.dataset.baseUrl;
            const queryVar = container.dataset.queryVar;
            const pageVar = container.dataset.pageVar;
            const operator = container.dataset.operator || 'IN';
            // eslint-disable-next-line no-console
            console.debug('[qf] toggleTerm', { values, baseUrl, queryVar, pageVar, operator });
            
            // Update terms visibility before navigation
            yield updateTermsVisibility( container );
            
            yield navigateTaxonomy( baseUrl, queryVar, pageVar, values, operator );
        },
        *clearTerms() {
            const { ref } = getElement();
            const container = getContainer( ref );
            const baseUrl = container.dataset.baseUrl;
            const queryVar = container.dataset.queryVar;
            const pageVar = container.dataset.pageVar;
            // Uncheck all
            container
                .querySelectorAll( '.wp-block-query-filter__checkbox:checked' )
                .forEach( ( el ) => ( el.checked = false ) );
            const operator = container.dataset.operator || 'IN';
            // eslint-disable-next-line no-console
            console.debug('[qf] clearTerms', { baseUrl, queryVar, pageVar, operator });
            
            // Update terms visibility after clearing
            yield updateTermsVisibility( container );
            
            yield navigateTaxonomy( baseUrl, queryVar, pageVar, [], operator );
        },
    },
} );

// Initialize term visibility and counts on first load
const initUpdate = () => {
    // Small timeout to ensure DOM is fully hydrated (for SSR/BlockEditor front-end)
    setTimeout( () => {
        updateTermsVisibility();
    }, 0 );
};

if ( document.readyState === 'loading' ) {
    document.addEventListener( 'DOMContentLoaded', initUpdate );
} else {
    initUpdate();
}

// External refresh hook (e.g., active-filters clear-all chip)
document.addEventListener( 'query-filter:refresh', () => {
    updateTermsVisibility();
} );
