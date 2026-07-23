<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

while ( have_posts() ) : the_post();
	$region      = get_the_terms( get_the_ID(), 'region' );
	$specialites = get_the_terms( get_the_ID(), 'specialite' );
	$accepte     = get_field( 'accepte_nouveaux_patients' );
	$statut      = acdq_get_open_status();
	$tel         = get_field( 'telephone' );
	$site        = get_field( 'site_web' );
	$rating      = function_exists( 'acdq_get_average_rating' ) ? acdq_get_average_rating( get_the_ID() ) : array( 'average' => 0, 'count' => 0 );
	?>

	<!-- HERO -->
	<section class="clinic-hero">
		<div class="container clinic-hero-inner">
			<div class="clinic-hero-info">
				<h1><?php the_title(); ?></h1>

				<?php if ( $specialites && ! is_wp_error( $specialites ) ) : ?>
					<p class="clinic-hero-subtitle"><?php echo esc_html( implode( ', ', wp_list_pluck( $specialites, 'name' ) ) ); ?></p>
				<?php endif; ?>

				<?php if ( $rating['count'] > 0 ) : ?>
					<p class="clinic-hero-rating">★★★★★ <span>(<?php echo esc_html( $rating['count'] ); ?>)</span></p>
				<?php endif; ?>

				<p class="clinic-hero-status <?php echo $statut['ouvert'] ? 'is-open' : 'is-closed'; ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
					<?php echo esc_html( $statut['texte'] ); ?>
				</p>

				<p class="clinic-hero-meta">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 22s7-7.2 7-12.5S16.4 2 12 2 5 4.6 5 9.5 12 22 12 22Z" stroke="currentColor" stroke-width="1.6"/></svg>
					<?php echo esc_html( get_field( 'adresse' ) . ', ' . get_field( 'ville' ) . ' ' . get_field( 'code_postal' ) ); ?>
				</p>
				<?php if ( $tel ) : ?>
					<p class="clinic-hero-meta">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z" stroke="currentColor" stroke-width="1.5"/></svg>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ); ?>"><?php echo esc_html( $tel ); ?></a>
						<?php if ( $site ) : ?> · <a href="<?php echo esc_url( $site ); ?>" target="_blank" rel="noopener">Site web</a><?php endif; ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="clinic-hero-actions">
				<a href="#acdq-booking" class="btn btn-primary">Demandez un rendez-vous</a>
				<?php if ( $accepte ) : ?>
					<div class="trust-box">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z" stroke="currentColor" stroke-width="1.6"/></svg>
						Accepte de nouveaux patients
					</div>
				<?php endif; ?>
			</div>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="clinic-hero-media"><?php the_post_thumbnail( 'large' ); ?></div>
			<?php endif; ?>
		</div>
	</section>

	<div class="container clinic-body">
		<!-- REVIEWS -->
		<section class="clinic-panel">
			<h2>Avis : <?php the_title(); ?></h2>
			<?php if ( $rating['count'] > 0 ) : ?>
				<p class="clinic-panel-lead">★★★★★ <?php echo esc_html( $rating['average'] ); ?> sur 5 (<?php echo esc_html( $rating['count'] ); ?> avis)</p>
			<?php else : ?>
				<p class="clinic-panel-empty">Aucun avis pour l'instant.</p>
			<?php endif; ?>
		</section>

		<!-- SERVICES -->
		<section class="clinic-panel">
			<h2>Services</h2>
			<?php if ( $specialites && ! is_wp_error( $specialites ) ) : ?>
				<div class="clinic-specialites">
					<?php foreach ( $specialites as $s ) echo '<span>' . esc_html( $s->name ) . '</span>'; ?>
				</div>
			<?php else : ?>
				<p class="clinic-panel-empty">Aucun service listé pour l'instant.</p>
			<?php endif; ?>
		</section>

		<!-- HOURS -->
		<section class="clinic-panel">
			<h2>Voir horaires</h2>
			<ul class="clinic-hours-list">
				<?php
				$days = array( 'lundi' => 'lun.', 'mardi' => 'mar.', 'mercredi' => 'mer.', 'jeudi' => 'jeu.', 'vendredi' => 'ven.', 'samedi' => 'sam.', 'dimanche' => 'dim.' );
				$today_index = (int) date_i18n( 'N' ) - 1;
				$day_keys = array_keys( $days );
				foreach ( $days as $key => $label ) :
					$val = get_field( 'heures_' . $key );
					$is_today = $day_keys[ $today_index ] === $key;
					?>
					<li class="<?php echo $is_today ? 'is-today' : ''; ?>">
						<span><?php echo esc_html( $label ); ?></span>
						<span><?php echo esc_html( $val ? $val : 'Fermé' ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<?php if ( get_the_content() ) : ?>
		<section class="clinic-panel">
			<h2>À propos</h2>
			<div class="clinic-content"><?php the_content(); ?></div>
		</section>
		<?php endif; ?>

		<section class="clinic-panel" id="acdq-booking">
			<h2>Demander un rendez-vous</h2>
			<p class="clinic-panel-empty">Formulaire de demande de rendez-vous à venir — pour l'instant, contactez la clinique directement au <?php echo esc_html( $tel ); ?>.</p>
		</section>
	</div>

<?php endwhile;
get_footer(); ?>