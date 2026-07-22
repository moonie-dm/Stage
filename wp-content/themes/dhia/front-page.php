<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$total_cliniques   = wp_count_posts( 'clinique' )->publish;
$total_regions     = wp_count_terms( array( 'taxonomy' => 'region', 'hide_empty' => false ) );
$total_specialites = wp_count_terms( array( 'taxonomy' => 'specialite', 'hide_empty' => false ) );
?>

<!-- HERO + RECHERCHE -->
<section class="hero-search">
	<div class="container hero-search-inner">
		<span class="eyebrow"><?php echo esc_html( $total_cliniques ); ?> cliniques référencées au Québec</span>
		<h1>Trouvez un dentiste, région par région</h1>
		<form class="search-bar" action="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>" method="get">
			<input type="search" name="s" placeholder="Nom, ville ou spécialité">
			<button type="submit" class="btn btn-primary">Rechercher</button>
		</form>
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
				<span class="region-tile-name"><?php echo esc_html( $region->name ); ?></span>
				<span class="region-tile-count acdq-mono"><?php echo esc_html( $region->count ); ?> cliniques</span>
			</a>
		<?php endforeach; ?>
	</div>
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
	<div class="clinic-grid">
		<?php
		$latest = new WP_Query( array( 'post_type' => 'clinique', 'posts_per_page' => 3, 'orderby' => 'date', 'order' => 'DESC' ) );
		if ( $latest->have_posts() ) : while ( $latest->have_posts() ) : $latest->the_post();
			get_template_part( 'template-parts/clinic-card' );
		endwhile; wp_reset_postdata(); endif;
		?>
	</div>
	<p class="home-latest-cta">
		<a class="btn btn-ghost" href="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>">Voir toutes les cliniques →</a>
	</p>
</section>

<?php get_footer(); ?>