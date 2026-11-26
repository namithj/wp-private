/* WP Private settings screen: copy buttons, label wiring, tab memory, unsaved-changes guard. */
( function ( $ ) {
	'use strict';

	$( function () {
		const $root = $( '.wp-private-settings' );
		if ( ! $root.length ) {
			return;
		}
		const i18n = ( window.wpPrivateAdmin && window.wpPrivateAdmin.i18n ) || {};
		const $form = $root.find( 'form[action="options.php"]' );
		const speak = function ( message ) {
			if ( window.wp && window.wp.a11y && message ) {
				window.wp.a11y.speak( message );
			}
		};

		// 1. Copy buttons.
		const selectText = function ( node ) {
			const range = document.createRange();
			range.selectNodeContents( node );
			const selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange( range );
		};
		$root.find( '.wp-private-snippet' ).each( function () {
			const $snippet = $( this );
			const $button = $( '<button type="button" class="button button-small wp-private-copy"></button>' ).text( i18n.copy );
			$button.on( 'click', function () {
				const text = $snippet.text();
				const fail = function () {
					selectText( $snippet[ 0 ] );
					speak( i18n.copyFailed );
				};
				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( text ).then( function () {
						speak( i18n.copied );
						$button.text( i18n.copied );
						window.setTimeout( function () {
							$button.text( i18n.copy );
						}, 2000 );
					}, fail );
				} else {
					fail();
				}
			} );
			$snippet.after( $button );
		} );

		// 2. Label wiring (cassette-cmf prints plain th cells inside tabs, see WP-CMF-6).
		$root.find( '.cassette-cmf-tab-panel .form-table > tbody > tr, .cassette-cmf-tab-panel .form-table > tr' ).each( function ( index ) {
			const $row = $( this );
			const $th = $row.children( 'th' );
			if ( ! $th.length || $th.find( 'label' ).length ) {
				return;
			}
			const $control = $row.find( 'td' ).find( 'textarea, input[type="url"], input[type="text"], select' ).first();
			const id = $control.attr( 'id' );
			if ( id ) {
				const $label = $( '<label></label>' ).attr( 'for', id ).text( $th.text() );
				$th.empty().append( $label );
				return;
			}
			const $group = $row.find( 'td fieldset' ).filter( function () {
				return $( this ).find( 'input[type="radio"]' ).length > 0;
			} ).first();
			if ( $group.length ) {
				const thId = 'wp-private-th-' + index;
				$th.attr( 'id', thId );
				$group.attr( 'aria-labelledby', thId );
			}
		} );

		// 3. Tab memory.
		const KEY = 'wpPrivateSettingsTab';
		try {
			const stored = window.sessionStorage.getItem( KEY );
			if ( stored && /^[a-z0-9_-]+$/.test( stored ) ) {
				const $button = $root.find( '.cassette-cmf-tab-button[data-tab="' + stored + '"]' );
				if ( $button.length && ! $button.hasClass( 'active' ) ) {
					$button.trigger( 'click' );
				}
			}
		} catch ( e ) {
			// Storage unavailable: no memory.
		}
		$( document ).on( 'cassette-cmf-tab-activated', function ( event, $panel ) {
			if ( ! $panel || ! $panel.length || ! $root.has( $panel ).length ) {
				return;
			}
			try {
				window.sessionStorage.setItem( KEY, String( $panel.attr( 'data-tab' ) ) );
			} catch ( e ) {
				// Storage unavailable: no memory.
			}
		} );

		// 4. Unsaved-changes guard. The user search input belongs to another form (form=), so it is ignored.
		if ( $form.length ) {
			const formNode = $form[ 0 ];
			let dirty = false;
			$form.on( 'input change', 'input, textarea, select', function () {
				if ( this.form === formNode ) {
					dirty = true;
				}
			} );
			$form.on( 'submit', function () {
				dirty = false;
			} );
			window.addEventListener( 'beforeunload', function ( event ) {
				if ( dirty ) {
					event.preventDefault();
					event.returnValue = '';
				}
			} );
		}
	} );
}( jQuery ) );
