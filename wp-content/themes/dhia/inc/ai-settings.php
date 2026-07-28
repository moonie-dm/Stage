<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Fallback API-key storage for sites where whoever's configuring this has
 * wp-admin access but not the server filesystem (so wp-config.php isn't
 * reachable). Stores the key as a WordPress option instead of a constant.
 *
 * This is a deliberately weaker security posture than ACDQ_ANTHROPIC_KEY in
 * wp-config.php — anyone with database access, or a compromised plugin that
 * can read options, can read this key, where a wp-config.php constant can't
 * be reached that way. inc/ai-helpers.php::acdq_ai_get_key() always prefers
 * the constant when one is defined, so a site can move to the safer storage
 * later without touching this file. Still: manage_options-gated, never
 * rendered back into the field once saved, never sent to the front end.
 */

function acdq_ai_settings_menu() {
	add_options_page(
		"Clé API IA (Anthropic)",
		'DentisteQC IA',
		'manage_options',
		'acdq-ai-settings',
		'acdq_ai_settings_page_render'
	);
}
add_action( 'admin_menu', 'acdq_ai_settings_menu' );

function acdq_ai_settings_register() {
	register_setting( 'acdq_ai_settings_group', 'acdq_anthropic_key', array(
		'type'              => 'string',
		'sanitize_callback' => 'acdq_ai_sanitize_key',
		'default'           => '',
	) );
}
add_action( 'admin_init', 'acdq_ai_settings_register' );

/**
 * Blank submit = "leave the stored key unchanged" (the field is never
 * pre-filled with the real key, so a blank submit is what happens if the
 * admin just clicks Save without touching it) — otherwise a stray save
 * would silently wipe a working key. The explicit "Supprimer" checkbox is
 * the only way to actually clear it.
 */
function acdq_ai_sanitize_key( $value ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return get_option( 'acdq_anthropic_key', '' );
	}
	if ( ! empty( $_POST['acdq_anthropic_key_clear'] ) ) {
		return '';
	}
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return get_option( 'acdq_anthropic_key', '' );
	}
	return sanitize_text_field( $value );
}

function acdq_ai_settings_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$has_key           = '' !== get_option( 'acdq_anthropic_key', '' );
	$constant_defined  = defined( 'ACDQ_ANTHROPIC_KEY' ) && ACDQ_ANTHROPIC_KEY;
	?>
	<div class="wrap">
		<h1>Clé API IA (Anthropic)</h1>

		<?php if ( $constant_defined ) : ?>
			<div class="notice notice-info">
				<p>Une clé <code>ACDQ_ANTHROPIC_KEY</code> est déjà définie dans <code>wp-config.php</code> — elle est utilisée en priorité et ce réglage est actuellement ignoré.</p>
			</div>
		<?php endif; ?>

		<p>
			Les fonctionnalités IA du site (recherche intelligente, assistant de clavardage, réécriture de
			description de clinique) nécessitent une clé API Anthropic (Claude). Vous pouvez en obtenir une sur
			<a href="https://console.anthropic.com/" target="_blank" rel="noopener">console.anthropic.com</a>.
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'acdq_ai_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acdq_anthropic_key">Clé API</label></th>
					<td>
						<input
							type="password"
							id="acdq_anthropic_key"
							name="acdq_anthropic_key"
							class="regular-text"
							autocomplete="off"
							placeholder="<?php echo $has_key ? esc_attr__( 'Clé enregistrée — laissez vide pour ne pas la modifier' ) : 'sk-ant-...'; ?>"
						>
						<p class="description">
							<?php if ( $has_key ) : ?>
								Une clé est actuellement enregistrée (elle n'est jamais réaffichée ici). Laissez ce champ vide et cliquez sur Enregistrer pour la conserver telle quelle.
							<?php else : ?>
								Aucune clé enregistrée pour le moment.
							<?php endif; ?>
						</p>
						<?php if ( $has_key ) : ?>
							<label>
								<input type="checkbox" name="acdq_anthropic_key_clear" value="1">
								Supprimer la clé enregistrée
							</label>
						<?php endif; ?>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Enregistrer' ); ?>
		</form>
	</div>
	<?php
}
