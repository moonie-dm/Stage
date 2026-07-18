<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@500;600&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="site-header-inner">
		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		</div>
		<nav class="main-navigation" aria-label="<?php esc_attr_e( 'Menu principal', 'dhia' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'menu-1',
				'container'      => false,
				'fallback_cb'    => false,
			) );
			?>
		</nav>
		<a class="btn btn-primary" href="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>">
			<?php _e( 'Trouver une clinique', 'dhia' ); ?>
		</a>
	</div>
</header>