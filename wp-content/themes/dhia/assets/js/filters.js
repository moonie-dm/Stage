(function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		var list = document.querySelector( '.clinic-row-list' );
		if ( ! list || typeof acdqFilters === 'undefined' ) return;

		var openBtn = document.querySelector( '.filter-chip[data-filter="open"]' );
		var acceptBtn = document.querySelector( '.filter-chip[data-filter="accepting"]' );
		var specSelect = document.querySelector( '.filter-select' );
		var sortSelect = document.querySelectorAll( '.filter-select' )[1];

		var state = { open: false, accepting: false, specialite: '', sort: 'date' };

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
				state.sort = sortSelect.selectedIndex === 1 ? 'title' : 'date';
				fetchResults();
			} );
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
					}
				} )
				.finally( function () { list.style.opacity = '1'; } );
		}
	} );
} )();