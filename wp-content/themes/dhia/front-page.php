<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$total_cliniques   = wp_count_posts( 'clinique' )->publish;
$total_regions     = wp_count_terms( array( 'taxonomy' => 'region', 'hide_empty' => false ) );
$total_specialites = wp_count_terms( array( 'taxonomy' => 'specialite', 'hide_empty' => false ) );

$archive_url  = get_post_type_archive_link( 'clinique' );
$urgence_term = get_term_by( 'slug', 'urgence-dentaire', 'specialite' );
$urgence_url  = $urgence_term ? get_term_link( $urgence_term ) : add_query_arg( 'f', 'open', $archive_url );
?>

<!-- HERO + RECHERCHE -->
<section class="hero-search">
	<div class="container hero-search-inner">
		<div class="hero-search-copy">
			<span class="eyebrow"><?php echo esc_html( $total_cliniques ); ?> cliniques référencées au Québec</span>
			<h1>Trouvez un dentiste, région par région</h1>
			<p class="hero-search-sub">Comparez les cliniques, consultez les disponibilités et contactez-les directement — sans intermédiaire.</p>
			<form class="search-bar" id="acdq-search-form" action="<?php echo esc_url( $archive_url ); ?>" method="get">
				<input type="search" name="s" placeholder="Nom, ville ou spécialité — ou décrivez votre besoin">
				<button type="submit" class="btn btn-primary">Rechercher</button>
			</form>
			<a class="near-me-link" href="<?php echo esc_url( add_query_arg( 'near', '1', $archive_url ) ); ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 22s7-7.2 7-12.5S16.4 2 12 2 5 4.6 5 9.5 12 22 12 22Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="9.5" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg>
				Près de moi
			</a>
		</div>

		<div class="hero-search-art" aria-hidden="true">
			<svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect x="24" y="24" width="352" height="352" rx="130" fill="var(--accent-tint)"/>
				<circle cx="70" cy="330" r="7" fill="var(--accent)" opacity=".5"/>
				<circle cx="340" cy="90" r="5" fill="var(--accent-dark)" opacity=".5"/>
				<circle cx="330" cy="320" r="4" fill="var(--primary)" opacity=".3"/>

				<!-- abstract tooth -->
				<path d="M200 96c-46 0-78 34-78 79 0 46 16 88 38 112 6 7 15 7 18-3 5-16 12-26 22-26s17 10 22 26c3 10 12 10 18 3 22-24 38-66 38-112 0-45-32-79-78-79Z"
					fill="var(--surface)" stroke="var(--primary)" stroke-width="7" stroke-linejoin="round"/>

				<!-- floating location-pin chip, echoing the directory's map markers -->
				<g transform="translate(64 96)">
					<circle cx="28" cy="28" r="28" fill="var(--accent)"/>
					<path d="M28 14c-7.7 0-14 6-14 14 0 10 14 24 14 24s14-14 14-24c0-8-6.3-14-14-14Z" fill="var(--surface)"/>
					<circle cx="28" cy="27" r="4.5" fill="var(--accent)"/>
				</g>

				<!-- floating rating chip -->
				<g transform="translate(272 250)">
					<rect x="0" y="0" width="92" height="40" rx="20" fill="var(--surface)" stroke="var(--line)" stroke-width="1.5"/>
					<path d="M20 10l2.6 5.4 5.9.8-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9-4.3-4.1 5.9-.8L20 10Z" fill="var(--warm)"/>
					<text x="38" y="25" font-family="Inter, sans-serif" font-size="15" font-weight="600" fill="var(--ink)">4,8</text>
				</g>
			</svg>
		</div>
	</div>
	<div class="hero-pills">
		<a class="filter-chip" href="<?php echo esc_url( add_query_arg( 'f', 'open', $archive_url ) ); ?>">Ouvert maintenant</a>
		<a class="filter-chip" href="<?php echo esc_url( $urgence_url ); ?>">Urgence</a>
		<a class="filter-chip" href="<?php echo esc_url( add_query_arg( 'f', 'accepting', $archive_url ) ); ?>">Nouveaux patients</a>
	</div>
</section>

<!-- BANDEAU STATISTIQUES -->
<section class="stats-bar">
	<div class="container stats-bar-inner">
		<div class="stat">
			<p class="stat-number acdq-mono"><?php echo esc_html( $total_cliniques ); ?><span>+</span></p>
			<p class="stat-label">Cliniques référencées</p>
		</div>
		<div class="stat">
			<p class="stat-number acdq-mono"><?php echo esc_html( $total_regions ); ?></p>
			<p class="stat-label">Régions du Québec couvertes</p>
		</div>
		<div class="stat">
			<p class="stat-number acdq-mono"><?php echo esc_html( $total_specialites ); ?></p>
			<p class="stat-label">Spécialités dentaires</p>
		</div>
	</div>
</section>

<!-- SECTION INFORMATIVE : COMMENT ÇA MARCHE -->
<section class="info-section">
	<div class="container">
		<h2>Comment fonctionne l'annuaire</h2>
		<p class="info-section-lead">Un outil simple pour trouver un dentiste près de chez vous, sans complications.</p>
		<div class="info-grid">
			<div class="info-card">
				<span class="info-card-icon">🔍</span>
				<h3>Cherchez par région ou spécialité</h3>
				<p>Parcourez les 17 régions administratives du Québec ou filtrez par spécialité — dentisterie générale, orthodontie, urgence, et plus.</p>
			</div>
			<div class="info-card">
				<span class="info-card-icon">📋</span>
				<h3>Consultez les fiches détaillées</h3>
				<p>Adresse, téléphone, heures d'ouverture et statut d'acceptation de nouveaux patients pour chaque clinique.</p>
			</div>
			<div class="info-card">
				<span class="info-card-icon">📞</span>
				<h3>Contactez la clinique directement</h3>
				<p>Appelez ou envoyez une demande de rendez-vous en un clic — vous êtes mis en contact directement, sans intermédiaire.</p>
			</div>
		</div>
	</div>
</section>

<!-- RÉGIONS -->
<section class="container region-section">
	<h2>Parcourir par région</h2>
	<div class="region-grid">
		<?php
		$regions = get_terms( array( 'taxonomy' => 'region', 'hide_empty' => false, 'orderby' => 'name' ) );
		if ( ! is_wp_error( $regions ) ) foreach ( $regions as $region ) : ?>
			<a class="region-tile" href="<?php echo esc_url( get_term_link( $region ) ); ?>">
				<span class="region-tile-icon" aria-hidden="true">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 22s7-7.2 7-12.5S16.4 2 12 2 5 4.6 5 9.5 12 22 12 22Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="9.5" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg>
				</span>
				<span class="region-tile-name"><?php echo esc_html( $region->name ); ?></span>
				<span class="region-tile-count acdq-mono"><?php echo esc_html( acdq_clinique_count_label( $region->count ) ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<!-- CARTE -->
<section class="container home-map-section">
	<h2>Explorez les cliniques sur la carte</h2>
	<div id="acdq-home-map" class="home-map"></div>
</section>

<!-- FAQ -->
<section class="faq-section">
	<div class="container">
		<h2>Questions fréquentes</h2>
		<div class="faq-list">
			<details class="faq-item">
				<summary>Comment trouver un bon dentiste au Québec?</summary>
				<p>Vérifiez que la clinique accepte de nouveaux patients, consultez ses heures d'ouverture et sa localisation par rapport à chez vous, et n'hésitez pas à l'appeler directement pour poser vos questions avant de prendre rendez-vous.</p>
			</details>
			<details class="faq-item">
				<summary>La RAMQ couvre-t-elle les soins dentaires au Québec?</summary>
				<p>La plupart des soins dentaires sont à la charge des résidents du Québec. La RAMQ offre toutefois une couverture pour certaines procédures chez les enfants de moins de 10 ans et dans certaines situations particulières. Renseignez-vous directement auprès de la clinique pour connaître les options applicables à votre situation.</p>
			</details>
			<details class="faq-item">
				<summary>Que faire en cas d'urgence dentaire?</summary>
				<p>Contactez d'abord votre clinique habituelle — plusieurs offrent des plages horaires d'urgence. Si elle n'est pas disponible, utilisez le filtre par spécialité pour trouver une clinique offrant des services d'urgence dentaire près de chez vous.</p>
			</details>
			<details class="faq-item">
				<summary>Comment savoir si une clinique accepte de nouveaux patients?</summary>
				<p>Chaque fiche clinique de notre annuaire indique clairement son statut d'acceptation de nouveaux patients, mis à jour régulièrement.</p>
			</details>
		</div>
	</div>
</section>

<!-- CLINIQUES RÉCENTES -->
<section class="container home-latest">
	<h2>Cliniques récemment ajoutées</h2>
	<div class="home-latest-carousel">
		<div class="home-latest-viewport" id="acdq-home-latest-viewport">
			<?php
			$latest = new WP_Query( array( 'post_type' => 'clinique', 'posts_per_page' => 8, 'orderby' => 'date', 'order' => 'DESC' ) );
			if ( $latest->have_posts() ) : while ( $latest->have_posts() ) : $latest->the_post();
				?>
				<div class="home-latest-slide"><?php get_template_part( 'template-parts/clinic-card' ); ?></div>
				<?php
			endwhile; wp_reset_postdata(); endif;
			?>
		</div>
		<?php if ( $latest->post_count > 1 ) : ?>
			<button type="button" class="home-latest-arrow home-latest-prev" aria-label="Cliniques précédentes">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M15 5 8 12l7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<button type="button" class="home-latest-arrow home-latest-next" aria-label="Cliniques suivantes">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		<?php endif; ?>
	</div>
	<p class="home-latest-cta">
		<a class="btn btn-ghost" href="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>">Voir toutes les cliniques →</a>
	</p>
</section>

<?php get_footer(); ?>