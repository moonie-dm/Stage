<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

while ( have_posts() ) : the_post();
	$region = get_the_terms( get_the_ID(), 'region' );
	$specialites = get_the_terms( get_the_ID(), 'specialite' );
	?>

	<article class="clinic-single">

		<h1><?php the_title(); ?></h1>

		<?php if ( $region && ! is_wp_error( $region ) ) : ?>
			<p class="clinic-region"><?php echo esc_html( $region[0]->name ); ?></p>
		<?php endif; ?>

		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'medium_large' ); ?>
		<?php endif; ?>

		<?php if ( get_field( 'accepte_nouveaux_patients' ) ) : ?>
			<p class="clinic-badge">✅ Accepte de nouveaux patients</p>
		<?php endif; ?>

		<?php if ( $specialites && ! is_wp_error( $specialites ) ) : ?>
			<ul class="clinic-specialites">
				<?php foreach ( $specialites as $s ) : ?>
					<li><?php echo esc_html( $s->name ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<section class="clinic-description">
			<?php the_content(); ?>
		</section>

		<section class="clinic-coordonnees">
			<h2>Coordonnées</h2>
			<p>
				<?php echo esc_html( get_field( 'adresse' ) ); ?>,
				<?php echo esc_html( get_field( 'ville' ) ); ?>
				<?php echo esc_html( get_field( 'code_postal' ) ); ?>
			</p>

			<?php $tel = get_field( 'telephone' ); if ( $tel ) : ?>
				<p><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ); ?>"><?php echo esc_html( $tel ); ?></a></p>
			<?php endif; ?>

			<?php $email = get_field( 'courriel' ); if ( $email ) : ?>
				<p><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
			<?php endif; ?>

			<?php $site = get_field( 'site_web' ); if ( $site ) : ?>
				<p><a href="<?php echo esc_url( $site ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $site ); ?></a></p>
			<?php endif; ?>
		</section>

		<section class="clinic-hours">
			<h2>Heures d'ouverture</h2>
			<ul>
				<?php
				$days = array(
					'heures_lundi'    => 'Lundi',
					'heures_mardi'    => 'Mardi',
					'heures_mercredi' => 'Mercredi',
					'heures_jeudi'    => 'Jeudi',
					'heures_vendredi' => 'Vendredi',
					'heures_samedi'   => 'Samedi',
					'heures_dimanche' => 'Dimanche',
				);
				foreach ( $days as $field => $label ) :
					$val = get_field( $field );
					?>
					<li><strong><?php echo esc_html( $label ); ?> :</strong> <?php echo esc_html( $val ? $val : 'Fermé' ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<p><a href="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>">&larr; Retour à l'annuaire</a></p>

	</article>

<?php endwhile;
get_footer();