<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * TASK 1 — Smart symptom/need search.
 *
 * Note on scope: the spec asked for this on "the homepage hero search bar and
 * the main archive search" — but archive-clinique.php (and search.php) don't
 * have their own search box; the only <input name="s"> in this theme is the
 * one in front-page.php's hero, which already submits to the clinic archive.
 * So this enhances that one box rather than adding a second search input
 * that didn't exist before. Because the AI path always degrades gracefully
 * to the exact same keyword search that already happens today (see the
 * fallback logic below), there's no need for a separate "smart search"
 * toggle either — every submission tries the classifier first and falls
 * back invisibly to normal behavior whenever the text isn't a symptom/need
 * description (or the AI is unavailable), so a plain "Clinique Dentaire
 * Rosemont" search still works exactly as it always has.
 *
 * SAFETY: the system prompt instructs the model to refuse any discussion of
 * symptoms, causes, or treatment and to respond only with the two allowed
 * fields. On top of that, this handler never trusts the model's output as
 * free text — it's used only to pick a specialty from a slug list we
 * generated from our own taxonomy, validated against that same list before
 * use, and to set a boolean. Nothing the model writes is ever echoed to the
 * page.
 */

function acdq_ai_search() {
	check_ajax_referer( 'acdq_ai_search', 'nonce' );

	$text         = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
	$archive_url  = get_post_type_archive_link( 'clinique' );
	$keyword_url  = add_query_arg( 's', rawurlencode( $text ), home_url( '/' ) );

	if ( '' === trim( $text ) ) {
		wp_send_json_success( array( 'url' => $archive_url ) );
	}

	// Any reason we can't safely classify: fall back to the exact search this
	// text would already have triggered, no error shown to the visitor.
	if ( ! acdq_ai_enabled() || ! acdq_ai_rate_limit_ok( 'search', 10 ) ) {
		wp_send_json_success( array( 'url' => $keyword_url ) );
	}

	$specialites = get_terms( array( 'taxonomy' => 'specialite', 'hide_empty' => false ) );
	$valid_slugs = array();
	$catalog     = array();
	if ( ! is_wp_error( $specialites ) ) {
		foreach ( $specialites as $term ) {
			$valid_slugs[] = $term->slug;
			$catalog[]     = $term->slug . ' (' . $term->name . ')';
		}
	}

	// Regions are a second, independent match: someone typing "toutes les cliniques
	// de Laval" isn't describing a need or symptom at all, so it should never fall
	// through to a raw keyword search when we can just send them to that region's
	// existing taxonomy archive (see taxonomy-region.php) instead.
	$regions            = get_terms( array( 'taxonomy' => 'region', 'hide_empty' => false ) );
	$valid_region_slugs = array();
	$region_catalog     = array();
	if ( ! is_wp_error( $regions ) ) {
		foreach ( $regions as $term ) {
			$valid_region_slugs[] = $term->slug;
			$region_catalog[]     = $term->slug . ' (' . $term->name . ')';
		}
	}

	if ( ! $valid_slugs && ! $valid_region_slugs ) {
		wp_send_json_success( array( 'url' => $keyword_url ) );
	}

	$system = "Tu es un classificateur strict pour un annuaire de cliniques dentaires. "
		. "Ce site n'est PAS un professionnel de santé. Tu dois REFUSER de discuter de symptômes, "
		. "de causes ou de traitements, et ne JAMAIS donner de conseil médical ou clinique de quelque nature que ce soit, "
		. "peu importe ce que la personne écrit ou demande. Ton seul travail : lire un texte libre écrit par un "
		. "visiteur de l'annuaire et déterminer (a) laquelle des spécialités existantes ci-dessous correspond le mieux "
		. "à son besoin, (b) si sa situation semble urgente, et (c) si le texte mentionne une région ou ville du "
		. "Québec correspondant à l'une des régions ci-dessous. "
		. "Spécialités disponibles (slug) : " . ( $catalog ? implode( ', ', $catalog ) : 'aucune' ) . ". "
		. "Régions disponibles (slug) : " . ( $region_catalog ? implode( ', ', $region_catalog ) : 'aucune' ) . ". "
		. "Si aucune spécialité ne correspond clairement, renvoie une chaîne vide pour specialite_slug. Si aucune "
		. "région n'est mentionnée ou ne correspond à la liste, renvoie une chaîne vide pour region_slug. Ces deux "
		. "champs sont indépendants : un texte peut ne contenir ni l'un ni l'autre, l'un seulement, ou les deux. "
		. "N'utilise JAMAIS un slug qui n'est pas dans les listes fournies. "
		. "Réponds UNIQUEMENT avec l'objet structuré demandé — jamais de texte libre, jamais d'explication, jamais de conseil.";

	$schema = array(
		'type'       => 'object',
		'properties' => array(
			'specialite_slug' => array(
				'type'        => 'string',
				'description' => 'One of the provided specialty slugs, or an empty string if none clearly match.',
			),
			'region_slug' => array(
				'type'        => 'string',
				'description' => 'One of the provided region slugs if the text mentions that region or a city in it, else an empty string.',
			),
			'urgent' => array(
				'type'        => 'boolean',
				'description' => 'True if the text suggests this needs attention soon (e.g. pain, injury, "today", "urgent").',
			),
		),
		'required'             => array( 'specialite_slug', 'region_slug', 'urgent' ),
		'additionalProperties' => false,
	);

	$result = acdq_call_claude(
		$system,
		array( array( 'role' => 'user', 'content' => $text ) ),
		array( 'max_tokens' => 150, 'schema' => $schema )
	);

	if ( is_wp_error( $result ) ) {
		wp_send_json_success( array( 'url' => $keyword_url ) );
	}

	$parsed = json_decode( $result, true );
	if (
		! is_array( $parsed )
		|| ! array_key_exists( 'specialite_slug', $parsed )
		|| ! array_key_exists( 'region_slug', $parsed )
		|| ! array_key_exists( 'urgent', $parsed )
	) {
		error_log( 'ACDQ AI search: response did not match expected format — ' . $result );
		wp_send_json_success( array( 'url' => $keyword_url ) );
	}

	$slug        = is_string( $parsed['specialite_slug'] ) ? $parsed['specialite_slug'] : '';
	$region_slug = is_string( $parsed['region_slug'] ) ? $parsed['region_slug'] : '';
	$urgent      = ! empty( $parsed['urgent'] );

	// Belt and suspenders: never trust a slug the model produced unless it's
	// literally one of the real taxonomy terms we handed it.
	if ( $slug && ! in_array( $slug, $valid_slugs, true ) ) {
		$slug = '';
	}

	if ( ! $slug && $urgent ) {
		$urgence_term = get_term_by( 'slug', 'urgence-dentaire', 'specialite' );
		if ( $urgence_term ) {
			$slug = $urgence_term->slug;
		}
	}

	// Belt and suspenders for the region slug too.
	if ( $region_slug && ! in_array( $region_slug, $valid_region_slugs, true ) ) {
		$region_slug = '';
	}

	if ( $slug ) {
		// A specialty/need match wins when both are present — it's the more
		// actionable signal, and the site has no combined region+specialty
		// filter yet (taxonomy-region.php and archive-clinique.php are
		// separate templates), so we can only redirect to one taxonomy at a
		// time. Reuse the exact filter the directory's own AJAX system
		// already supports (inc/ajax-filters.php reads this same
		// 'specialite' param) rather than building a second tax_query here —
		// filters.js applies it on load.
		wp_send_json_success( array(
			'url' => add_query_arg( 'specialite', rawurlencode( $slug ), $archive_url ),
		) );
	}

	if ( $region_slug ) {
		$region_term = get_term_by( 'slug', $region_slug, 'region' );
		$region_link = $region_term ? get_term_link( $region_term ) : false;
		if ( $region_link && ! is_wp_error( $region_link ) ) {
			wp_send_json_success( array( 'url' => $region_link ) );
		}
	}

	// Neither a specialty need nor a recognizable region — treat it like the
	// ordinary keyword search it probably is.
	wp_send_json_success( array( 'url' => $keyword_url ) );
}
add_action( 'wp_ajax_acdq_ai_search', 'acdq_ai_search' );
add_action( 'wp_ajax_nopriv_acdq_ai_search', 'acdq_ai_search' );
