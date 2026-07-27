(function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		var btn = document.querySelector( '.clinic-share-copy' );
		if ( ! btn ) return;

		btn.addEventListener( 'click', function () {
			var url = btn.getAttribute( 'data-url' ) || window.location.href;
			var label = btn.querySelector( '.clinic-share-copy-label' );
			var originalText = label ? label.textContent : '';

			function showCopied() {
				if ( ! label ) return;
				label.textContent = 'Lien copié !';
				setTimeout( function () { label.textContent = originalText; }, 2000 );
			}

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( url ).then( showCopied );
			} else {
				var input = document.createElement( 'input' );
				input.value = url;
				document.body.appendChild( input );
				input.select();
				document.execCommand( 'copy' );
				document.body.removeChild( input );
				showCopied();
			}
		} );
	} );
} )();
