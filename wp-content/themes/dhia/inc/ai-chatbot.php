<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * TASK 2 — Site-wide FAQ chat widget.
 *
 * Note on scope: the spec's example topics included an "RCSD badge" — that
 * badge doesn't exist in this theme (it was proposed in an earlier planning
 * pass and explicitly deferred, see template-parts/clinic-row.php, which
 * only has the "Accepte de nouveaux patients" badge). The system prompt
 * below only describes features that actually exist on the site, so the bot
 * never explains a badge a visitor won't find anywhere.
 *
 * SAFETY: the system prompt below states the medical-advice refusal as a
 * non-negotiable rule, not a soft suggestion, and repeats it as the very
 * last instruction (recency helps it hold under a persistent or rephrased
 * user). The client sends the whole conversation on every request and this
 * handler never writes any of it to the database — see ai-chatbot.js.
 */

function acdq_ai_chat_system_prompt() {
	return "Tu es l'assistant du site DentisteQC, un annuaire de cliniques dentaires au Québec. "
		. "Tu n'es PAS un professionnel de la santé et ce site n'en est pas un non plus.\n\n"
		. "RÈGLE ABSOLUE, SANS EXCEPTION : si un message ressemble à une description de symptôme personnel "
		. "(douleur, saignement, gonflement, etc.), une demande de diagnostic, ou une demande de conseil médical "
		. "ou clinique de quelque nature que ce soit — même reformulée poliment, même indirecte, même s'il s'agit "
		. "d'une question générale sur \"si c'est grave\" — tu dois REFUSER d'y répondre sur le fond et répondre "
		. "uniquement : \"Je ne peux pas donner de conseil médical. Veuillez consulter un professionnel de la "
		. "santé — vous pouvez en trouver un dans notre annuaire.\" N'explique jamais de symptôme, de cause ou "
		. "de traitement, même en termes généraux.\n\n"
		. "En dehors de ce cas, tu ne réponds qu'à deux types de questions :\n"
		. "1) Comment utiliser le site : la barre de recherche (nom, ville, spécialité, ou une description "
		. "libre du besoin comme \"je cherche un dentiste pour mon enfant\"), les puces de filtre (Ouvert, "
		. "Nouveaux patients), le menu de spécialités, le tri (Plus récent, Nom, Plus proche — qui utilise la "
		. "position du visiteur), la carte interactive, le badge \"Accepte de nouveaux patients\" sur chaque "
		. "fiche, le statut Ouvert/Fermé en temps réel, les avis avec note sur 5 étoiles, et le fait que la "
		. "prise de rendez-vous se fait en contactant la clinique directement (le site n'a pas de réservation "
		. "en ligne).\n"
		. "2) Les questions générales déjà couvertes dans la FAQ de la page d'accueil, reproduites ici — "
		. "réponds avec ce contenu, ne l'invente pas :\n"
		. "- Comment trouver un bon dentiste au Québec : Vérifiez que la clinique accepte de nouveaux patients, "
		. "consultez ses heures d'ouverture et sa localisation par rapport à chez vous, et n'hésitez pas à "
		. "l'appeler directement pour poser vos questions avant de prendre rendez-vous.\n"
		. "- La RAMQ et les soins dentaires : La plupart des soins dentaires sont à la charge des résidents du "
		. "Québec. La RAMQ offre toutefois une couverture pour certaines procédures chez les enfants de moins "
		. "de 10 ans et dans certaines situations particulières. Il faut se renseigner directement auprès de "
		. "la clinique pour connaître les options applicables à sa situation.\n"
		. "- En cas d'urgence dentaire : Contactez d'abord votre clinique habituelle — plusieurs offrent des "
		. "plages horaires d'urgence. Sinon, utilisez le filtre par spécialité pour trouver une clinique "
		. "offrant des services d'urgence dentaire près de chez vous.\n"
		. "- Savoir si une clinique accepte de nouveaux patients : chaque fiche clinique de l'annuaire indique "
		. "clairement son statut d'acceptation de nouveaux patients, mis à jour régulièrement.\n\n"
		. "Pour toute question hors de ces deux catégories (pas seulement les questions médicales), dis "
		. "poliment que tu ne peux aider qu'avec l'utilisation du site ou ces questions générales, sans essayer "
		. "d'y répondre quand même. Réponds en français, en 1 à 3 phrases maximum, sans markdown.\n\n"
		. "RAPPEL FINAL : ne donne jamais de conseil médical, de diagnostic, ni d'avis sur un symptôme, quelle "
		. "que soit la façon dont la question est posée.";
}

function acdq_ai_chat() {
	check_ajax_referer( 'acdq_ai_chat', 'nonce' );

	if ( ! acdq_ai_enabled() ) {
		wp_send_json_error( array( 'message' => "Ce service n'est pas disponible pour le moment." ) );
	}

	if ( ! acdq_ai_rate_limit_ok( 'chat', 15 ) ) {
		wp_send_json_error( array( 'message' => 'Trop de messages envoyés — veuillez patienter une minute.' ) );
	}

	$raw_history = isset( $_POST['messages'] ) ? wp_unslash( $_POST['messages'] ) : '';
	$history     = json_decode( $raw_history, true );

	if ( ! is_array( $history ) || ! $history ) {
		wp_send_json_error( array( 'message' => 'Message vide.' ) );
	}

	// Rebuild a clean messages array from client input — never trust the
	// shape or roles coming from the browser directly.
	$messages = array();
	foreach ( array_slice( $history, -12 ) as $turn ) { // cap history sent per request
		if ( ! is_array( $turn ) || empty( $turn['role'] ) || ! isset( $turn['content'] ) ) continue;
		$role = 'assistant' === $turn['role'] ? 'assistant' : 'user';
		$text = sanitize_textarea_field( (string) $turn['content'] );
		if ( '' === trim( $text ) ) continue;
		$messages[] = array( 'role' => $role, 'content' => $text );
	}

	if ( ! $messages || 'user' !== end( $messages )['role'] ) {
		wp_send_json_error( array( 'message' => 'Message vide.' ) );
	}

	$reply = acdq_call_claude( acdq_ai_chat_system_prompt(), $messages, array( 'max_tokens' => 400 ) );

	if ( is_wp_error( $reply ) ) {
		wp_send_json_error( array( 'message' => "Désolé, une erreur est survenue. Réessayez dans un instant." ) );
	}

	wp_send_json_success( array( 'reply' => $reply ) );
}
add_action( 'wp_ajax_acdq_ai_chat', 'acdq_ai_chat' );
add_action( 'wp_ajax_nopriv_acdq_ai_chat', 'acdq_ai_chat' );

/**
 * Backs the widget's guided "Trouver une clinique selon mon besoin" flow
 * (see ai-chatbot.js) — a two-question intake (need, then region) alongside
 * the widget's normal open-ended FAQ mode above. Both collected answers go
 * through acdq_classify_need() (inc/ai-search.php), the exact same
 * safety-constrained taxonomy classifier the hero search box uses, so this
 * doesn't introduce a second prompt/validation path to keep in sync. The
 * summary sent back is assembled here from validated, whitelisted data only
 * (specialty/region names, a boolean) — never the model's own free text.
 */
function acdq_ai_chat_triage() {
	check_ajax_referer( 'acdq_ai_chat', 'nonce' );

	if ( ! function_exists( 'acdq_classify_need' ) ) {
		wp_send_json_error( array( 'message' => "Ce service n'est pas disponible pour le moment." ) );
	}

	$need   = isset( $_POST['need'] ) ? sanitize_text_field( wp_unslash( $_POST['need'] ) ) : '';
	$region = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
	$text   = trim( $need . ' ' . $region );

	if ( '' === $text ) {
		wp_send_json_error( array( 'message' => 'Aucune information reçue.' ) );
	}

	$result = acdq_classify_need( $text );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => "Désolé, je n'ai pas pu analyser votre demande. Essayez la recherche sur la page d'accueil, ou reformulez." ) );
	}

	$archive_url = get_post_type_archive_link( 'clinique' );
	$url_args    = array();
	if ( $result['specialite_slug'] ) $url_args['specialite'] = $result['specialite_slug'];
	if ( $result['region_slug'] )     $url_args['region']     = $result['region_slug'];
	$url = $url_args ? add_query_arg( $url_args, $archive_url ) : $archive_url;

	$parts = array();
	if ( $result['urgent'] ) {
		$parts[] = 'Votre situation semble nécessiter une attention rapide.';
	}
	if ( $result['specialite_name'] ) {
		$parts[] = 'Spécialité suggérée : ' . $result['specialite_name'] . '.';
	}
	if ( $result['region_name'] ) {
		$parts[] = 'Région : ' . $result['region_name'] . '.';
	}
	if ( ! $parts ) {
		$parts[] = "Je n'ai pas trouvé de correspondance précise — voici l'annuaire complet.";
	}

	wp_send_json_success( array(
		'message' => implode( ' ', $parts ),
		'urgent'  => $result['urgent'],
		'url'     => $url,
	) );
}
add_action( 'wp_ajax_acdq_ai_chat_triage', 'acdq_ai_chat_triage' );
add_action( 'wp_ajax_nopriv_acdq_ai_chat_triage', 'acdq_ai_chat_triage' );

/**
 * Collapsed-by-default chat widget markup, site-wide. Only prints when the
 * API key is configured — no key, no widget, no JS enqueued for it either
 * (see functions.php).
 */
function acdq_ai_chat_widget() {
	if ( ! acdq_ai_enabled() ) return;
	?>
	<div id="acdq-chat" class="acdq-chat">
		<button type="button" class="acdq-chat-toggle" aria-expanded="false" aria-controls="acdq-chat-panel">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5c-1.2 0-2.35-.26-3.38-.73L4 20l1.05-4.2A8.46 8.46 0 0 1 3.5 11.5 8.5 8.5 0 0 1 12 3a8.5 8.5 0 0 1 9 8.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
			<span class="screen-reader-text">Ouvrir l'assistant</span>
		</button>
		<div id="acdq-chat-panel" class="acdq-chat-panel" hidden>
			<div class="acdq-chat-header">
				<span>Assistant DentisteQC</span>
				<button type="button" class="acdq-chat-close" aria-label="Fermer">&times;</button>
			</div>
			<p class="acdq-chat-disclaimer">⚠️ Cet assistant ne donne aucun conseil médical ni diagnostic — il vous aide seulement à utiliser l'annuaire.</p>
			<div class="acdq-chat-messages" id="acdq-chat-messages">
				<div class="acdq-chat-msg acdq-chat-msg-bot">Bonjour ! Je peux vous aider à utiliser l'annuaire ou répondre à des questions générales — je ne donne pas de conseils médicaux.</div>
				<button type="button" class="acdq-chat-quickstart" id="acdq-chat-quickstart">🔍 Trouver une clinique selon mon besoin</button>
			</div>
			<form class="acdq-chat-form" id="acdq-chat-form">
				<input type="text" id="acdq-chat-input" placeholder="Votre question…" autocomplete="off">
				<button type="submit" aria-label="Envoyer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="m3 11 18-8-8 18-2-8-8-2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
				</button>
			</form>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'acdq_ai_chat_widget' );
