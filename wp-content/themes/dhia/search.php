<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$specialites = get_terms( array( 'taxonomy' => 'specialite', 'hide_empty' => true ) );
$search_term = get_search_query();
?>

<div class="container">
	<h1 class="archive-title">Résultats pour : « <?php echo esc_html( $search_term ); ?> »</h1>

	<?php if ( have_posts() ) : ?>

		<div class="filter-chips">
			<button type="button" class="filter-chip" data-filter="open">Ouvert</button>
			<button type="button" class="filter-chip" data-filter="accepting">Nouveaux patients</button>
			<select class="filter-select">
				<option value="">Toutes spécialités</option>
				<?php if ( ! is_wp_error( $specialites ) ) foreach ( $specialites as $s ) : ?>
					<option value="<?php echo esc_attr( $s->slug ); ?>"><?php echo esc_html( $s->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="sort-label">Trier
				<select class="filter-select">
					<option>Plus récent</option>
					<option>Nom (A-Z)</option>
					<option>Plus proche</option>
				</select>
			</span>
		</div>

		<div class="directory-split">
			<div class="clinic-row-list">
				<?php while ( have_posts() ) : the_post();
					get_template_part( 'template-parts/clinic-row' );
				endwhile; ?>

				<?php the_posts_pagination( array( 'prev_text' => '←', 'next_text' => '→' ) ); ?>
			</div>

			<div id="acdq-map" class="directory-map"></div>
		</div>

	<?php else : ?>

		<div class="search-no-results">
			<h2>Aucun résultat</h2>
			<p class="clinic-panel-empty">Aucune clinique ne correspond à « <?php echo esc_html( $search_term ); ?> ». Essayez un autre nom, une autre ville ou une autre spécialité.</p>
			<a class="btn btn-primary" href="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>">Voir toutes les cliniques →</a>
		</div>

	<?php endif; ?>
</div>

<?php get_footer(); ?>
