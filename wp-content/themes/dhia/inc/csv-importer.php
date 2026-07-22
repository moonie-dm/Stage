<?php
/**
 * Simple, free CSV bulk importer for the "Clinique" post type.
 * Adds an admin page: Cliniques → Importer CSV
 *
 * Expects a CSV with a header row using these exact column names:
 * titre,description,adresse,ville,code_postal,telephone,courriel,site_web,
 * courriel_rdv,accepte_nouveaux_patients,latitude,longitude,region,specialites,
 * heures_lundi,heures_mardi,heures_mercredi,heures_jeudi,heures_vendredi,heures_samedi,heures_dimanche
 *
 * - accepte_nouveaux_patients: "oui" or "non"
 * - region: a single region name (must match one of your Région terms, e.g. "Montréal")
 * - specialites: one or more specialty names separated by " | " (pipe), e.g. "Orthodontie | Urgence dentaire"
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function acdq_import_menu() {
	add_submenu_page(
		'edit.php?post_type=clinique',
		'Importer des cliniques (CSV)',
		'Importer CSV',
		'manage_options',
		'acdq-csv-import',
		'acdq_render_import_page'
	);
}
add_action( 'admin_menu', 'acdq_import_menu' );

function acdq_csv_columns() {
	return array(
		'titre', 'image_url', 'description', 'adresse', 'ville', 'code_postal', 'telephone', 'courriel',
		'site_web', 'courriel_rdv', 'accepte_nouveaux_patients', 'latitude', 'longitude',
		'region', 'specialites',
		'heures_lundi', 'heures_mardi', 'heures_mercredi', 'heures_jeudi',
		'heures_vendredi', 'heures_samedi', 'heures_dimanche',
	);
}

/**
 * Serve a blank CSV template for download.
 */
function acdq_maybe_serve_template() {
	if ( ! isset( $_GET['acdq_download_template'] ) || ! current_user_can( 'manage_options' ) ) return;

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="modele-cliniques.csv"' );

	$out = fopen( 'php://output', 'w' );
	fputs( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM so accents display correctly in Excel
	fputcsv( $out, acdq_csv_columns() );
	fputcsv( $out, array(
	'Clinique Dentaire Exemple', 'https://exemple.ca/photo-clinique.jpg', 'Une courte description de la clinique.', '123 rue Principale', 'Québec', 'G1V 0A6',
	'418 555-0123', 'info@exemple.ca', 'https://exemple.ca', 'rdv@exemple.ca', 'oui', '46.8139', '-71.2080',
	'Capitale-Nationale', 'Dentisterie générale | Urgence dentaire',
	'8h00 - 17h00', '8h00 - 17h00', '8h00 - 17h00', '8h00 - 19h00', '8h00 - 16h00', 'Fermé', 'Fermé',
) );
	fclose( $out );
	exit;
}
add_action( 'admin_init', 'acdq_maybe_serve_template' );

function acdq_render_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$results = null;

	if ( isset( $_POST['acdq_import_nonce'] ) && wp_verify_nonce( $_POST['acdq_import_nonce'], 'acdq_import' ) ) {
		$results = acdq_process_csv_import();
	}
	?>
	<div class="wrap">
		<h1>Importer des cliniques (CSV)</h1>

		<p>Téléchargez d'abord le modèle, remplissez-le avec vos données, exportez-le en <strong>CSV</strong>, puis téléversez-le ci-dessous.</p>

		<p><a href="<?php echo esc_url( add_query_arg( 'acdq_download_template', '1' ) ); ?>" class="button">⬇ Télécharger le modèle CSV</a></p>

		<?php if ( $results ) : ?>
			<div class="notice notice-<?php echo $results['errors'] ? 'warning' : 'success'; ?>">
				<p>
					<strong><?php echo esc_html( $results['created'] ); ?></strong> clinique(s) créée(s).
					<strong><?php echo esc_html( $results['updated'] ); ?></strong> clinique(s) mise(s) à jour.
					<?php if ( $results['skipped'] ) : ?> <strong><?php echo esc_html( $results['skipped'] ); ?></strong> ligne(s) ignorée(s).<?php endif; ?>
				</p>
				<?php if ( $results['errors'] ) : ?>
					<ul style="margin-left:20px;list-style:disc;">
						<?php foreach ( $results['errors'] as $err ) echo '<li>' . esc_html( $err ) . '</li>'; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'acdq_import', 'acdq_import_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="acdq_csv_file">Fichier CSV</label></th>
					<td><input type="file" name="acdq_csv_file" id="acdq_csv_file" accept=".csv" required></td>
				</tr>
				<tr>
					<th scope="row">Mode</th>
					<td>
						<label>
							<input type="checkbox" name="acdq_update_existing" value="1">
							Mettre à jour les cliniques existantes (identifiées par titre exact) au lieu de les ignorer
						</label>
						<p class="description">Utile pour réimporter le même fichier après avoir ajouté des coordonnées GPS ou d'autres données manquantes.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Importer les cliniques' ); ?>
		</form>

		<h2>Format attendu</h2>
		<p>Colonnes (dans cet ordre, en-têtes exacts) :</p>
		<code style="display:block;padding:10px;background:#f0f0f1;overflow-x:auto;white-space:pre;"><?php echo esc_html( implode( ', ', acdq_csv_columns() ) ); ?></code>
	</div>
	<?php
}

function acdq_process_csv_import() {
	$created = 0;
	$updated = 0;
	$skipped = 0;
	$errors  = array();
	$update_mode = isset( $_POST['acdq_update_existing'] );

	if ( empty( $_FILES['acdq_csv_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['acdq_csv_file']['tmp_name'] ) ) {
		$errors[] = 'Aucun fichier reçu.';
		return compact( 'created', 'updated', 'skipped', 'errors' );
	}

	$handle = fopen( $_FILES['acdq_csv_file']['tmp_name'], 'r' );
	if ( ! $handle ) {
		$errors[] = 'Impossible de lire le fichier.';
		return compact( 'created', 'updated', 'skipped', 'errors' );
	}

	$header = fgetcsv( $handle );
	if ( ! $header ) {
		$errors[] = 'Fichier vide ou invalide.';
		fclose( $handle );
		return compact( 'created', 'updated', 'skipped', 'errors' );
	}
	$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );
	$header = array_map( 'trim', $header );

	$row_num = 1;
	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$row_num++;
		if ( count( array_filter( $row, function ( $v ) { return trim( $v ) !== ''; } ) ) === 0 ) continue;

		$data = array();
		foreach ( $header as $i => $col ) $data[ $col ] = isset( $row[ $i ] ) ? trim( $row[ $i ] ) : '';

		if ( empty( $data['titre'] ) ) {
			$errors[] = "Ligne $row_num : titre manquant, ignorée.";
			continue;
		}

		$existing_id = post_exists( $data['titre'], '', '', 'clinique' );
		$post_id = null;

		if ( $existing_id ) {
			if ( ! $update_mode ) {
				$skipped++;
				continue;
			}
			$post_id = $existing_id;
			wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
			) );
			$updated++;
		} else {
			$post_id = wp_insert_post( array(
				'post_type'    => 'clinique',
				'post_title'   => sanitize_text_field( $data['titre'] ),
				'post_content' => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
				'post_status'  => 'publish',
			), true );

			if ( is_wp_error( $post_id ) ) {
				$errors[] = "Ligne $row_num : " . $post_id->get_error_message();
				continue;
			}
			$created++;
		}

		// Image (only sideload for new posts without an existing thumbnail, to avoid re-downloading on every update)
		if ( ! empty( $data['image_url'] ) && filter_var( $data['image_url'], FILTER_VALIDATE_URL ) && ! has_post_thumbnail( $post_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attachment_id = media_sideload_image( esc_url_raw( $data['image_url'] ), $post_id, sanitize_text_field( $data['titre'] ), 'id' );
			if ( ! is_wp_error( $attachment_id ) ) set_post_thumbnail( $post_id, $attachment_id );
		}

		$field_map = array(
			'adresse' => 'adresse', 'ville' => 'ville', 'code_postal' => 'code_postal',
			'telephone' => 'telephone', 'courriel' => 'courriel', 'site_web' => 'site_web',
			'courriel_rdv' => 'courriel_rdv', 'latitude' => 'latitude', 'longitude' => 'longitude',
			'heures_lundi' => 'heures_lundi', 'heures_mardi' => 'heures_mardi', 'heures_mercredi' => 'heures_mercredi',
			'heures_jeudi' => 'heures_jeudi', 'heures_vendredi' => 'heures_vendredi',
			'heures_samedi' => 'heures_samedi', 'heures_dimanche' => 'heures_dimanche',
		);
		foreach ( $field_map as $csv_col => $acf_field ) {
			if ( isset( $data[ $csv_col ] ) && $data[ $csv_col ] !== '' ) {
				acdq_set_field( $acf_field, sanitize_text_field( $data[ $csv_col ] ), $post_id );
			}
		}

		if ( isset( $data['accepte_nouveaux_patients'] ) ) {
			$val = mb_strtolower( trim( $data['accepte_nouveaux_patients'] ) );
			acdq_set_field( 'accepte_nouveaux_patients', in_array( $val, array( 'oui', 'yes', '1', 'true' ), true ) ? 1 : 0, $post_id );
		}

		if ( ! empty( $data['region'] ) ) {
			$term = get_term_by( 'name', $data['region'], 'region' );
			if ( $term ) wp_set_object_terms( $post_id, (int) $term->term_id, 'region' );
		}

		if ( ! empty( $data['specialites'] ) ) {
			$names = array_map( 'trim', explode( '|', $data['specialites'] ) );
			$term_ids = array();
			foreach ( $names as $name ) {
				if ( $name === '' ) continue;
				$term = get_term_by( 'name', $name, 'specialite' );
				if ( $term ) $term_ids[] = (int) $term->term_id;
			}
			if ( $term_ids ) wp_set_object_terms( $post_id, $term_ids, 'specialite' );
		}
	}

	fclose( $handle );
	return compact( 'created', 'updated', 'skipped', 'errors' );
}

/**
 * Set a field value using ACF's function if available, otherwise plain post meta.
 * This keeps the importer working even if ACF is deactivated.
 */
function acdq_set_field( $field_name, $value, $post_id ) {
	if ( function_exists( 'update_field' ) ) {
		update_field( $field_name, $value, $post_id );
	} else {
		update_post_meta( $post_id, $field_name, $value );
	}
}