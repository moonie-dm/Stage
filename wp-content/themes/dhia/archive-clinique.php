<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$specialites = get_terms( array( 'taxonomy' => 'specialite', 'hide_empty' => true ) );
?>

<div class="container">
	<h1 class="archive-title">Cliniques dentaires avec les meilleures évaluations au Québec</h1>

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
			</select>
		</span>
	</div>

	<div id="acdq-map" class="directory-map"></div>

	<div class="clinic-row-list">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post();
			get_template_part( 'template-parts/clinic-row' );
		endwhile; else : ?>
			<p>Aucune clinique n'est encore inscrite.</p>
		<?php endif; ?>
	</div>
</div>

<?php get_footer(); ?>