<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function acdq_filter_cliniques() {
	check_ajax_referer( 'acdq_nonce', 'nonce' );

	$specialite = isset( $_POST['specialite'] ) ? sanitize_text_field( wp_unslash( $_POST['specialite'] ) ) : '';
	$region     = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
	$open_only  = isset( $_POST['open'] ) && $_POST['open'] === '1';
	$accepting  = isset( $_POST['accepting'] ) && $_POST['accepting'] === '1';
	$min_rating = isset( $_POST['rating'] ) ? max( 0, (int) $_POST['rating'] ) : 0;
	$sort       = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'date';
	$paged      = isset( $_POST['paged'] ) ? max( 1, (int) $_POST['paged'] ) : 1;
	$search     = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';

	$user_lat = isset( $_POST['lat'] ) && is_numeric( $_POST['lat'] ) ? (float) $_POST['lat'] : null;
	$user_lng = isset( $_POST['lng'] ) && is_numeric( $_POST['lng'] ) ? (float) $_POST['lng'] : null;
	$sort_by_distance = $sort === 'distance' && null !== $user_lat && null !== $user_lng;

	$args = array(
		'post_type'      => 'clinique',
		'post_status'    => 'publish',
		// Pull everything when we need to filter/sort in PHP rather than in SQL: open-only,
		// accepting-patients, and minimum rating are all checked per-post below (rating isn't
		// a taxonomy or a reliable meta field — it's computed from approved comment ratings via
		// acdq_get_average_rating(), same as the card display already uses), and distance
		// sorting needs the full set before it can rank them.
		'posts_per_page' => ( $open_only || $accepting || $min_rating || $sort_by_distance ) ? -1 : 10,
		'paged'          => $paged,
	);

	if ( $search ) {
		$args['s'] = $search;
	}
	$tax_query = array();
	if ( $specialite ) {
		$tax_query[] = array( 'taxonomy' => 'specialite', 'field' => 'slug', 'terms' => $specialite );
	}
	if ( $region ) {
		$tax_query[] = array( 'taxonomy' => 'region', 'field' => 'slug', 'terms' => $region );
	}
	if ( $tax_query ) {
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		$args['tax_query'] = $tax_query;
	}
	if ( $sort === 'title' ) {
		$args['orderby'] = 'title';
		$args['order']   = 'ASC';
	} else {
		// Distance sort re-orders $query->posts directly below (SQL can't rank by
		// haversine distance to a point that only the browser's geolocation knows);
		// this is just its pre-sort order, and the default order otherwise.
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	}

	$query = new WP_Query( $args );

	if ( $sort_by_distance ) {
		usort( $query->posts, function ( $a, $b ) use ( $user_lat, $user_lng ) {
			$da = acdq_distance_to_clinic( $a->ID, $user_lat, $user_lng );
			$db = acdq_distance_to_clinic( $b->ID, $user_lat, $user_lng );
			return $da <=> $db;
		} );
	}

	ob_start();
	$shown = 0;
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			if ( $open_only ) {
				$statut = acdq_get_open_status();
				if ( ! $statut['ouvert'] ) continue;
			}
			if ( $accepting && ! get_field( 'accepte_nouveaux_patients', get_the_ID() ) ) continue;
			if ( $min_rating ) {
				$rating = function_exists( 'acdq_get_average_rating' ) ? acdq_get_average_rating( get_the_ID() ) : array( 'average' => 0 );
				if ( $rating['average'] < $min_rating ) continue;
			}
			get_template_part( 'template-parts/clinic-row' );
			$shown++;
		}
		wp_reset_postdata();
	}
	if ( $shown === 0 ) {
		echo '<p class="no-results">Aucune clinique ne correspond à ces critères.</p>';
	}
	$html = ob_get_clean();

	wp_send_json_success( array(
		'html'  => $html,
		'found' => $shown,
	) );
}
add_action( 'wp_ajax_acdq_filter_cliniques', 'acdq_filter_cliniques' );
add_action( 'wp_ajax_nopriv_acdq_filter_cliniques', 'acdq_filter_cliniques' );