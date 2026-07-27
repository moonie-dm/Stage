<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * TASK 3 — Admin-only clinic description writer.
 *
 * Never exposed to public visitors: the meta box only registers for users
 * who can edit_posts, the enqueue only fires in wp-admin on the clinique
 * edit screen, and the AJAX handler itself re-checks the capability
 * server-side (the client-side gates are a UX nicety, not the security
 * boundary). The suggestion only ever replaces the in-browser editor
 * content when the admin clicks "Accepter" — it's never written to the
 * database until the admin then uses WordPress's own Publish/Update button,
 * so there's no path where this auto-saves anything.
 */

function acdq_ai_description_metabox() {
	if ( ! acdq_ai_enabled() || ! current_user_can( 'edit_posts' ) ) return;

	add_meta_box(
		'acdq_ai_description',
		"Améliorer avec l'IA",
		'acdq_ai_description_metabox_render',
		'clinique',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'acdq_ai_description_metabox' );

function acdq_ai_description_metabox_render( $post ) {
	?>
	<p class="description">Réécrit le texte actuel de l'éditeur en une description professionnelle de 2 à 3 phrases.</p>
	<button type="button" class="button button-primary" id="acdq-ai-improve-btn">Améliorer avec l'IA</button>

	<div id="acdq-ai-improve-result" style="display:none;margin-top:12px;">
		<p><strong>Suggestion :</strong></p>
		<textarea id="acdq-ai-improve-suggestion" rows="5" style="width:100%;" readonly></textarea>
		<p style="margin-top:8px;">
			<button type="button" class="button button-primary" id="acdq-ai-improve-accept">Accepter</button>
			<button type="button" class="button" id="acdq-ai-improve-reject">Rejeter</button>
		</p>
	</div>

	<p id="acdq-ai-improve-status" style="margin-top:8px;color:#666;"></p>
	<?php
}

function acdq_ai_improve_description() {
	check_ajax_referer( 'acdq_ai_improve', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Permission refusée.' ), 403 );
	}

	if ( ! acdq_ai_enabled() ) {
		wp_send_json_error( array( 'message' => "Ce service n'est pas configuré." ) );
	}

	if ( ! acdq_ai_rate_limit_ok( 'admin_description', 20 ) ) {
		wp_send_json_error( array( 'message' => 'Trop de requêtes — veuillez patienter une minute.' ) );
	}

	$draft = isset( $_POST['draft'] ) ? sanitize_textarea_field( wp_unslash( $_POST['draft'] ) ) : '';
	if ( '' === trim( $draft ) ) {
		wp_send_json_error( array( 'message' => "Le contenu de l'éditeur est vide." ) );
	}

	$system = "Tu réécris des textes bruts en descriptions professionnelles de cliniques dentaires pour un "
		. "annuaire québécois. Écris exactement 2 à 3 phrases en français, sur un ton professionnel et chaleureux, "
		. "sans markdown et sans guillemets autour du texte. N'invente aucune information absente du texte fourni "
		. "(pas de nouveaux services, horaires, ou coordonnées). Réponds uniquement avec la description finale, rien d'autre.";

	$result = acdq_call_claude(
		$system,
		array( array( 'role' => 'user', 'content' => $draft ) ),
		array( 'max_tokens' => 250 )
	);

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => 'Erreur lors de la génération. Réessayez.' ) );
	}

	wp_send_json_success( array( 'suggestion' => trim( $result ) ) );
}
add_action( 'wp_ajax_acdq_ai_improve_description', 'acdq_ai_improve_description' );
