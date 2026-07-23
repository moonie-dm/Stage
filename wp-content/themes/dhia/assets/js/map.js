(function () {
	'use strict';

	var map = null;
	var markersLayer = null;
	var defaultIcon = null;
	var activeIcon = null;
	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var rowObserver = null;
	var ratios = new Map();
	var markerByRow = new Map();
	var activeRow = null;
	var syncScheduled = false;
	var pendingRow = null;
	var settleTimer = null;
	var SETTLE_DELAY = 150; // ms a card must stay "most visible" before we commit to it

	function initMap() {
		var el = document.getElementById( 'acdq-map' );
		if ( ! el || typeof L === 'undefined' ) return;

		if ( ! map ) {
			map = L.map( el ).setView( [ 52.5, -71.5 ], 5 );

			defaultIcon = L.divIcon({
				className: 'acdq-marker',
				html: '<div class="acdq-marker-pin"></div>',
				iconSize: [26, 26],
				iconAnchor: [13, 26],
				popupAnchor: [0, -26],
			});
			activeIcon = L.divIcon({
				className: 'acdq-marker acdq-marker--active',
				html: '<div class="acdq-marker-pin"></div>',
				iconSize: [26, 26],
				iconAnchor: [13, 26],
				popupAnchor: [0, -26],
			});

			// CARTO Positron — light, minimal basemap (no API key required).
			L.tileLayer( 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
				subdomains: 'abcd',
				maxZoom: 19,
			} ).addTo( map );
			markersLayer = L.layerGroup().addTo( map );
		} else {
			markersLayer.clearLayers();
		}

		markerByRow = new Map();
		activeRow = null;

		var bounds = [];
		document.querySelectorAll( '.clinic-row' ).forEach( function ( row ) {
			var lat = parseFloat( row.getAttribute( 'data-lat' ) );
			var lng = parseFloat( row.getAttribute( 'data-lng' ) );
			if ( isNaN( lat ) || isNaN( lng ) ) return;
			var title = row.getAttribute( 'data-title' ) || '';
			var link = row.querySelector( '.clinic-row-title a' );
			var href = link ? link.getAttribute( 'href' ) : '#';
			var marker = L.marker( [ lat, lng ], { icon: defaultIcon } ).addTo( markersLayer )
				.bindPopup( '<strong>' + title + '</strong><br><a href="' + href + '">Voir la fiche</a>' );
			markerByRow.set( row, marker );
			bounds.push( [ lat, lng ] );
		} );

		if ( bounds.length ) map.fitBounds( bounds, { padding: [ 30, 30 ], maxZoom: 12 } );

		setupScrollSync();
	}

	/**
	 * Watch which .clinic-row is most visible as the page scrolls, and keep the
	 * map + marker highlight in sync with it.
	 */
	function setupScrollSync() {
		if ( rowObserver ) rowObserver.disconnect();
		if ( settleTimer ) { clearTimeout( settleTimer ); settleTimer = null; }
		ratios = new Map();
		pendingRow = null;

		var rows = document.querySelectorAll( '.clinic-row' );
		if ( ! rows.length ) return;

		var thresholds = [];
		for ( var i = 0; i <= 10; i++ ) thresholds.push( i / 10 );

		rowObserver = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				ratios.set( entry.target, entry.isIntersecting ? entry.intersectionRatio : 0 );
			} );
			scheduleActiveRowUpdate();
		}, { threshold: thresholds } );

		rows.forEach( function ( row ) { rowObserver.observe( row ); } );
	}

	// Coalesce bursts of IntersectionObserver callbacks (fast scrolling) into one check per frame.
	function scheduleActiveRowUpdate() {
		if ( syncScheduled ) return;
		syncScheduled = true;
		requestAnimationFrame( function () {
			syncScheduled = false;
			updateActiveRow();
		} );
	}

	function updateActiveRow() {
		var best = null;
		var bestRatio = 0;
		ratios.forEach( function ( ratio, row ) {
			if ( ratio > bestRatio ) {
				bestRatio = ratio;
				best = row;
			}
		} );

		if ( ! best || bestRatio === 0 || best === activeRow ) {
			// Current active card is still (or again) the most visible — cancel any pending switch.
			pendingRow = null;
			if ( settleTimer ) { clearTimeout( settleTimer ); settleTimer = null; }
			return;
		}

		// Debounce: only commit to a new active card once it's stayed "most visible"
		// for a short moment, so fast scrolling past several cards doesn't spam flyTo.
		if ( best === pendingRow ) return;
		pendingRow = best;
		if ( settleTimer ) clearTimeout( settleTimer );
		settleTimer = setTimeout( function () {
			settleTimer = null;
			if ( pendingRow ) setActiveRow( pendingRow );
		}, SETTLE_DELAY );
	}

	function setActiveRow( row ) {
		if ( activeRow ) {
			activeRow.classList.remove( 'is-active' );
			var prevMarker = markerByRow.get( activeRow );
			if ( prevMarker ) {
				prevMarker.setIcon( defaultIcon );
				prevMarker.setZIndexOffset( 0 );
			}
		}

		activeRow = row;
		row.classList.add( 'is-active' );

		var marker = markerByRow.get( row );
		if ( ! marker || ! map ) return;

		marker.setIcon( activeIcon );
		marker.setZIndexOffset( 1000 );

		var targetZoom = Math.max( map.getZoom(), 13 );
		if ( reduceMotion ) {
			map.setView( marker.getLatLng(), targetZoom );
		} else {
			map.flyTo( marker.getLatLng(), targetZoom, { duration: 0.6 } );
		}
	}

	window.acdqInitMap = initMap;
	document.addEventListener( 'DOMContentLoaded', initMap );
} )();
