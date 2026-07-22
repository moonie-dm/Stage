(function () {
	'use strict';
	function haversine( lat1, lon1, lat2, lon2 ) {
		var R = 6371;
		var dLat = ( lat2 - lat1 ) * Math.PI / 180;
		var dLon = ( lon2 - lon1 ) * Math.PI / 180;
		var a = Math.sin( dLat / 2 ) ** 2 + Math.cos( lat1 * Math.PI / 180 ) * Math.cos( lat2 * Math.PI / 180 ) * Math.sin( dLon / 2 ) ** 2;
		return R * 2 * Math.atan2( Math.sqrt( a ), Math.sqrt( 1 - a ) );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( ! navigator.geolocation ) return;
		navigator.geolocation.getCurrentPosition( function ( pos ) {
			var userLat = pos.coords.latitude, userLng = pos.coords.longitude;
			document.querySelectorAll( '.clinic-row' ).forEach( function ( row ) {
				var lat = parseFloat( row.getAttribute( 'data-lat' ) );
				var lng = parseFloat( row.getAttribute( 'data-lng' ) );
				if ( isNaN( lat ) || isNaN( lng ) ) return;
				var dist = haversine( userLat, userLng, lat, lng );
				var badge = row.querySelector( '.distance-badge' );
				if ( badge ) badge.textContent = dist.toFixed( 1 ) + ' km';
			} );
		}, function () { /* location denied — badges just stay hidden */ } );
	} );
} )();