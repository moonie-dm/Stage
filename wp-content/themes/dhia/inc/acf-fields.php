<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Clinic photo gallery.
 *
 * The "Détails de la clinique" field group isn't defined anywhere in this theme's
 * code — it was built through the ACF admin UI, so its field group key lives only
 * in the database and isn't something we can safely target from here. Registering
 * a sibling field group via PHP (ACF's standard "local fields" approach) attaches
 * cleanly to the clinique post type without guessing at that key.
 *
 * ACF PRO's Gallery field type would be the more natural fit for this, but this
 * theme has no way to detect from the repo whether PRO or Free is installed on a
 * given deployment. Six plain Image fields work identically on both, so that's
 * what's registered here — acdq_get_gallery_photos() below is the single place
 * that would need to change if this is later upgraded to a real Gallery field.
 */
add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

	$fields = array();
	for ( $i = 1; $i <= 6; $i++ ) {
		$fields[] = array(
			'key'           => 'field_acdq_galerie_photo_' . $i,
			'label'         => 'Photo ' . $i,
			'name'          => 'galerie_photo_' . $i,
			'type'          => 'image',
			'return_format' => 'array',
			'preview_size'  => 'medium',
			'library'       => 'all',
		);
	}

	acf_add_local_field_group( array(
		'key'      => 'group_acdq_galerie_photos',
		'title'    => 'Galerie de photos',
		'fields'   => $fields,
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'clinique',
				),
			),
		),
		'menu_order'            => 5,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'instructions'          => 'Ajoutez jusqu\'à 6 vraies photos de la clinique (façade, salle d\'attente, équipe, salle de soins). Laissez vide si aucune photo n\'est disponible — la fiche affichera un visuel de remplacement plutôt qu\'un espace vide.',
	) );
} );

/**
 * Uploaded gallery photos for a clinic, in order, skipping any empty slots.
 * Each entry is the raw ACF image array (id, url, alt, sizes, ...).
 */
function acdq_get_gallery_photos( $post_id = null ) {
	if ( ! $post_id ) $post_id = get_the_ID();
	$photos = array();
	for ( $i = 1; $i <= 6; $i++ ) {
		$img = get_field( 'galerie_photo_' . $i, $post_id );
		if ( $img && ! empty( $img['url'] ) ) {
			$photos[] = $img;
		}
	}
	return $photos;
}

/**
 * Photos for the clinic-page carousel: gallery photos only. Deliberately does
 * NOT fall back to the featured image — that image is often a small logo
 * (from CSV imports especially), and stretching a logo to fill a large photo
 * slot looks broken rather than "no photo yet". Empty array means show the
 * branded placeholder instead.
 */
function acdq_get_clinic_photos( $post_id = null, $size = 'large' ) {
	if ( ! $post_id ) $post_id = get_the_ID();

	$photos = array();
	foreach ( acdq_get_gallery_photos( $post_id ) as $img ) {
		$url = ! empty( $img['sizes'][ $size ] ) ? $img['sizes'][ $size ] : $img['url'];
		$photos[] = array( 'url' => $url, 'alt' => ! empty( $img['alt'] ) ? $img['alt'] : get_the_title( $post_id ) );
	}

	return $photos;
}

/**
 * The single image to show for a clinic on a card/list row: first gallery
 * photo if there is one, otherwise the featured image, otherwise null
 * (callers fall back to the placeholder).
 */
function acdq_get_card_image( $post_id = null, $size = 'medium' ) {
	if ( ! $post_id ) $post_id = get_the_ID();

	$gallery = acdq_get_gallery_photos( $post_id );
	if ( $gallery ) {
		$img = $gallery[0];
		$url = ! empty( $img['sizes'][ $size ] ) ? $img['sizes'][ $size ] : $img['url'];
		return array( 'url' => $url, 'alt' => ! empty( $img['alt'] ) ? $img['alt'] : get_the_title( $post_id ) );
	}

	if ( has_post_thumbnail( $post_id ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $size );
		if ( $src ) {
			return array( 'url' => $src[0], 'alt' => get_the_title( $post_id ) );
		}
	}

	return null;
}
