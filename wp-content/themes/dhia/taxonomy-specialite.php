<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
get_template_part( 'template-parts/breadcrumbs' );
$term = get_queried_object();
?>

<section class="page-hero">
	<div class="container">
		<span class="eyebrow"><?php echo esc_html( $term->count ); ?> cliniques pour cette spécialité</span>
		<h1><?php single_term_title(); ?></h1>
	</div>
</section>

<div class="directory-layout container">
	<aside class="filters-sidebar">
		<div class="filter-group">
			<p class="filter-label">Région</p>
			<?php
			$regions = get_terms( array( 'taxonomy' => 'region', 'hide_empty' => true ) );
			if ( ! is_wp_error( $regions ) ) foreach ( $regions as $r ) : ?>
				<a class="filter-link" href="<?php echo esc_url( get_term_link( $r ) ); ?>"><?php echo esc_html( $r->name ); ?></a>
			<?php endforeach; ?>
		</div>
		<p class="filter-group"><a class="filter-link" href="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>">← Toutes les spécialités</a></p>
	</aside>

	<div class="results-list">
		<div class="clinic-grid">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post();
				get_template_part( 'template-parts/clinic-card' );
			endwhile; else : ?>
				<p>Aucune clinique pour cette spécialité pour l'instant.</p>
			<?php endif; ?>
		</div>
		<?php the_posts_pagination( array( 'prev_text' => '←', 'next_text' => '→' ) ); ?>
	</div>
</div>

<?php get_footer(); ?>
