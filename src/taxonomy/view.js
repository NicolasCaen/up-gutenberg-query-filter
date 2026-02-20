import { store, getElement } from '@wordpress/interactivity';

const REGION_SELECTOR = '[data-wp-router-region="query-filter"]';

// Core SPA swap: fetch url, extract region, replace DOM.
// When pushUrl is true a new history entry is created; false for popstate.
const spaSwap = async ( url, pushUrl = true ) => {
    const region = document.querySelector( REGION_SELECTOR );
    if ( ! region ) {
        window.location.assign( url );
        return;
    }

    try {
        const response = await fetch( url, {
            headers: { 'Accept': 'text/html' },
            credentials: 'same-origin',
        } );
        if ( ! response.ok ) {
            window.location.assign( url );
            return;
        }
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString( html, 'text/html' );
        const newRegion = doc.querySelector( REGION_SELECTOR );

        if ( ! newRegion ) {
            window.location.assign( url );
            return;
        }

        region.innerHTML = newRegion.innerHTML;

        if ( pushUrl ) {
            window.history.pushState( {}, '', url );
        }

        // Dispatch event so other scripts know the region was updated.
        document.dispatchEvent( new Event( 'query-filter:navigated' ) );
    } catch ( err ) {
        console.error( '[qf] SPA navigate failed, falling back', err );
        window.location.assign( url );
    }
};

const navigate = async ( url ) => {
    // Normalise both URLs before comparing to avoid false positives.
    const target = new URL( url, window.location.origin );
    const current = new URL( window.location.href );
    if ( target.href === current.href ) return;
    await spaSwap( url, true );
};

const updateURL = async ( action, value, name ) => {
    const url = new URL( action );
    if ( value || name === 's' ) {
        url.searchParams.set( name, value );
    } else {
        url.searchParams.delete( name );
    }
    await navigate( url.toString() );
};

const pendingNavigation = new WeakMap();

const scheduleNavigateTaxonomy = ( container, delayMs = 150 ) => {
    const baseUrl = container.dataset.baseUrl;
    const queryVar = container.dataset.queryVar;
    const pageVar = container.dataset.pageVar;
    const operator = container.dataset.operator || 'IN';

    const prev = pendingNavigation.get( container );
    if ( prev?.timer ) {
        clearTimeout( prev.timer );
    }

    const timer = setTimeout( () => {
        const values = getSelectedValues( container );
        navigateTaxonomy( baseUrl, queryVar, pageVar, values, operator );
    }, delayMs );

    pendingNavigation.set( container, { timer } );
};

const navigateTaxonomy = async ( baseUrl, queryVar, pageVar, value, operator ) => {
    // Start from current URL to preserve other active filters, then patch only this taxonomy's param.
    const url = new URL( window.location.href );
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
    // eslint-disable-next-line no-console
    console.debug('[qf] navigateTaxonomy', { baseUrl, queryVar, pageVar, value, operator, url: url.toString() });
    await spaSwap( url.toString(), true );
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
                    // Ensure there is exactly one .term-count, remove extras if any
                    const countSpans = termEl.querySelectorAll( '.term-count' );
                    let countSpan = countSpans[0] || null;
                    if ( countSpans.length > 1 ) {
                        for ( let i = 1; i < countSpans.length; i++ ) {
                            countSpans[i].remove();
                        }
                    }
                    if ( ! countSpan ) {
                        const label = termEl.querySelector( 'label' );
                        countSpan = document.createElement( 'span' );
                        countSpan.className = 'term-count';
                        if ( label ) {
                            label.appendChild( countSpan );
                        } else {
                            termEl.appendChild( countSpan );
                        }
                    }
                    // Update count only if different to avoid duplication
                    const expectedText = showCounts ? ` (${ termData.count })` : '';
                    if ( countSpan.textContent !== expectedText ) {
                        countSpan.textContent = expectedText;
                    }
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
                // Fallback: if the API didn't return this term, rely on existing markup/data-count
                else {
                    const countAttr = termEl.getAttribute( 'data-count' );
                    let count = null;
                    if ( countAttr !== null && countAttr !== '' ) {
                        count = parseInt( countAttr, 10 );
                    } else {
                        const countSpan = termEl.querySelector( '.term-count' );
                        if ( countSpan && countSpan.textContent ) {
                            const match = countSpan.textContent.match( /(\d+)/ );
                            if ( match ) count = parseInt( match[1], 10 );
                        }
                    }

                    if ( count !== null ) {
                        // Align inactive class with current checkbox state
                        if ( count === 0 && ! checkbox.checked ) {
                            termEl.classList.add( 'inactive' );
                            if ( markInactive ) {
                                termEl.style.display = '';
                            } else if ( hideZero ) {
                                termEl.style.display = 'none';
                            } else {
                                termEl.style.display = '';
                            }
                        } else {
                            termEl.classList.remove( 'inactive' );
                            termEl.style.display = '';
                        }
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
            yield navigate( e.target.value );
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
        *toggleTerm() {
            // Handled by native delegated listener on document.
        },
        *clearTerms() {
            // Handled by native delegated listener on document.
        },
    },
} );

// Prime missing data-count from server markup and align inactive classes
const primeCountsFromMarkup = () => {
    const containers = document.querySelectorAll( '[data-taxonomy]' );
    containers.forEach( ( container ) => {
        const markInactive = (container.dataset.markZeroInactive || 'false') === 'true';
        const termElements = container.querySelectorAll( '.wp-block-query-filter__term' );
        termElements.forEach( ( termEl ) => {
            const checkbox = termEl.querySelector( '.wp-block-query-filter__checkbox' );
            if ( ! checkbox ) return;
            let dataCount = termEl.getAttribute( 'data-count' );
            if ( dataCount === null || dataCount === '' ) {
                // Try to read from the visible label if present
                const countSpan = termEl.querySelector( '.term-count' );
                if ( countSpan && countSpan.textContent ) {
                    const match = countSpan.textContent.match( /(\d+)/ );
                    if ( match ) {
                        termEl.setAttribute( 'data-count', match[1] );
                        dataCount = match[1];
                    }
                }
            }
            // If we now have a numeric count, align inactive class immediately
            const numeric = dataCount !== null && dataCount !== '' ? parseInt( dataCount, 10 ) : null;
            if ( numeric !== null ) {
                if ( numeric === 0 && ! checkbox.checked ) {
                    if ( markInactive ) termEl.classList.add( 'inactive' );
                } else {
                    termEl.classList.remove( 'inactive' );
                }
            }
        } );
    } );
};

// Detect if the current URL has any query params used by our filters
const hasAnyFilterParams = () => {
    const params = new URLSearchParams( window.location.search );
    const containers = document.querySelectorAll( '[data-taxonomy]' );
    for ( const container of containers ) {
        const queryVar = container.dataset.queryVar;
        if ( queryVar && ( params.has( queryVar ) || params.has( `${ queryVar }-op` ) ) ) {
            return true;
        }
    }
    return false;
};

// Emulate a real click on the reset button so Interactivity actions (including navigation) run
// Returns true if a reset button was found and clicked
const triggerResetClickOnce = () => {
    const containers = document.querySelectorAll( '[data-taxonomy]' );
    // Prefer the first visible reset button to avoid duplicate navigations
    for ( const container of containers ) {
        const resetBtn = container.querySelector( '.wp-block-query-filter__reset' );
        if ( resetBtn ) {
            resetBtn.click();
            return true;
        }
    }
    return false;
};

// Native delegated listeners — survive any DOM swap, no WP Interactivity dependency.
// Use capture phase to fire before WP Interactivity's own handlers.
document.addEventListener( 'change', ( e ) => {
    const checkbox = e.target.closest( '.wp-block-query-filter__checkbox' );
    if ( ! checkbox ) return;
    const container = getContainer( checkbox );
    if ( ! container ) return;
    const values = getSelectedValues( container );
    const baseUrl = container.dataset.baseUrl;
    const queryVar = container.dataset.queryVar;
    const pageVar = container.dataset.pageVar;
    const operator = container.dataset.operator || 'IN';
    // eslint-disable-next-line no-console
    console.debug( '[qf] native change', { values, baseUrl, queryVar, pageVar, operator } );
    scheduleNavigateTaxonomy( container );
}, true );

document.addEventListener( 'click', ( e ) => {
    const btn = e.target.closest( '.wp-block-query-filter__reset' );
    if ( ! btn ) return;
    const container = getContainer( btn );
    if ( ! container ) return;
    container
        .querySelectorAll( '.wp-block-query-filter__checkbox:checked' )
        .forEach( ( el ) => ( el.checked = false ) );
    scheduleNavigateTaxonomy( container, 0 );
}, true );

// Initialize term visibility and counts on first load
const initUpdate = () => {
    // Small timeout to ensure DOM is fully hydrated
    setTimeout( () => {
        // Prime state from existing markup (useful on direct visits without query params)
        primeCountsFromMarkup();
        // Never auto-navigate on load: only compute counts/visibility.
        updateTermsVisibility();
    }, 0 );
};

if ( document.readyState === 'loading' ) {
    document.addEventListener( 'DOMContentLoaded', initUpdate );
} else {
    initUpdate();
}

// Handle browser Back / Forward navigation.
window.addEventListener( 'popstate', () => {
    const region = document.querySelector( REGION_SELECTOR );
    if ( ! region ) return;
    // Re-fetch current (popped) URL and swap the query region (no pushState).
    spaSwap( window.location.href, false ).catch( () => {
        window.location.reload();
    } );
} );

// Recompute counts/visibility after every SPA swap.
document.addEventListener( 'query-filter:navigated', () => {
    updateTermsVisibility();
} );

// External refresh hook (e.g., active-filters clear-all chip)
document.addEventListener( 'query-filter:refresh', () => {
    updateTermsVisibility();
} );
