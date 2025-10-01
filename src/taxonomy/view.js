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

const navigateTaxonomy = async ( baseUrl, queryVar, opVar, pageVar, value, operator ) => {
    const url = new URL( baseUrl, window.location.origin );
    if ( value && value.length ) {
        url.searchParams.set( queryVar, value.join( ',' ) );
        if ( operator && opVar ) {
            url.searchParams.set( opVar, operator );
        }
    } else {
        url.searchParams.delete( queryVar );
        if ( opVar ) url.searchParams.delete( opVar );
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
            const opVar = container.dataset.opVar;
            const pageVar = container.dataset.pageVar;
            const operator = container.dataset.operator || 'IN';
            yield navigateTaxonomy( baseUrl, queryVar, opVar, pageVar, values, operator );
        },
        *clearTerms() {
            const { ref } = getElement();
            const container = getContainer( ref );
            const baseUrl = container.dataset.baseUrl;
            const queryVar = container.dataset.queryVar;
            const opVar = container.dataset.opVar;
            const pageVar = container.dataset.pageVar;
            const operator = container.dataset.operator || 'IN';
            // Uncheck checkboxes
            container
                .querySelectorAll( '.wp-block-query-filter__checkbox:checked' )
                .forEach( ( el ) => ( el.checked = false ) );
            // Deactivate tag buttons
            container
                .querySelectorAll( '.is-active[data-term-value]' )
                .forEach( ( el ) => el.classList.remove( 'is-active' ) );
            // Remove tokens
            container
                .querySelectorAll( '.wp-block-query-filter__typeahead .typeahead__tokens [data-term-value]' )
                .forEach( ( el ) => el.remove() );
            // Navigate with empty selection
            yield navigateTaxonomy( baseUrl, queryVar, opVar, pageVar, [], operator );
        },
        *toggleTagButton( e ) {
            const { ref } = getElement();
            const btn = ref;
            const container = getContainer( ref );
            const baseUrl = container.dataset.baseUrl;
            const queryVar = container.dataset.queryVar;
            const opVar = container.dataset.opVar;
            const pageVar = container.dataset.pageVar;
            const operator = container.dataset.operator || 'IN';
            btn.classList.toggle( 'is-active' );
            const values = Array.from(
                container.querySelectorAll( '.is-active[data-term-value]' )
            ).map( ( el ) => el.getAttribute( 'data-term-value' ) );
            yield navigateTaxonomy( baseUrl, queryVar, opVar, pageVar, values, operator );
        },
        *typeahead( e ) {
            const { ref } = getElement();
            const container = getContainer( ref );
            const baseUrl = container.dataset.baseUrl;
            const queryVar = container.dataset.queryVar;
            const opVar = container.dataset.opVar;
            const pageVar = container.dataset.pageVar;
            const operator = container.dataset.operator || 'IN';
            const root = ref.closest( '.wp-block-query-filter__typeahead' );
            const input = root.querySelector( '.typeahead__input' );
            const list = root.querySelector( '.wp-block-query-filter__suggestions' );
            const terms = JSON.parse( root.dataset.terms || '[]' );
            const selected = Array.from(
                root.querySelectorAll( '.typeahead__tokens [data-term-value]' )
            ).map( ( el ) => el.getAttribute( 'data-term-value' ) );
            const q = ( input.value || '' ).toLowerCase();
            list.innerHTML = '';
            if ( q.length === 0 ) return;
            const matches = terms.filter( ( t ) =>
                t.slug.toLowerCase().includes( q ) || t.name.toLowerCase().includes( q )
            ).filter( ( t ) => ! selected.includes( t.slug ) ).slice( 0, 8 );
            for ( const t of matches ) {
                const li = document.createElement( 'li' );
                li.textContent = t.name;
                li.setAttribute( 'role', 'option' );
                li.dataset.termValue = t.slug;
                li.addEventListener( 'click', async () => {
                    // add token
                    const tokens = root.querySelector( '.typeahead__tokens' );
                    const span = document.createElement( 'span' );
                    span.className = `token tag-btn__${ queryVar.replace(/^q\d*-/, '').replace(/^q-/, '') } tag-btn__${ queryVar.replace(/^q\d*-/, '').replace(/^q-/, '') }--${ t.slug }`;
                    span.dataset.termValue = t.slug;
                    span.textContent = t.name + ' ';
                    const rm = document.createElement( 'button' );
                    rm.type = 'button';
                    rm.className = 'token__remove';
                    rm.setAttribute( 'aria-label', 'Remove' );
                    rm.textContent = '×';
                    rm.addEventListener( 'click', async () => {
                        span.remove();
                        const values = Array.from( root.querySelectorAll( '.typeahead__tokens [data-term-value]' ) ).map( ( el ) => el.getAttribute( 'data-term-value' ) );
                        await navigateTaxonomy( baseUrl, queryVar, opVar, pageVar, values, operator );
                    } );
                    span.appendChild( rm );
                    tokens.appendChild( span );
                    input.value = '';
                    list.innerHTML = '';
                    const values = Array.from( root.querySelectorAll( '.typeahead__tokens [data-term-value]' ) ).map( ( el ) => el.getAttribute( 'data-term-value' ) );
                    await navigateTaxonomy( baseUrl, queryVar, opVar, pageVar, values, operator );
                } );
                list.appendChild( li );
            }
        },
        *removeToken( e ) {
            const { ref } = getElement();
            const token = ref.closest( '[data-term-value]' );
            const root = ref.closest( '.wp-block-query-filter__typeahead' );
            if ( token && root ) token.remove();
            const container = getContainer( ref );
            const baseUrl = container.dataset.baseUrl;
            const queryVar = container.dataset.queryVar;
            const opVar = container.dataset.opVar;
            const pageVar = container.dataset.pageVar;
            const operator = container.dataset.operator || 'IN';
            const values = Array.from( root.querySelectorAll( '.typeahead__tokens [data-term-value]' ) ).map( ( el ) => el.getAttribute( 'data-term-value' ) );
            yield navigateTaxonomy( baseUrl, queryVar, opVar, pageVar, values, operator );
        },
    },
} );
