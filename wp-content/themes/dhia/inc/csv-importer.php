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
		'titre', 'description', 'adresse', 'ville', 'code_postal', 'telephone', 'courriel',
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
		'Clinique Dentaire Exemple', 'Une courte description de la clinique.', '123 rue Principale', 'Québec', 'G1V 0A6',
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

		<p>Téléchargez d'abord le modèle, remplissez-le avec vos données (Excel, Google Sheets, etc.), exportez-le en <strong>CSV</strong>, puis téléversez-le ci-dessous.</p>

		<p><a href="<?php echo esc_url( add_query_arg( 'acdq_download_template', '1' ) ); ?>" class="button">⬇ Télécharger le modèle CSV</a></p>

		<?php if ( $results ) : ?>
			<div class="notice notice-<?php echo $results['errors'] ? 'warning' : 'success'; ?>">
				<p>
					<strong><?php echo esc_html( $results['created'] ); ?></strong> clinique(s) créée(s).
					<?php if ( $results['skipped'] ) : ?> <strong><?php echo esc_html( $results['skipped'] ); ?></strong> ligne(s) ignorée(s) (titre déjà existant).<?php endif; ?>
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
			</table>
			<?php submit_button( 'Importer les cliniques' ); ?>
		</form>

		<h2>Format attendu</h2>
		<p>Colonnes (dans cet ordre, en-têtes exacts) :</p>
		<code style="display:block;padding:10px;background:#f0f0f1;overflow-x:auto;white-space:pre;"><?php echo esc_html( implode( ', ', acdq_csv_columns() ) ); ?></code>
		<ul style="margin-top:14px;list-style:disc;margin-left:20px;">
			<li><strong>accepte_nouveaux_patients</strong> : écrire <code>oui</code> ou <code>non</code></li>
			<li><strong>region</strong> : un seul nom de région, doit correspondre exactement à un terme existant (ex. <code>Montréal</code>)</li>
			<li><strong>specialites</strong> : une ou plusieurs spécialités séparées par <code> | </code> (ex. <code>Orthodontie | Urgence dentaire</code>)</li>
			<li>Les colonnes vides sont acceptées — vous pourrez compléter les fiches plus tard.</li>
		</ul>
	</div>
	<?php
}

function acdq_process_csv_import() {
	$created = 0;
	$skipped = 0;
	$errors  = array();

	if ( empty( $_FILES['acdq_csv_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['acdq_csv_file']['tmp_name'] ) ) {
		$errors[] = 'Aucun fichier reçu.';
		return compact( 'created', 'skipped', 'errors' );
	}

	$handle = fopen( $_FILES['acdq_csv_file']['tmp_name'], 'r' );
	if ( ! $handle ) {
		$errors[] = 'Impossible de lire le fichier.';
		return compact( 'created', 'skipped', 'errors' );
	}

	$header = fgetcsv( $handle );
	if ( ! $header ) {
		$errors[] = 'Fichier vide ou invalide.';
		fclose( $handle );
		return compact( 'created', 'skipped', 'errors' );
	}
	// Strip UTF-8 BOM from the first header cell if present.
	$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );
	$header = array_map( 'trim', $header );

	$row_num = 1;
	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$row_num++;
		if ( count( array_filter( $row, function ( $v ) { return trim( $v ) !== ''; } ) ) === 0 ) continue; // skip fully blank rows

		$data = array();
		foreach ( $header as $i => $col ) $data[ $col ] = isset( $row[ $i ] ) ? trim( $row[ $i ] ) : '';

		if ( empty( $data['titre'] ) ) {
			$errors[] = "Ligne $row_num : titre manquant, ignorée.";
			continue;
		}
		if ( post_exists( $data['titre'], '', '', 'clinique' ) ) {
			$skipped++;
			continue;
		}

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

		// ACF text/email/url fields — works whether or not ACF is active (falls back to plain post meta).
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
			if ( $term ) {
				wp_set_object_terms( $post_id, (int) $term->term_id, 'region' );
			} else {
				$errors[] = "Ligne $row_num : région \"{$data['region']}\" introuvable, ignorée pour cette clinique.";
			}
		}

		if ( ! empty( $data['specialites'] ) ) {
			$names = array_map( 'trim', explode( '|', $data['specialites'] ) );
			$term_ids = array();
			foreach ( $names as $name ) {
				if ( $name === '' ) continue;
				$term = get_term_by( 'name', $name, 'specialite' );
				if ( $term ) {
					$term_ids[] = (int) $term->term_id;
				} else {
					$errors[] = "Ligne $row_num : spécialité \"$name\" introuvable, ignorée.";
				}
			}
			if ( $term_ids ) wp_set_object_terms( $post_id, $term_ids, 'specialite' );
		}

		$created++;
	}

	fclose( $handle );
	return compact( 'created', 'skipped', 'errors' );
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