<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<section class="hero-search">
	<div class="container hero-search-inner">
		<span class="eyebrow"><?php echo esc_html( wp_count_posts( 'clinique' )->publish ); ?> cliniques référencées au Québec</span>
		<h1>Trouvez un dentiste, région par région</h1>
		<form class="search-bar" action="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>" method="get">
			<input type="search" name="s" placeholder="Nom, ville ou spécialité">
			<button type="submit" class="btn btn-primary">Rechercher</button>
		</form>
	</div>
</section>

<section class="container region-section">
	<h2>Parcourir par région</h2>
	<div class="region-grid">
		<?php
		$regions = get_terms( array( 'taxonomy' => 'region', 'hide_empty' => false, 'orderby' => 'name' ) );
		if ( ! is_wp_error( $regions ) ) foreach ( $regions as $region ) : ?>
			<a class="region-tile" href="<?php echo esc_url( get_term_link( $region ) ); ?>">
				<span class="region-tile-name"><?php echo esc_html( $region->name ); ?></span>
				<span class="region-tile-count acdq-mono"><?php echo esc_html( $region->count ); ?> cliniques</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<?php get_footer(); ?>