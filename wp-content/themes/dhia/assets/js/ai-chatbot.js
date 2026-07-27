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

		// Conversation history lives only in this page's memory — never sent
		// anywhere except back to our own endpoint on the next message, and
		// never persisted (no localStorage, no cookie). A reload clears it.
		var history = [];
		var sending = false;

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
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( sending ) return;

			var text = input.value.trim();
			if ( ! text ) return;

			addMessage( 'user', text );
			history.push( { role: 'user', content: text } );
			input.value = '';
			sending = true;

			var typing = document.createElement( 'div' );
			typing.className = 'acdq-chat-msg acdq-chat-msg-bot acdq-chat-typing';
			typing.textContent = '…';
			messagesEl.appendChild( typing );
			messagesEl.scrollTop = messagesEl.scrollHeight;

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
		} );
	} );
} )();
