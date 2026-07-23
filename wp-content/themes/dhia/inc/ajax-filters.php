<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function acdq_filter_cliniques() {
	check_ajax_referer( 'acdq_nonce', 'nonce' );

	$specialite = isset( $_POST['specialite'] ) ? sanitize_text_field( wp_unslash( $_POST['specialite'] ) ) : '';
	$open_only  = isset( $_POST['open'] ) && $_POST['open'] === '1';
	$accepting  = isset( $_POST['accepting'] ) && $_POST['accepting'] === '1';
	$sort       = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'date';
	$paged      = isset( $_POST['paged'] ) ? max( 1, (int) $_POST['paged'] ) : 1;

	$args = array(
		'post_type'      => 'clinique',
		'post_status'    => 'publish',
		'posts_per_page' => $open_only ? -1 : 10, // pull all when open-filtering, since it's filtered after the query
		'paged'          => $paged,
	);

	if ( $specialite ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'specialite', 'field' => 'slug', 'terms' => $specialite ) );
	}
	if ( $accepting ) {
		$args['meta_query'] = array( array( 'key' => 'accepte_nouveaux_patients', 'value' => '1' ) );
	}
	if ( $sort === 'title' ) {
		$args['orderby'] = 'title';
		$args['order']   = 'ASC';
	} else {
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	}

	$query = new WP_Query( $args );

	ob_start();
	$shown = 0;
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			if ( $open_only ) {
				$statut = acdq_get_open_status();
				if ( ! $statut['ouvert'] ) continue;
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