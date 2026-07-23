<?php
/**
 * dhia functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package dhia
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.4.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function dhia_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on dhia, use a find and replace
		* to change 'dhia' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'dhia', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'dhia' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'dhia_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'dhia_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function dhia_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'dhia_content_width', 640 );
}
add_action( 'after_setup_theme', 'dhia_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function dhia_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'dhia' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'dhia' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'dhia_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function dhia_scripts() {
	wp_enqueue_style( 'dhia-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'dhia-style', 'rtl', 'replace' );

	wp_enqueue_script( 'dhia-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	$is_clinic_listing = is_post_type_archive( 'clinique' ) || is_tax( array( 'region', 'specialite' ) ) || is_search();

	if ( $is_clinic_listing ) {
		wp_enqueue_style( 'acdq-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'acdq-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
		wp_enqueue_script( 'acdq-map', get_template_directory_uri() . '/assets/js/map.js', array( 'acdq-leaflet' ), _S_VERSION, true );
		wp_enqueue_script( 'acdq-distance', get_template_directory_uri() . '/assets/js/distance.js', array(), _S_VERSION, true );
	}
	if ( $is_clinic_listing ) {
		wp_enqueue_script( 'acdq-filters', get_template_directory_uri() . '/assets/js/filters.js', array(), _S_VERSION, true );
		wp_localize_script( 'acdq-filters', 'acdqFilters', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'acdq_nonce' ),
			'search'  => is_search() ? get_search_query() : '',
		) );
	}
}
add_action( 'wp_enqueue_scripts', 'dhia_scripts' );

/**
 * This site's only searchable content is the clinic directory, so route every
 * front-end search through the 'clinique' post type instead of core posts/pages.
 */
function acdq_search_clinics_only( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		$query->set( 'post_type', 'clinique' );
	}
}
add_action( 'pre_get_posts', 'acdq_search_clinics_only' );

require get_template_directory() . '/inc/ajax-filters.php';
/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}
require get_template_directory() . '/inc/csv-importer.php';
function acdq_get_open_status( $post_id = null ) {
	if ( ! $post_id ) $post_id = get_the_ID();
	$jours = array( 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche' );
	$jour_index = (int) date_i18n( 'N' ) - 1;
	$horaire = get_field( 'heures_' . $jours[ $jour_index ], $post_id );

	if ( ! $horaire || strtolower( trim( $horaire ) ) === 'fermé' ) {
		return array( 'ouvert' => false, 'texte' => 'Fermé aujourd\'hui' );
	}
	// Accepts both "8h30 - 17h00" and the "8:30 – 20:00" format actually used in the
	// clinic hours fields (colon instead of "h", en/em dash instead of a plain hyphen).
	if ( preg_match( '/(\d{1,2})[h:](\d{2})\s*[-–—]\s*(\d{1,2})[h:](\d{2})/u', $horaire, $m ) ) {
		$maintenant = (int) date_i18n( 'Hi' );
		$debut = (int) ( $m[1] . $m[2] );
		$fin   = (int) ( $m[3] . $m[4] );
		$ouvert = $maintenant >= $debut && $maintenant <= $fin;
		return array( 'ouvert' => $ouvert, 'texte' => ( $ouvert ? 'Ouvert' : 'Fermé' ) . ' · ' . $horaire );
	}
	return array( 'ouvert' => false, 'texte' => $horaire );
}

/**
 * Great-circle distance in km between two lat/lng points.
 */
function acdq_haversine_km( $lat1, $lng1, $lat2, $lng2 ) {
	$r = 6371;
	$d_lat = deg2rad( $lat2 - $lat1 );
	$d_lng = deg2rad( $lng2 - $lng1 );
	$a = sin( $d_lat / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $d_lng / 2 ) ** 2;
	return $r * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
}

/**
 * Distance in km from a given point to a clinique, or INF if it has no coordinates
 * (pushes clinics missing lat/lng to the end of a "closest first" sort instead of
 * crashing the comparison).
 */
function acdq_distance_to_clinic( $post_id, $lat, $lng ) {
	$clinic_lat = get_field( 'latitude', $post_id );
	$clinic_lng = get_field( 'longitude', $post_id );
	if ( $clinic_lat === '' || $clinic_lat === false || $clinic_lng === '' || $clinic_lng === false ) {
		return INF;
	}
	return acdq_haversine_km( $lat, $lng, (float) $clinic_lat, (float) $clinic_lng );
}

/**
 * Add a 1-5 star rating field to the comment form, only for cliniques.
 */
function acdq_comment_form_add_rating( $comment_field ) {
	if ( 'clinique' !== get_post_type() ) {
		return $comment_field;
	}
	$options = '';
	for ( $i = 5; $i >= 1; $i-- ) {
		$options .= '<option value="' . $i . '">' . $i . ' - ' . str_repeat( '★', $i ) . '</option>';
	}
	$rating_field = '<p class="comment-form-rating">' .
		'<label for="acdq_rating">' . esc_html__( 'Votre note', 'dhia' ) . '</label> ' .
		'<select name="acdq_rating" id="acdq_rating" required>' .
		'<option value="">' . esc_html__( 'Choisir une note', 'dhia' ) . '</option>' . $options .
		'</select></p>';
	return $rating_field . $comment_field;
}
add_filter( 'comment_form_field_comment', 'acdq_comment_form_add_rating' );

/**
 * Store the submitted rating as comment meta.
 */
function acdq_save_comment_rating( $comment_id ) {
	if ( ! isset( $_POST['acdq_rating'] ) ) {
		return;
	}
	$rating = (int) $_POST['acdq_rating'];
	if ( $rating < 1 || $rating > 5 ) {
		return;
	}
	add_comment_meta( $comment_id, 'acdq_rating', $rating, true );
}
add_action( 'comment_post', 'acdq_save_comment_rating' );

/**
 * Show each review's star rating above its comment text.
 */
function acdq_prepend_rating_to_comment_text( $comment_text, $comment = null ) {
	if ( ! $comment || 'clinique' !== get_post_type( $comment->comment_post_ID ) ) {
		return $comment_text;
	}
	$rating = (int) get_comment_meta( $comment->comment_ID, 'acdq_rating', true );
	if ( $rating < 1 || $rating > 5 ) {
		return $comment_text;
	}
	$stars = '<p class="comment-rating" aria-label="' . esc_attr( sprintf( '%d sur 5', $rating ) ) . '">' .
		str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) . '</p>';
	return $stars . $comment_text;
}
add_filter( 'comment_text', 'acdq_prepend_rating_to_comment_text', 10, 2 );

/**
 * Average rating + review count for a clinique, based on approved comment ratings.
 */
function acdq_get_average_rating( $post_id = null ) {
	if ( ! $post_id ) $post_id = get_the_ID();

	$comments = get_comments( array(
		'post_id' => $post_id,
		'status'  => 'approve',
		'type'    => 'comment',
	) );

	$total = 0;
	$count = 0;
	foreach ( $comments as $comment ) {
		$rating = (int) get_comment_meta( $comment->comment_ID, 'acdq_rating', true );
		if ( $rating >= 1 && $rating <= 5 ) {
			$total += $rating;
			$count++;
		}
	}

	return array(
		'average' => $count ? round( $total / $count, 1 ) : 0,
		'count'   => $count,
	);
}

