import { store } from '@wordpress/interactivity';

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
			const { actions } = yield import( '@wordpress/interactivity-router' );
			yield actions.navigate( href );
		},
	},
} );
