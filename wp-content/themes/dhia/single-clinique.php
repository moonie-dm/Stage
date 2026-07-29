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
	$site_host   = $site ? preg_replace( '#^https?://(www\.)?#i', '', untrailingslashit( $site ) ) : '';
	$rating      = function_exists( 'acdq_get_average_rating' ) ? acdq_get_average_rating( get_the_ID() ) : array( 'average' => 0, 'count' => 0 );
	$photos = function_exists( 'acdq_get_clinic_photos' ) ? acdq_get_clinic_photos( get_the_ID() ) : array();

	// acdq_get_clinic_photos() deliberately only returns real gallery photos —
	// but the fiche should always show the featured image ("image mise en
	// avant") when one is set, regardless of whether gallery photos also
	// exist, so it's prepended as the first slide rather than only appearing
	// as a fallback when the gallery is empty.
	if ( has_post_thumbnail() ) {
		$featured_src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $featured_src ) {
			array_unshift( $photos, array( 'url' => $featured_src[0], 'alt' => get_the_title() ) );
		}
	}

	// Most recent approved review, for the compact "Avis" summary card — the
	// full list (and the comment form) still lives further down the page.
	$latest_review = null;
	if ( $rating['count'] > 0 ) {
		$recent = get_comments( array( 'post_id' => get_the_ID(), 'status' => 'approve', 'number' => 1, 'orderby' => 'comment_date', 'order' => 'DESC' ) );
		if ( $recent ) $latest_review = $recent[0];
	}
	?>

	<!-- HERO -->
	<section class="clinic-hero">
		<div class="container clinic-hero-inner">
			<div class="clinic-hero-info">
				<h1><?php the_title(); ?></h1>

				<?php if ( $specialites && ! is_wp_error( $specialites ) ) : ?>
					<p class="clinic-hero-subtitle"><?php echo esc_html( implode( ', ', wp_list_pluck( $specialites, 'name' ) ) ); ?></p>
				<?php endif; ?>

				<p class="clinic-hero-status <?php echo $statut['ouvert'] ? 'is-open' : 'is-closed'; ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
					<?php echo esc_html( $statut['texte'] ); ?>
					<a href="#acdq-hours">Voir les heures d'ouverture ↓</a>
				</p>

				<p class="clinic-hero-meta">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 22s7-7.2 7-12.5S16.4 2 12 2 5 4.6 5 9.5 12 22 12 22Z" stroke="currentColor" stroke-width="1.6"/></svg>
					<?php echo esc_html( acdq_format_clinic_address( get_the_ID() ) ); ?>
				</p>
				<?php if ( $tel ) : ?>
					<p class="clinic-hero-meta">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z" stroke="currentColor" stroke-width="1.5"/></svg>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ); ?>"><?php echo esc_html( $tel ); ?></a>
					</p>
				<?php endif; ?>
				<?php if ( $site ) : ?>
					<p class="clinic-hero-meta">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M3 12h18M12 3c2.5 2.5 4 5.7 4 9s-1.5 6.5-4 9c-2.5-2.5-4-5.7-4-9s1.5-6.5 4-9Z" stroke="currentColor" stroke-width="1.4"/></svg>
						<a href="<?php echo esc_url( $site ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $site_host ); ?></a>
					</p>
				<?php endif; ?>
			</div>

			<div class="clinic-hero-side">
				<?php if ( $rating['count'] > 0 ) : ?>
					<p class="clinic-hero-rating">★★★★★ <span>(<?php echo esc_html( $rating['count'] ); ?>)</span></p>
				<?php endif; ?>

				<?php if ( $tel ) : ?>
					<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ); ?>" class="btn btn-primary">Appeler pour un rendez-vous</a>
				<?php else : ?>
					<a href="#acdq-directions" class="btn btn-primary">Comment prendre rendez-vous</a>
				<?php endif; ?>

				<?php if ( $accepte ) : ?>
					<div class="clinic-hero-badges">
						<div class="trust-box">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z" stroke="currentColor" stroke-width="1.6"/></svg>
							Accepte de nouveaux patients
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="clinic-hero-media">
				<?php if ( $photos ) : ?>
					<div class="clinic-gallery">
						<div class="clinic-gallery-viewport">
							<div class="clinic-gallery-track">
								<?php foreach ( $photos as $photo ) : ?>
									<div class="clinic-gallery-slide">
										<img src="<?php echo esc_url( $photo['url'] ); ?>" alt="<?php echo esc_attr( $photo['alt'] ); ?>" loading="lazy">
									</div>
								<?php endforeach; ?>
							</div>
							<?php if ( count( $photos ) > 1 ) : ?>
								<button type="button" class="clinic-gallery-arrow clinic-gallery-prev" aria-label="Photo précédente">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M15 5 8 12l7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
								</button>
								<button type="button" class="clinic-gallery-arrow clinic-gallery-next" aria-label="Photo suivante">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
								</button>
							<?php endif; ?>
						</div>
						<?php if ( count( $photos ) > 1 ) : ?>
							<div class="clinic-gallery-dots">
								<?php foreach ( $photos as $i => $photo ) : ?>
									<button type="button" class="clinic-gallery-dot <?php echo 0 === $i ? 'is-active' : ''; ?>" aria-label="Aller à la photo <?php echo esc_attr( $i + 1 ); ?>"></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="clinic-gallery-empty">
						<svg viewBox="0 0 24 24" fill="none"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z" stroke="currentColor" stroke-width="1.4"/></svg>
						<span>Photos à venir</span>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<div class="container clinic-body">

		<!-- AVIS / SERVICES / HORAIRES -->
		<div class="clinic-row-3col">
			<section class="clinic-panel">
				<h2>Avis</h2>
				<?php if ( $rating['count'] > 0 ) : ?>
					<p class="clinic-panel-lead">★★★★★ <?php echo esc_html( $rating['average'] ); ?> sur 5 <span>(<?php echo esc_html( $rating['count'] ); ?> avis)</span></p>
					<?php if ( $latest_review ) : ?>
						<div class="clinic-review-preview">
							<p class="clinic-review-preview-author">
								<?php echo esc_html( $latest_review->comment_author ); ?> ·
								<?php echo esc_html( human_time_diff( strtotime( $latest_review->comment_date ), current_time( 'timestamp' ) ) ); ?>
							</p>
							<p class="clinic-review-preview-text"><?php echo esc_html( wp_trim_words( $latest_review->comment_content, 22 ) ); ?></p>
						</div>
					<?php endif; ?>
				<?php else : ?>
					<p class="clinic-panel-empty">Aucun avis pour l'instant. Soyez le premier à laisser un avis.</p>
				<?php endif; ?>
				<a class="clinic-panel-link" href="#acdq-full-reviews">Voir tous les avis →</a>
			</section>

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

			<section class="clinic-panel" id="acdq-hours">
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
		</div>

		<?php if ( get_the_content() ) : ?>
		<section class="clinic-panel">
			<h2>À propos</h2>
			<div class="clinic-content"><?php the_content(); ?></div>
		</section>
		<?php endif; ?>

		<!-- EMPLACEMENT + RENDEZ-VOUS -->
		<div class="clinic-directions-row" id="acdq-directions">
			<section class="clinic-panel clinic-directions-main">
				<h2>Emplacement</h2>
				<?php if ( get_field( 'latitude' ) && get_field( 'longitude' ) ) : ?>
					<div id="acdq-clinic-map" class="clinic-map" data-lat="<?php echo esc_attr( get_field( 'latitude' ) ); ?>" data-lng="<?php echo esc_attr( get_field( 'longitude' ) ); ?>"></div>
				<?php else : ?>
					<p class="clinic-panel-empty">Emplacement non disponible pour l'instant.</p>
				<?php endif; ?>
			</section>

			<aside class="clinic-sidebar-cta">
				<h2>Demandez un rendez-vous</h2>
				<?php if ( $tel ) : ?>
					<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ); ?>" class="btn btn-ghost"><?php echo esc_html( $tel ); ?></a>
				<?php endif; ?>
				<?php if ( $accepte ) : ?>
					<div class="trust-box trust-box--on-dark">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z" stroke="currentColor" stroke-width="1.6"/></svg>
						Accepte de nouveaux patients
					</div>
				<?php endif; ?>
				<p class="clinic-sidebar-cta-note">Pas de réservation en ligne pour l'instant — appelez directement pour prendre rendez-vous.</p>
			</aside>
		</div>

		<!-- AVIS COMPLETS -->
		<section class="clinic-panel clinic-reviews" id="acdq-full-reviews">
			<h2>Avis : <?php the_title(); ?></h2>
			<?php if ( $rating['count'] > 0 ) : ?>
				<p class="clinic-panel-lead">★★★★★ <?php echo esc_html( $rating['average'] ); ?> sur 5 (<?php echo esc_html( $rating['count'] ); ?> avis)</p>
			<?php else : ?>
				<p class="clinic-panel-empty">Aucun avis pour l'instant. Soyez le premier à laisser un avis.</p>
			<?php endif; ?>
			<?php comments_template(); ?>
		</section>

		<!-- PARTAGER -->
		<div class="clinic-share clinic-share--footer">
			<span class="clinic-share-label">Partager :</span>
			<a class="clinic-share-btn" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode( get_permalink() ); ?>" aria-label="Partager sur Facebook">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 8.5h2V5.3c-.3 0-1.5-.1-2.9-.1-2.9 0-4.8 1.7-4.8 4.9v2.6H6.4V16h2.9v8h3.4v-8h2.8l.4-3.3h-3.2V10c0-.9.3-1.5 1.3-1.5Z" fill="currentColor"/></svg>
			</a>
			<a class="clinic-share-btn" href="mailto:?subject=<?php echo esc_attr( rawurlencode( get_the_title() ) ); ?>&amp;body=<?php echo esc_attr( rawurlencode( get_permalink() ) ); ?>" aria-label="Partager par courriel">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4V6Z" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</a>
			<button type="button" class="clinic-share-btn clinic-share-copy" data-url="<?php echo esc_url( get_permalink() ); ?>" aria-label="Copier le lien">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="9" y="9" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M5 15V6a1 1 0 0 1 1-1h9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
				<span class="clinic-share-copy-label">Copier le lien</span>
			</button>
		</div>
	</div>

<?php endwhile;
get_footer(); ?>
