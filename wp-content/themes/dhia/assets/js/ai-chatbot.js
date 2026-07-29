(function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof acdqAiChat === 'undefined' ) return;

		var root = document.getElementById( 'acdq-chat' );
		if ( ! root ) return;

		var toggleBtn = root.querySelector( '.acdq-chat-toggle' );
		var closeBtn = root.querySelector( '.acdq-chat-close' );
		var panel = document.getElementById( 'acdq-chat-panel' );
		var messagesEl = document.getElementById( 'acdq-chat-messages' );
		var form = document.getElementById( 'acdq-chat-form' );
		var input = document.getElementById( 'acdq-chat-input' );
		var quickstartBtn = document.getElementById( 'acdq-chat-quickstart' );

		// Conversation history lives only in this page's memory — never sent
		// anywhere except back to our own endpoint on the next message, and
		// never persisted (no localStorage, no cookie). A reload clears it.
		var history = [];
		var sending = false;

		// Guided "find a clinic" flow, alongside normal open-ended chat below.
		// step 0 = inactive (typing goes to the normal FAQ endpoint);
		// step 1 = awaiting the need/problem answer; step 2 = awaiting region.
		var triage = { step: 0, need: '', region: '' };

		function openPanel() {
			panel.hidden = false;
			toggleBtn.setAttribute( 'aria-expanded', 'true' );
			input.focus();
		}
		function closePanel() {
			panel.hidden = true;
			toggleBtn.setAttribute( 'aria-expanded', 'false' );
		}

		toggleBtn.addEventListener( 'click', function () {
			if ( panel.hidden ) openPanel(); else closePanel();
		} );
		closeBtn.addEventListener( 'click', closePanel );

		function addMessage( role, text ) {
			var div = document.createElement( 'div' );
			div.className = 'acdq-chat-msg acdq-chat-msg-' + ( role === 'user' ? 'user' : 'bot' );
			div.textContent = text;
			messagesEl.appendChild( div );
			messagesEl.scrollTop = messagesEl.scrollHeight;
			return div;
		}

		function addTyping() {
			var typing = document.createElement( 'div' );
			typing.className = 'acdq-chat-msg acdq-chat-msg-bot acdq-chat-typing';
			typing.textContent = '…';
			messagesEl.appendChild( typing );
			messagesEl.scrollTop = messagesEl.scrollHeight;
			return typing;
		}

		function addResultLink( url ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'acdq-chat-msg acdq-chat-msg-bot acdq-chat-result';
			var a = document.createElement( 'a' );
			a.href = url;
			a.className = 'btn btn-primary';
			a.textContent = 'Voir les cliniques →';
			wrap.appendChild( a );
			messagesEl.appendChild( wrap );
			messagesEl.scrollTop = messagesEl.scrollHeight;
		}

		quickstartBtn.addEventListener( 'click', function () {
			if ( sending ) return;
			triage = { step: 1, need: '', region: '' };
			quickstartBtn.hidden = true;
			addMessage( 'bot', 'Quel est votre problème ou besoin ?' );
			input.focus();
		} );

		function submitTriage() {
			sending = true;
			var typing = addTyping();

			var params = new URLSearchParams();
			params.set( 'action', 'acdq_ai_chat_triage' );
			params.set( 'nonce', acdqAiChat.nonce );
			params.set( 'need', triage.need );
			params.set( 'region', triage.region );

			fetch( acdqAiChat.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString(),
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					typing.remove();
					if ( res && res.success && res.data && res.data.url ) {
						addMessage( 'bot', res.data.message || 'Voici ce que j’ai trouvé.' );
						addResultLink( res.data.url );
					} else {
						addMessage( 'bot', ( res && res.data && res.data.message ) || 'Désolé, une erreur est survenue.' );
					}
				} )
				.catch( function () {
					typing.remove();
					addMessage( 'bot', 'Désolé, une erreur de connexion est survenue.' );
				} )
				.finally( function () {
					sending = false;
					quickstartBtn.hidden = false;
				} );
		}

		function handleTriageAnswer( text ) {
			if ( 1 === triage.step ) {
				triage.need = text;
				triage.step = 2;
				addMessage( 'bot', 'Quelle est votre région ou ville ?' );
				return;
			}
			// step 2
			triage.region = text;
			triage.step = 0;
			submitTriage();
		}

		function submitChatMessage( text ) {
			history.push( { role: 'user', content: text } );
			sending = true;
			var typing = addTyping();

			var params = new URLSearchParams();
			params.set( 'action', 'acdq_ai_chat' );
			params.set( 'nonce', acdqAiChat.nonce );
			params.set( 'messages', JSON.stringify( history ) );

			fetch( acdqAiChat.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString(),
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					typing.remove();
					if ( res && res.success && res.data && res.data.reply ) {
						addMessage( 'bot', res.data.reply );
						history.push( { role: 'assistant', content: res.data.reply } );
					} else {
						var msg = ( res && res.data && res.data.message ) || 'Désolé, une erreur est survenue.';
						addMessage( 'bot', msg );
						history.pop(); // don't carry a failed turn forward
					}
				} )
				.catch( function () {
					typing.remove();
					addMessage( 'bot', 'Désolé, une erreur de connexion est survenue.' );
					history.pop();
				} )
				.finally( function () {
					sending = false;
				} );
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( sending ) return;

			var text = input.value.trim();
			if ( ! text ) return;

			addMessage( 'user', text );
			input.value = '';

			if ( triage.step > 0 ) {
				handleTriageAnswer( text );
			} else {
				submitChatMessage( text );
			}
		} );
	} );
} )();
