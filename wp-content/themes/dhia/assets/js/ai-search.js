(function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof acdqAiSearch === 'undefined' ) return;

		var form = document.getElementById( 'acdq-search-form' );
		if ( ! form ) return;
		var input = form.querySelector( 'input[name="s"]' );
		if ( ! input ) return;

		form.addEventListener( 'submit', function ( e ) {
			var q = input.value.trim();
			if ( ! q ) return; // empty search — let the normal GET submit happen

			e.preventDefault();

			var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
			var timeout = setTimeout( function () {
				if ( controller ) controller.abort();
			}, 5000 );

			var params = new URLSearchParams();
			params.set( 'action', 'acdq_ai_search' );
			params.set( 'nonce', acdqAiSearch.nonce );
			params.set( 'q', q );

			fetch( acdqAiSearch.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString(),
				signal: controller ? controller.signal : undefined,
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					clearTimeout( timeout );
					if ( res && res.success && res.data && res.data.url ) {
						window.location.href = res.data.url;
					} else {
						form.submit();
					}
				} )
				.catch( function () {
					clearTimeout( timeout );
					// Network error, timeout, or abort — never block the visitor,
					// just fall back to the normal keyword search submit.
					form.submit();
				} );
		} );
	} );
} )();
