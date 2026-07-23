<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$accepte = get_field( 'accepte_nouveaux_patients' );
$statut  = acdq_get_open_status();
$tel     = get_field( 'telephone' );
?>
<article class="clinic-card">
	<div class="clinic-card-media">
		<a href="<?php the_permalink(); ?>">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'medium' ); ?>
			<?php else : ?>
				<div class="clinic-card-placeholder" aria-hidden="true">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z" stroke="currentColor" stroke-width="1.5"/></svg>
				</div>
			<?php endif; ?>
		</a>
		<span class="status-badge <?php echo $statut['ouvert'] ? 'is-open' : 'is-closed'; ?>"><?php echo esc_html( $statut['texte'] ); ?></span>
	</div>

	<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

	<p class="clinic-card-address">
		<?php echo esc_html( get_field( 'adresse' ) ); ?>, <?php echo esc_html( get_field( 'ville' ) ); ?>
	</p>

	<?php
	$regions = get_the_terms( get_the_ID(), 'region' );
	if ( $regions && ! is_wp_error( $regions ) ) : ?>
		<span class="tag"><?php echo esc_html( $regions[0]->name ); ?></span>
	<?php endif; ?>

	<div class="availability-chip <?php echo $accepte ? 'is-open' : 'is-closed'; ?>">
		<span class="dot"></span>
		<?php echo $accepte ? 'Accepte de nouveaux patients' : 'Complet actuellement'; ?>
	</div>

	<div class="clinic-card-actions">
		<?php if ( $tel ) : ?>
			<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ); ?>" class="btn btn-ghost">Appelez</a>
		<?php endif; ?>
		<a href="<?php the_permalink(); ?>" class="btn btn-primary">Voir la fiche</a>
	</div>
</article>
