<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$specialites = get_terms( array( 'taxonomy' => 'specialite', 'hide_empty' => true ) );
$regions     = get_terms( array( 'taxonomy' => 'region', 'hide_empty' => true ) );
?>

<div class="container">
	<h1 class="archive-title">Cliniques dentaires avec les meilleures évaluations au Québec</h1>

	<div class="filter-chips">
		<button type="button" class="filter-chip" data-filter="open">Ouvert</button>
		<button type="button" class="filter-chip" data-filter="accepting">Nouveaux patients</button>
		<select class="filter-select" data-role="specialite">
			<option value="">Toutes spécialités</option>
			<?php if ( ! is_wp_error( $specialites ) ) foreach ( $specialites as $s ) : ?>
				<option value="<?php echo esc_attr( $s->slug ); ?>"><?php echo esc_html( $s->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<select class="filter-select" data-role="region">
			<option value="">Toutes les régions</option>
			<?php if ( ! is_wp_error( $regions ) ) foreach ( $regions as $r ) : ?>
				<option value="<?php echo esc_attr( $r->slug ); ?>"><?php echo esc_html( $r->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<select class="filter-select" data-role="rating">
			<option value="0">Toutes les notes</option>
			<option value="4">4+ étoiles</option>
			<option value="3">3+ étoiles</option>
		</select>
		<span class="sort-label">Trier
			<select class="filter-select" data-role="sort">
				<option>Plus récent</option>
				<option>Nom (A-Z)</option>
				<option>Plus proche</option>
			</select>
		</span>
	</div>

	<div class="directory-split">
		<div class="clinic-row-list">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post();
				get_template_part( 'template-parts/clinic-row' );
			endwhile; else : ?>
				<p>Aucune clinique n'est encore inscrite.</p>
			<?php endif; ?>

			<?php the_posts_pagination( array( 'prev_text' => '←', 'next_text' => '→' ) ); ?>
		</div>

		<div id="acdq-map" class="directory-map"></div>
	</div>
</div>

<?php get_footer(); ?>