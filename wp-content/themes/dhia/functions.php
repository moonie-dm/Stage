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
	define( '_S_VERSION', '1.0.1' );
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
	if ( is_post_type_archive( 'clinique' ) || is_tax( array( 'region', 'specialite' ) ) ) {
	wp_enqueue_style( 'acdq-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
	wp_enqueue_script( 'acdq-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
	wp_enqueue_script( 'acdq-map', get_template_directory_uri() . '/assets/js/map.js', array( 'acdq-leaflet' ), _S_VERSION, true );
	wp_enqueue_script( 'acdq-distance', get_template_directory_uri() . '/assets/js/distance.js', array(), _S_VERSION, true );
}
}
add_action( 'wp_enqueue_scripts', 'dhia_scripts' );

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
	if ( preg_match( '/(\d{1,2})h(\d{2})\s*-\s*(\d{1,2})h(\d{2})/', $horaire, $m ) ) {
		$maintenant = (int) date_i18n( 'Hi' );
		$debut = (int) ( $m[1] . $m[2] );
		$fin   = (int) ( $m[3] . $m[4] );
		$ouvert = $maintenant >= $debut && $maintenant <= $fin;
		return array( 'ouvert' => $ouvert, 'texte' => ( $ouvert ? 'Ouvert' : 'Fermé' ) . ' · ' . $horaire );
	}
	return array( 'ouvert' => false, 'texte' => $horaire );
}

