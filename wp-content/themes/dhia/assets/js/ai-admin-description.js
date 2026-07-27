(function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof acdqAiAdmin === 'undefined' ) return;

		var improveBtn = document.getElementById( 'acdq-ai-improve-btn' );
		if ( ! improveBtn ) return;

		var resultBox = document.getElementById( 'acdq-ai-improve-result' );
		var suggestionEl = document.getElementById( 'acdq-ai-improve-suggestion' );
		var acceptBtn = document.getElementById( 'acdq-ai-improve-accept' );
		var rejectBtn = document.getElementById( 'acdq-ai-improve-reject' );
		var statusEl = document.getElementById( 'acdq-ai-improve-status' );

		// Classic editor: prefer TinyMCE's live content when the visual tab is
		// active, since the underlying #content textarea isn't synced until then.
		function getEditorContent() {
			if ( typeof tinymce !== 'undefined' ) {
				var editor = tinymce.get( 'content' );
				if ( editor && ! editor.isHidden() ) {
					return editor.getContent( { format: 'text' } );
				}
			}
			var textarea = document.getElementById( 'content' );
			return textarea ? textarea.value : '';
		}

		function setEditorContent( text ) {
			if ( typeof tinymce !== 'undefined' ) {
				var editor = tinymce.get( 'content' );
				if ( editor && ! editor.isHidden() ) {
					editor.setContent( text );
					editor.save(); // sync back to the textarea WP actually submits
					return;
				}
			}
			var textarea = document.getElementById( 'content' );
			if ( textarea ) textarea.value = text;
		}

		improveBtn.addEventListener( 'click', function () {
			var draft = getEditorContent().trim();
			if ( ! draft ) {
				statusEl.textContent = "Écrivez d'abord un brouillon dans l'éditeur.";
				return;
			}

			improveBtn.disabled = true;
			statusEl.textContent = 'Génération en cours…';
			resultBox.style.display = 'none';

			var params = new URLSearchParams();
			params.set( 'action', 'acdq_ai_improve_description' );
			params.set( 'nonce', acdqAiAdmin.nonce );
			params.set( 'draft', draft );

			fetch( acdqAiAdmin.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString(),
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res && res.success && res.data && res.data.suggestion ) {
						suggestionEl.value = res.data.suggestion;
						resultBox.style.display = 'block';
						statusEl.textContent = '';
					} else {
						statusEl.textContent = ( res && res.data && res.data.message ) || 'Erreur lors de la génération.';
					}
				} )
				.catch( function () {
					statusEl.textContent = 'Erreur de connexion. Réessayez.';
				} )
				.finally( function () {
					improveBtn.disabled = false;
				} );
		} );

		// "Accepter" only replaces the in-browser editor content — nothing is
		// persisted until the admin uses WordPress's own Publish/Update button.
		acceptBtn.addEventListener( 'click', function () {
			setEditorContent( suggestionEl.value );
			resultBox.style.display = 'none';
			statusEl.textContent = 'Suggestion appliquée — pensez à enregistrer.';
		} );

		rejectBtn.addEventListener( 'click', function () {
			resultBox.style.display = 'none';
			statusEl.textContent = '';
		} );
	} );
} )();
