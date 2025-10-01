import { store } from '@wordpress/interactivity';

// Extend the existing 'query-filter' store with a generic navigate action for href links
store( 'query-filter', {
	actions: {
		*navigateHref( e ) {
			e.preventDefault();
			const href = e.currentTarget.getAttribute( 'href' );
			if ( ! href ) return;
			const { actions } = yield import( '@wordpress/interactivity-router' );
			yield actions.navigate( href );
		},
	},
} );
