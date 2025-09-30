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
    const { actions } = await import( '@wordpress/interactivity-router' );
    await actions.navigate( url.toString() );
};

const getContainer = ( node ) => node.closest( '[data-base-url]' );
const getSelectedValues = ( container ) =>
    Array.from(
        container.querySelectorAll( '.wp-block-query-filter__checkbox:checked' )
    ).map( ( el ) => el.value );

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
            yield navigateTaxonomy( baseUrl, queryVar, pageVar, [], operator );
        },
    },
} );
