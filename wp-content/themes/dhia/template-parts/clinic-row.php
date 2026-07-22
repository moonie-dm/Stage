<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$accepte = get_field( 'accepte_nouveaux_patients' );
$statut  = acdq_get_open_status();
$tel     = get_field( 'telephone' );
$lat     = get_field( 'latitude' );
$lng     = get_field( 'longitude' );
$rating  = acdq_get_average_rating( get_the_ID() ); // returns 0 / no reviews if system not yet ported
?>
<article class="clinic-row" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>">
	<div class="clinic-row-top">
		<span class="distance-badge" hidden></span>
		<span class="open-status <?php echo $statut['ouvert'] ? 'is-open' : 'is-closed'; ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
			<?php echo esc_html( $statut['texte'] ); ?>
		</span>
		<?php if ( function_exists( 'acdq_get_average_rating' ) && $rating['count'] > 0 ) : ?>
			<span class="rating-inline">★★★★★ (<?php echo esc_html( $rating['count'] ); ?>)</span>
		<?php endif; ?>
	</div>

	<h2 class="clinic-row-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?> →</a></h2>

	<?php
	$specs = get_the_terms( get_the_ID(), 'specialite' );
	if ( $specs && ! is_wp_error( $specs ) ) : ?>
		<p class="clinic-row-tags"><?php echo esc_html( implode( ', ', wp_list_pluck( $specs, 'name' ) ) ); ?></p>
	<?php endif; ?>

	<p class="clinic-row-meta">
		<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 22s7-7.2 7-12.5S16.4 2 12 2 5 4.6 5 9.5 12 22 12 22Z" stroke="currentColor" stroke-width="1.6"/></svg>
		<?php echo esc_html( get_field( 'adresse' ) . ', ' . get_field( 'ville' ) ); ?>
	</p>
	<?php if ( $tel ) : ?>
		<p class="clinic-row-meta">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z" stroke="currentColor" stroke-width="1.5"/></svg>
			<?php echo esc_html( $tel ); ?>
		</p>
	<?php endif; ?>

	<div class="clinic-row-actions">
		<?php if ( $accepte ) : ?>
			<div class="trust-box">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z" stroke="currentColor" stroke-width="1.6"/></svg>
				Accepte de nouveaux patients
			</div>
		<?php endif; ?>
		<a href="<?php the_permalink(); ?>" class="btn btn-primary">Demandez un rendez-vous</a>
	</div>
</article>