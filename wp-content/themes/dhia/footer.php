<footer class="site-footer">
	<div class="container footer-grid">
		<div class="footer-col">
			<p class="footer-brand"><?php bloginfo( 'name' ); ?></p>
			<p>L'annuaire des cliniques dentaires du Québec.</p>
		</div>
		<div class="footer-col">
			<p class="footer-heading">Régions populaires</p>
			<?php
			$top_regions = get_terms( array( 'taxonomy' => 'region', 'number' => 5, 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => false ) );
			if ( ! is_wp_error( $top_regions ) ) foreach ( $top_regions as $t ) :
				echo '<a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a>';
			endforeach;
			?>
		</div>
		<div class="footer-col">
			<p class="footer-heading">Spécialités</p>
			<?php
			$specialites = get_terms( array( 'taxonomy' => 'specialite', 'number' => 5, 'hide_empty' => false ) );
			if ( ! is_wp_error( $specialites ) ) foreach ( $specialites as $s ) :
				echo '<a href="' . esc_url( get_term_link( $s ) ) . '">' . esc_html( $s->name ) . '</a>';
			endforeach;
			?>
		</div>
		<div class="footer-col">
			<p class="footer-heading">Cliniques</p>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'clinique' ) ); ?>">Voir l'annuaire</a>
		</div>
	</div>
	<div class="container footer-bottom">
		<p>&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>