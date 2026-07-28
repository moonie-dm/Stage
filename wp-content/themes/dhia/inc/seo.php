<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Basic on-page SEO: meta description, Open Graph/Twitter tags, and
 * schema.org structured data for clinic pages. None of this existed before —
 * every page shared the same blank meta description, shared links (the site
 * already has its own Facebook/email share buttons) rendered with no
 * preview card, and clinic pages had no structured data for search engines
 * to build rich results from.
 */

/**
 * The descriptive sentence for the current page, shared by the meta
 * description and Open Graph description so they never disagree.
 */
function acdq_get_page_description() {
	if ( is_singular( 'clinique' ) ) {
		$excerpt = get_the_excerpt();
		if ( $excerpt ) {
			return wp_strip_all_tags( $excerpt );
		}

		$specialites = get_the_terms( get_the_ID(), 'specialite' );
		$ville       = get_field( 'ville' );
		$parts       = array( get_the_title() );
		if ( $ville ) {
			$parts[] = 'à ' . $ville;
		}
		if ( $specialites && ! is_wp_error( $specialites ) ) {
			$parts[] = '— ' . implode( ', ', wp_list_pluck( $specialites, 'name' ) );
		}
		return implode( ' ', $parts ) . '. Coordonnées, horaires et avis sur DentisteQC.';
	}

	if ( is_tax( 'region' ) ) {
		$term = get_queried_object();
		return $term ? "Cliniques dentaires dans la région de {$term->name} : comparez {$term->count} cliniques, consultez leurs disponibilités et contactez-les directement." : '';
	}

	if ( is_tax( 'specialite' ) ) {
		$term = get_queried_object();
		return $term ? "Cliniques offrant {$term->name} au Québec : comparez {$term->count} cliniques, consultez leurs disponibilités et contactez-les directement." : '';
	}

	if ( is_post_type_archive( 'clinique' ) ) {
		return "Parcourez l'annuaire des cliniques dentaires du Québec : filtrez par région, spécialité ou disponibilité.";
	}

	if ( is_front_page() ) {
		return "DentisteQC référence des cliniques dentaires partout au Québec. Trouvez un dentiste par région ou spécialité, consultez les avis et contactez la clinique directement.";
	}

	return '';
}

function acdq_truncate_description( $desc, $max = 160 ) {
	$desc = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $desc ) ) );
	if ( '' === $desc ) {
		return '';
	}
	if ( mb_strlen( $desc ) > $max ) {
		$desc = mb_substr( $desc, 0, $max - 1 ) . '…';
	}
	return $desc;
}

function acdq_meta_description() {
	$desc = acdq_truncate_description( acdq_get_page_description(), 160 );
	if ( ! $desc ) {
		return;
	}
	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
}
add_action( 'wp_head', 'acdq_meta_description', 1 );

/**
 * Absolute URL for the current page — only needs to cover the page types
 * this theme actually has (singular, the two taxonomies, the clinic
 * archive); anything else falls back to the homepage.
 */
function acdq_current_url() {
	if ( is_singular() ) {
		$url = get_permalink();
		return $url ? $url : home_url( '/' );
	}
	if ( is_tax() ) {
		$term = get_queried_object();
		$url  = $term ? get_term_link( $term ) : false;
		return ( $url && ! is_wp_error( $url ) ) ? $url : home_url( '/' );
	}
	if ( is_post_type_archive( 'clinique' ) ) {
		$url = get_post_type_archive_link( 'clinique' );
		return $url ? $url : home_url( '/' );
	}
	return home_url( '/' );
}

/**
 * Best available image for sharing the current page: a clinic's own photo,
 * or the site logo, or nothing (omitting og:image is better than pointing
 * at a broken URL).
 */
function acdq_open_graph_image() {
	if ( is_singular( 'clinique' ) ) {
		if ( function_exists( 'acdq_get_clinic_photos' ) ) {
			$photos = acdq_get_clinic_photos( get_the_ID(), 'large' );
			if ( $photos ) {
				return $photos[0]['url'];
			}
		}
		if ( function_exists( 'acdq_get_card_image' ) ) {
			$card = acdq_get_card_image( get_the_ID(), 'large' );
			if ( $card ) {
				return $card['url'];
			}
		}
	}

	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$src = wp_get_attachment_image_src( $logo_id, 'large' );
		if ( $src ) {
			return $src[0];
		}
	}

	return '';
}

function acdq_open_graph_tags() {
	if ( is_admin() || is_search() || is_404() ) {
		return;
	}

	$title = wp_get_document_title();
	$desc  = acdq_truncate_description( acdq_get_page_description(), 200 );
	$url   = acdq_current_url();
	$image = acdq_open_graph_image();

	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:locale" content="fr_CA">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
}
add_action( 'wp_head', 'acdq_open_graph_tags', 2 );

/**
 * schema.org structured data for a single clinic page (Dentist, a
 * LocalBusiness subtype) — name, address, phone, geo, hours, and rating,
 * built from the same ACF fields the page itself already displays. This is
 * what gives search engines the raw material for rich results / map-pack
 * listings; nothing here is shown to visitors directly.
 */
function acdq_clinic_schema() {
	if ( ! is_singular( 'clinique' ) ) {
		return;
	}

	$post_id     = get_the_ID();
	$tel         = get_field( 'telephone', $post_id );
	$lat         = get_field( 'latitude', $post_id );
	$lng         = get_field( 'longitude', $post_id );
	$site        = get_field( 'site_web', $post_id );
	$adresse     = get_field( 'adresse', $post_id );
	$ville       = get_field( 'ville', $post_id );
	$code_postal = get_field( 'code_postal', $post_id );
	$rating      = function_exists( 'acdq_get_average_rating' ) ? acdq_get_average_rating( $post_id ) : array( 'average' => 0, 'count' => 0 );
	$image       = function_exists( 'acdq_get_card_image' ) ? acdq_get_card_image( $post_id, 'large' ) : null;

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Dentist',
		'name'     => get_the_title( $post_id ),
		'url'      => get_permalink( $post_id ),
	);

	if ( $image ) {
		$schema['image'] = $image['url'];
	}
	if ( $tel ) {
		$schema['telephone'] = $tel;
	}
	if ( $site ) {
		$schema['sameAs'] = array( $site );
	}

	if ( $adresse || $ville ) {
		$schema['address'] = array_filter( array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $adresse ? $adresse : null,
			'addressLocality' => $ville ? $ville : null,
			'postalCode'      => $code_postal ? $code_postal : null,
			'addressRegion'   => 'QC',
			'addressCountry'  => 'CA',
		) );
	}

	if ( is_numeric( $lat ) && is_numeric( $lng ) ) {
		$schema['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $lat,
			'longitude' => (float) $lng,
		);
	}

	// Same "8:30 – 20:00" / "8h30 - 17h00" parsing pattern as
	// acdq_get_open_status() in functions.php, kept in sync with it.
	$days = array(
		'lundi' => 'Monday', 'mardi' => 'Tuesday', 'mercredi' => 'Wednesday', 'jeudi' => 'Thursday',
		'vendredi' => 'Friday', 'samedi' => 'Saturday', 'dimanche' => 'Sunday',
	);
	$hours = array();
	foreach ( $days as $key => $en_day ) {
		$val = get_field( 'heures_' . $key, $post_id );
		if ( ! $val || 'fermé' === mb_strtolower( trim( $val ) ) ) {
			continue;
		}
		if ( preg_match( '/(\d{1,2})[h:](\d{2})\s*[-–—]\s*(\d{1,2})[h:](\d{2})/u', $val, $m ) ) {
			$hours[] = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => $en_day,
				'opens'     => sprintf( '%02d:%02d', $m[1], $m[2] ),
				'closes'    => sprintf( '%02d:%02d', $m[3], $m[4] ),
			);
		}
	}
	if ( $hours ) {
		$schema['openingHoursSpecification'] = $hours;
	}

	if ( $rating['count'] > 0 ) {
		$schema['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => $rating['average'],
			'reviewCount' => $rating['count'],
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'acdq_clinic_schema', 5 );

/**
 * Minor hardening/performance cleanup: stop advertising the exact WordPress
 * version in every page's <head>, and stop loading the emoji-conversion
 * script/styles sitewide — this theme doesn't rely on browsers lacking
 * native emoji support.
 */
remove_action( 'wp_head', 'wp_generator' );

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

add_filter( 'tinymce_plugins', function ( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
} );

add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$urls = array_diff( $urls, array( 'https://s.w.org' ) );
	}
	return $urls;
}, 10, 2 );
