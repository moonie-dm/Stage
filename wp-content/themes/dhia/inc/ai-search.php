<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * TASK 1 — Smart symptom/need search, plus the shared taxonomy classifier
 * (acdq_classify_need()) that also backs the chat widget's guided triage
 * flow in inc/ai-chatbot.php. Keeping the classification logic here in one
 * place means both entry points share the exact same safety-constrained
 * prompt and the exact same validation against real taxonomy terms, instead
 * of two prompts that could quietly drift apart.
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
 * symptoms, causes, or treatment and to respond only with the structured
 * fields. On top of that, callers never trust the model's output as free
 * text — it's used only to pick a specialty/region from slug lists we
 * generated from our own taxonomy, validated against those same lists
 * before use, and to set a boolean. Nothing the model writes is ever
 * echoed to the page.
 */

/**
 * Classify free text against the real specialite/region taxonomies.
 * Returns an array with 'specialite_slug', 'specialite_name', 'region_slug',
 * 'region_name', 'urgent' — all already validated against real taxonomy
 * terms — or a WP_Error when classification isn't possible (AI disabled,
 * rate-limited, API failure, or a response that didn't parse), so callers
 * can fall back to their own safe default rather than showing an error.
 */
function acdq_classify_need( $text ) {
	if ( '' === trim( $text ) ) {
		return new WP_Error( 'acdq_empty_text', 'Texte vide.' );
	}

	if ( ! acdq_ai_enabled() || ! acdq_ai_rate_limit_ok( 'search', 10 ) ) {
		return new WP_Error( 'acdq_unavailable', 'Classification indisponible pour le moment.' );
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
		return new WP_Error( 'acdq_no_taxonomy', 'Aucune spécialité ou région disponible.' );
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
		return $result;
	}

	$parsed = json_decode( $result, true );
	if (
		! is_array( $parsed )
		|| ! array_key_exists( 'specialite_slug', $parsed )
		|| ! array_key_exists( 'region_slug', $parsed )
		|| ! array_key_exists( 'urgent', $parsed )
	) {
		error_log( 'ACDQ AI classify: response did not match expected format — ' . $result );
		return new WP_Error( 'acdq_parse_error', "La réponse n'a pas pu être interprétée." );
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

	if ( $region_slug && ! in_array( $region_slug, $valid_region_slugs, true ) ) {
		$region_slug = '';
	}

	$specialite_term = $slug ? get_term_by( 'slug', $slug, 'specialite' ) : false;
	$region_term     = $region_slug ? get_term_by( 'slug', $region_slug, 'region' ) : false;

	return array(
		'specialite_slug' => $slug,
		'specialite_name' => $specialite_term ? $specialite_term->name : '',
		'region_slug'     => $region_slug,
		'region_name'     => $region_term ? $region_term->name : '',
		'urgent'          => $urgent,
	);
}

function acdq_ai_search() {
	check_ajax_referer( 'acdq_ai_search', 'nonce' );

	$text        = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
	$archive_url = get_post_type_archive_link( 'clinique' );
	$keyword_url = add_query_arg( 's', rawurlencode( $text ), home_url( '/' ) );

	if ( '' === trim( $text ) ) {
		wp_send_json_success( array( 'url' => $archive_url ) );
	}

	$result = acdq_classify_need( $text );

	// Any reason we couldn't safely classify: fall back to the exact search
	// this text would already have triggered, no error shown to the visitor.
	if ( is_wp_error( $result ) ) {
		wp_send_json_success( array( 'url' => $keyword_url ) );
	}

	if ( $result['specialite_slug'] && $result['region_slug'] ) {
		// Both matched: combine them into the archive's own filter pipeline
		// (inc/ajax-filters.php's tax_query supports specialite + region
		// together — filters.js applies both on load) rather than picking
		// just one and dropping the other.
		wp_send_json_success( array( 'url' => add_query_arg(
			array( 'specialite' => $result['specialite_slug'], 'region' => $result['region_slug'] ),
			$archive_url
		) ) );
	}

	if ( $result['specialite_slug'] ) {
		wp_send_json_success( array( 'url' => add_query_arg( 'specialite', rawurlencode( $result['specialite_slug'] ), $archive_url ) ) );
	}

	if ( $result['region_slug'] ) {
		// Region-only sends visitors to that region's own dedicated archive
		// (taxonomy-region.php) rather than the filtered main directory —
		// same canonical URL as every other link to that region on the site
		// (homepage region tiles, footer, etc.), with no flash of
		// unfiltered results while the AJAX filter kicks in.
		$region_term = get_term_by( 'slug', $result['region_slug'], 'region' );
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
