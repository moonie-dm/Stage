(function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		var el = document.getElementById( 'acdq-map' );
		if ( ! el || typeof L === 'undefined' ) return;

		var map = L.map( el ).setView( [ 52.5, -71.5 ], 5 );
		L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap',
			maxZoom: 18,
		} ).addTo( map );

		var bounds = [];
		document.querySelectorAll( '.clinic-row' ).forEach( function ( row ) {
			var lat = parseFloat( row.getAttribute( 'data-lat' ) );
			var lng = parseFloat( row.getAttribute( 'data-lng' ) );
			if ( isNaN( lat ) || isNaN( lng ) ) return;
			var title = row.getAttribute( 'data-title' ) || '';
			var link = row.querySelector( '.clinic-row-title a' );
			var href = link ? link.getAttribute( 'href' ) : '#';
			L.marker( [ lat, lng ] ).addTo( map )
				.bindPopup( '<strong>' + title + '</strong><br><a href="' + href + '">Voir la fiche</a>' );
			bounds.push( [ lat, lng ] );
		} );

		if ( bounds.length ) map.fitBounds( bounds, { padding: [ 30, 30 ], maxZoom: 12 } );
	} );
} )();