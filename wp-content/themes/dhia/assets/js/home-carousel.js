(function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		var viewport = document.getElementById( 'acdq-home-latest-viewport' );
		if ( ! viewport ) return;

		var prevBtn = document.querySelector( '.home-latest-prev' );
		var nextBtn = document.querySelector( '.home-latest-next' );
		if ( ! prevBtn || ! nextBtn ) return;

		// Native scroll-snap (see style.css) does the actual snapping and gives
		// free touch/trackpad swipe on mobile — the buttons just nudge the
		// scroll position by one card's width, same idea as the photo gallery
		// carousel but simpler since there's no fixed slide count to track.
		function scrollByCard( direction ) {
			var card = viewport.querySelector( '.home-latest-slide' );
			var step = card ? card.getBoundingClientRect().width + 16 : viewport.clientWidth * 0.8;
			viewport.scrollBy( { left: direction * step, behavior: 'smooth' } );
		}

		prevBtn.addEventListener( 'click', function () { scrollByCard( -1 ); } );
		nextBtn.addEventListener( 'click', function () { scrollByCard( 1 ); } );
	} );
} )();
