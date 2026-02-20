import { store } from '@wordpress/interactivity';

const REGION_SELECTOR = '[data-wp-router-region="query-filter"]';

// Core SPA swap: fetch url, extract region, replace DOM.
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

		document.dispatchEvent( new Event( 'query-filter:navigated' ) );
	} catch ( err ) {
		console.error( '[qf] SPA navigate failed, falling back', err );
		window.location.assign( url );
	}
};

const navigate = async ( url ) => {
	if ( url === window.location.href ) return;
	await spaSwap( url, true );
};

// Extend the existing 'query-filter' store with a generic navigate action for href links
store( 'query-filter', {
	actions: {
		*navigateHref( e ) {
			e.preventDefault();
			const href = e.currentTarget.getAttribute( 'href' );
			if ( ! href ) return;

			// If this is the clear-all chip, trigger a refresh of counts/visibility immediately
			if ( e.currentTarget.classList.contains( 'is-clear-all' ) ) {
				try {
					// Uncheck all taxonomy checkboxes in the DOM so current filters become empty
					document
						.querySelectorAll( '.wp-block-query-filter__checkbox:checked' )
						.forEach( ( el ) => ( el.checked = false ) );
					// Ask taxonomy view to recompute counts/visibility now
					document.dispatchEvent( new Event( 'query-filter:refresh' ) );
				} catch ( err ) {
					// no-op
				}
			}
			yield navigate( href );
		},
	},
} );
