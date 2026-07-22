<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
get_template_part( 'template-parts/breadcrumbs' );
?>

<section class="page-hero">
	<div class="container">
		<span class="eyebrow"><?php echo esc_html( wp_count_posts( 'clinique' )->publish ); ?> cliniques référencées</span>
		<h1>Annuaire des cliniques dentaires du Québec</h1>
	</div>
</section>

<div class="directory-layout container">
	<aside class="filters-sidebar">
		<div class="filter-group">
			<p class="filter-label">Spécialité</p>
			<?php
			$specialites = get_terms( array( 'taxonomy' => 'specialite', 'hide_empty' => true ) );
			if ( ! is_wp_error( $specialites ) ) foreach ( $specialites as $s ) : ?>
				<a class="filter-link" href="<?php echo esc_url( get_term_link( $s ) ); ?>"><?php echo esc_html( $s->name ); ?></a>
			<?php endforeach; ?>
		</div>
		<div class="filter-group">
			<p class="filter-label">Région</p>
			<?php
			$regions = get_terms( array( 'taxonomy' => 'region', 'hide_empty' => true ) );
			if ( ! is_wp_error( $regions ) ) foreach ( $regions as $r ) : ?>
				<a class="filter-link" href="<?php echo esc_url( get_term_link( $r ) ); ?>"><?php echo esc_html( $r->name ); ?></a>
			<?php endforeach; ?>
		</div>
	</aside>

	<div class="results-list">
		<div class="filter-chips">
	<button type="button" class="filter-chip is-active">Toutes</button>
	<button type="button" class="filter-chip">Ouvert maintenant</button>
	<button type="button" class="filter-chip">Nouveaux patients</button>
</div>
		<div class="clinic-grid">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post();
				get_template_part( 'template-parts/clinic-card' );
			endwhile; else : ?>
				<p>Aucune clinique n'est encore inscrite.</p>
			<?php endif; ?>
		</div>

		<?php
		the_posts_pagination( array(
			'prev_text' => '←',
			'next_text' => '→',
		) );
		?>
	</div>
</div>

<?php get_footer(); ?>