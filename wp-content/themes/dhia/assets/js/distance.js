(function () {
	'use strict';
	function haversine( lat1, lon1, lat2, lon2 ) {
		var R = 6371;
		var dLat = ( lat2 - lat1 ) * Math.PI / 180;
		var dLon = ( lon2 - lon1 ) * Math.PI / 180;
		var a = Math.sin( dLat / 2 ) ** 2 + Math.cos( lat1 * Math.PI / 180 ) * Math.cos( lat2 * Math.PI / 180 ) * Math.sin( dLon / 2 ) ** 2;
		return R * 2 * Math.atan2( Math.sqrt( a ), Math.sqrt( 1 - a ) );
	}

	// Paints distance badges using a position the caller already has —
	// filters.js requests geolocation itself, only when the visitor picks
	// "Plus proche", and passes the result in here. This used to request its
	// own location on every page load via DOMContentLoaded, which both
	// prompted for the browser's location permission before the visitor
	// asked for anything, and kept showing (often wildly inaccurate, e.g.
	// IP-based) distance badges under every sort mode, not just "Plus
	// proche" — because that stored position never got cleared or re-checked
	// against the current sort.
	function applyDistances( userLat, userLng ) {
		if ( typeof userLat !== 'number' || typeof userLng !== 'number' ) return;
		document.querySelectorAll( '.clinic-row' ).forEach( function ( row ) {
			var lat = parseFloat( row.getAttribute( 'data-lat' ) );
			var lng = parseFloat( row.getAttribute( 'data-lng' ) );
			var badge = row.querySelector( '.distance-badge' );
			if ( isNaN( lat ) || isNaN( lng ) || ! badge ) return;
			var dist = haversine( userLat, userLng, lat, lng );
			badge.textContent = dist.toFixed( 1 ) + ' km';
			badge.hidden = false;
		} );
	}

	window.acdqInitDistance = applyDistances;
} )();
