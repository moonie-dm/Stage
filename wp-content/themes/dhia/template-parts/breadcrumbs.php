<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<p class="breadcrumbs">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
	<span aria-hidden="true">/</span>
	<a href="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>">Annuaire</a>
	<?php if ( is_tax( 'region' ) ) : ?>
		<span aria-hidden="true">/</span>
		<span><?php single_term_title(); ?></span>
	<?php elseif ( is_singular( 'clinique' ) ) :
		$regions = get_the_terms( get_the_ID(), 'region' );
		if ( $regions && ! is_wp_error( $regions ) ) : ?>
			<span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( get_term_link( $regions[0] ) ); ?>"><?php echo esc_html( $regions[0]->name ); ?></a>
		<?php endif; ?>
		<span aria-hidden="true">/</span>
		<span><?php the_title(); ?></span>
	<?php endif; ?>
</p>