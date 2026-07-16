<?php/**
 * The template for displaying archive cliques
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package dhia
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<h1>Annuaire des cliniques dentaires</h1>

<div class="clinic-grid">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>

			<article class="clinic-card">
				<?php if ( has_post_thumbnail() ) : ?>
					<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a>
				<?php endif; ?>

				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

				<p>
					<?php echo esc_html( get_field( 'adresse' ) ); ?>,
					<?php echo esc_html( get_field( 'ville' ) ); ?>
				</p>

				<p><?php echo esc_html( get_field( 'telephone' ) ); ?></p>

				<?php
				$regions = get_the_terms( get_the_ID(), 'region' );
				if ( $regions && ! is_wp_error( $regions ) ) :
					?>
					<span class="tag"><?php echo esc_html( $regions[0]->name ); ?></span>
				<?php endif; ?>

				<a href="<?php the_permalink(); ?>">Voir la fiche →</a>
			</article>

		<?php endwhile; ?>
	<?php else : ?>
		<p>Aucune clinique n'est encore inscrite.</p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>