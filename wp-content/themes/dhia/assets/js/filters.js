(function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		var list = document.querySelector( '.clinic-row-list' );
		if ( ! list || typeof acdqFilters === 'undefined' ) return;

		var openBtn = document.querySelector( '.filter-chip[data-filter="open"]' );
		var acceptBtn = document.querySelector( '.filter-chip[data-filter="accepting"]' );
		var specSelect = document.querySelector( '.filter-select' );
		var sortSelect = document.querySelectorAll( '.filter-select' )[1];

		// Carries the current search term (if any) through every subsequent chip/sort refresh,
		// so filtering on /?s=... results doesn't silently drop back to the full directory.
		var state = {
			open: false, accepting: false, specialite: '', sort: 'date',
			search: acdqFilters.search || '', userLat: null, userLng: null,
		};

		function toggleChip( btn, key ) {
			btn.addEventListener( 'click', function () {
				state[ key ] = ! state[ key ];
				btn.classList.toggle( 'is-active', state[ key ] );
				fetchResults();
			} );
		}
		if ( openBtn ) toggleChip( openBtn, 'open' );
		if ( acceptBtn ) toggleChip( acceptBtn, 'accepting' );

		if ( specSelect ) {
			specSelect.addEventListener( 'change', function () {
				state.specialite = specSelect.value;
				fetchResults();
			} );
		}
		if ( sortSelect ) {
			sortSelect.addEventListener( 'change', function () {
				var idx = sortSelect.selectedIndex;
				state.sort = idx === 1 ? 'title' : ( idx === 2 ? 'distance' : 'date' );
				if ( state.sort === 'distance' ) {
					requestDistanceSort();
				} else {
					fetchResults();
				}
			} );
		}

		// "Plus proche" needs the visitor's position — ask for it (once), then refresh.
		// If it's denied or unavailable, we just fall back to the server's default order.
		function requestDistanceSort() {
			if ( state.userLat !== null ) {
				fetchResults();
				return;
			}
			if ( ! navigator.geolocation ) {
				fetchResults();
				return;
			}
			list.style.opacity = '.5';
			navigator.geolocation.getCurrentPosition( function ( pos ) {
				state.userLat = pos.coords.latitude;
				state.userLng = pos.coords.longitude;
				fetchResults();
			}, function () {
				fetchResults();
			} );
		}

		// Pre-select a filter when arriving from a hero quick-filter pill (?f=open|accepting).
		var presetFilter = new URLSearchParams( window.location.search ).get( 'f' );
		if ( presetFilter === 'open' && openBtn ) {
			state.open = true;
			openBtn.classList.add( 'is-active' );
		} else if ( presetFilter === 'accepting' && acceptBtn ) {
			state.accepting = true;
			acceptBtn.classList.add( 'is-active' );
		}
		if ( presetFilter === 'open' || presetFilter === 'accepting' ) {
			fetchResults();
		}

		function fetchResults() {
			list.style.opacity = '.5';
			var params = new URLSearchParams();
			params.set( 'action', 'acdq_filter_cliniques' );
			params.set( 'nonce', acdqFilters.nonce );
			params.set( 'specialite', state.specialite );
			params.set( 'open', state.open ? '1' : '0' );
			params.set( 'accepting', state.accepting ? '1' : '0' );
			params.set( 'sort', state.sort );
			params.set( 's', state.search );
			if ( state.sort === 'distance' && state.userLat !== null ) {
				params.set( 'lat', state.userLat );
				params.set( 'lng', state.userLng );
			}

			fetch( acdqFilters.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString(),
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res.success ) {
						list.innerHTML = res.data.html;
						if ( window.acdqInitMap ) window.acdqInitMap();
						if ( window.acdqInitDistance ) window.acdqInitDistance();
					}
				} )
				.finally( function () { list.style.opacity = '1'; } );
		}
	} );
} )();