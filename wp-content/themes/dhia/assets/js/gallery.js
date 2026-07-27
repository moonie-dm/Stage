(function () {
	'use strict';

	function initGallery( root ) {
		var track = root.querySelector( '.clinic-gallery-track' );
		var slides = root.querySelectorAll( '.clinic-gallery-slide' );
		var prevBtn = root.querySelector( '.clinic-gallery-prev' );
		var nextBtn = root.querySelector( '.clinic-gallery-next' );
		var dots = root.querySelectorAll( '.clinic-gallery-dot' );
		if ( ! track || slides.length < 2 ) return;

		var index = 0;

		function go( next ) {
			index = ( next + slides.length ) % slides.length;
			track.style.transform = 'translateX(-' + ( index * 100 ) + '%)';
			dots.forEach( function ( dot, i ) {
				dot.classList.toggle( 'is-active', i === index );
			} );
		}

		if ( prevBtn ) prevBtn.addEventListener( 'click', function () { go( index - 1 ); } );
		if ( nextBtn ) nextBtn.addEventListener( 'click', function () { go( index + 1 ); } );
		dots.forEach( function ( dot, i ) {
			dot.addEventListener( 'click', function () { go( i ); } );
		} );

		// Basic swipe support on touch devices.
		var startX = null;
		track.addEventListener( 'touchstart', function ( e ) { startX = e.touches[ 0 ].clientX; }, { passive: true } );
		track.addEventListener( 'touchend', function ( e ) {
			if ( startX === null ) return;
			var dx = e.changedTouches[ 0 ].clientX - startX;
			if ( Math.abs( dx ) > 40 ) go( dx < 0 ? index + 1 : index - 1 );
			startX = null;
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.clinic-gallery' ).forEach( initGallery );
	} );
} )();
