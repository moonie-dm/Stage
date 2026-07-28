<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shared plumbing for the three AI features (smart search, FAQ chatbot, admin
 * description writer): the API key gate, a per-IP rate limiter, and the single
 * function that actually talks to the Claude API. Nothing in here is
 * feature-specific — inc/ai-search.php, inc/ai-chatbot.php and
 * inc/ai-admin-description.php all call acdq_call_claude() and
 * acdq_ai_rate_limit_ok() rather than duplicating this logic.
 *
 * Model: Claude Haiku 4.5 (claude-haiku-4-5). Every call this theme makes is a
 * short, single-turn, bounded task — map free text to one of our existing
 * specialty taxonomy terms, answer a handful of fixed site-usage/FAQ questions,
 * or rewrite a paragraph into 2-3 polished sentences. None of that needs deep
 * multi-step reasoning, so the fastest/cheapest current model is the right
 * fit for a directory site's classification and rewriting calls rather than
 * paying frontier-model latency and cost for them.
 *
 * HTTP, not the Anthropic PHP SDK: this theme has no Composer/vendor setup —
 * composer.json here only lists dev-time lint tooling (phpcs, parallel-lint),
 * no runtime dependencies — and WordPress themes are conventionally
 * distributed without a build/install step. wp_remote_post() against the
 * Messages API directly is the standard way WordPress themes and plugins call
 * third-party HTTP APIs in that environment, so that's what's used here
 * instead of pulling in a Composer-managed SDK for three small requests.
 */
define( 'ACDQ_AI_MODEL', 'claude-haiku-4-5' );

/**
 * The Anthropic API key, preferring the wp-config.php constant
 * (ACDQ_ANTHROPIC_KEY) when it's defined — never in the database or
 * front-end JS, the safest option — and falling back to the
 * `acdq_anthropic_key` option (see inc/ai-settings.php) for sites where
 * whoever's setting this up only has wp-admin access, not the filesystem.
 * The constant always wins when both are set, so a site can move to the
 * safer storage later just by defining the constant.
 */
function acdq_ai_get_key() {
	if ( defined( 'ACDQ_ANTHROPIC_KEY' ) && ACDQ_ANTHROPIC_KEY ) {
		return ACDQ_ANTHROPIC_KEY;
	}
	$key = get_option( 'acdq_anthropic_key', '' );
	return is_string( $key ) ? trim( $key ) : '';
}

/**
 * True once an API key is available from either source above. Every AI
 * feature checks this before registering its AJAX handler or enqueueing its
 * JS, so the whole feature set is a no-op — not an error — on a site that
 * hasn't configured a key yet.
 */
function acdq_ai_enabled() {
	return '' !== acdq_ai_get_key();
}

/**
 * Best-effort visitor IP for rate-limiting purposes only (not used for
 * security decisions, so spoofable headers are an acceptable trade-off here).
 */
function acdq_ai_get_ip() {
	$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
	foreach ( $headers as $header ) {
		if ( empty( $_SERVER[ $header ] ) ) continue;
		$raw = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
		$ip  = trim( explode( ',', $raw )[0] );
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
	}
	return '0.0.0.0';
}

/**
 * Per-IP-per-action rate limit backed by a transient. Returns true (and counts
 * the request) if the caller is still under the cap, false if they should be
 * turned away. Shared by every AI endpoint so one visitor can't spam any of
 * them into running up API costs.
 */
function acdq_ai_rate_limit_ok( $action, $max_per_minute = 6 ) {
	$key   = 'acdq_ai_rl_' . $action . '_' . md5( acdq_ai_get_ip() );
	$count = (int) get_transient( $key );

	if ( $count >= $max_per_minute ) {
		return false;
	}

	set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
	return true;
}

/**
 * Call the Claude Messages API with a system prompt and a message list.
 *
 * $args:
 *   max_tokens (int)   — default 300, these are all short responses.
 *   schema (array|null) — when set, requests structured JSON output
 *                          constrained to this JSON Schema (supported on
 *                          Haiku 4.5). Use for anything that must parse
 *                          cleanly, e.g. the search classifier.
 *
 * Returns the response text (a JSON string when $schema was set) on success,
 * or a WP_Error on any failure — never exposes the raw API error to callers
 * that might echo it to a visitor. Every failure path is logged via
 * error_log() for debugging.
 */
function acdq_call_claude( $system_prompt, $messages, $args = array() ) {
	if ( ! acdq_ai_enabled() ) {
		return new WP_Error( 'acdq_ai_disabled', 'AI features are not configured.' );
	}

	$args = wp_parse_args( $args, array(
		'max_tokens' => 300,
		'schema'     => null,
	) );

	$body = array(
		'model'      => ACDQ_AI_MODEL,
		'max_tokens' => (int) $args['max_tokens'],
		'system'     => $system_prompt,
		'messages'   => $messages,
	);

	if ( $args['schema'] ) {
		$body['output_config'] = array(
			'format' => array(
				'type'   => 'json_schema',
				'schema' => $args['schema'],
			),
		);
	}

	$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
		'timeout' => 12,
		'headers' => array(
			'content-type'      => 'application/json',
			'x-api-key'         => acdq_ai_get_key(),
			'anthropic-version' => '2023-06-01',
		),
		'body' => wp_json_encode( $body ),
	) );

	if ( is_wp_error( $response ) ) {
		error_log( 'ACDQ AI: request failed — ' . $response->get_error_message() );
		return new WP_Error( 'acdq_ai_request_failed', 'AI request failed.' );
	}

	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code || ! is_array( $data ) ) {
		error_log( 'ACDQ AI: API returned HTTP ' . $code . ' — ' . wp_remote_retrieve_body( $response ) );
		return new WP_Error( 'acdq_ai_api_error', 'AI service error.' );
	}

	if ( isset( $data['stop_reason'] ) && 'refusal' === $data['stop_reason'] ) {
		error_log( 'ACDQ AI: model refused the request' );
		return new WP_Error( 'acdq_ai_refusal', 'AI declined to respond.' );
	}

	$text = '';
	foreach ( (array) ( $data['content'] ?? array() ) as $block ) {
		if ( isset( $block['type'], $block['text'] ) && 'text' === $block['type'] ) {
			$text .= $block['text'];
		}
	}

	if ( '' === $text ) {
		error_log( 'ACDQ AI: empty response content — ' . wp_json_encode( $data ) );
		return new WP_Error( 'acdq_ai_empty', 'AI returned no content.' );
	}

	return $text;
}
