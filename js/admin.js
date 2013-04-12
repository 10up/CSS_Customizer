/**
 * This code is taken almost directly from the Theme Editor scripts used in WordPress core to allow the use of the tab
 * key from within textarea elements.
 *
 * @since 0.1
 */
jQuery( document ).ready( function ( $ ) {
	// tab in textareas
	$( '.tenup_css' ).on( 'keydown',function ( e ) {
		if ( e.keyCode !== 9 ) {
			return true;
		}

		var el = e.target,
			selStart = el.selectionStart,
			selEnd = el.selectionEnd,
			val = el.value,
			scroll,
			sel;

		try {
			this.lastKey = 9; // not a standard DOM property, lastKey is to help stop Opera tab event. See blur handler below.
		} catch ( err ) {
		}

		if ( document.selection ) {
			el.focus();
			sel = document.selection.createRange();
			sel.text = '\t';
		} else if ( selStart >= 0 ) {
			scroll = this.scrollTop;
			el.value = val.substring( 0, selStart ).concat( '\t', val.substring( selEnd ) );
			el.selectionStart = el.selectionEnd = selStart + 1;
			this.scrollTop = scroll;
		}

		if ( e.stopPropagation ) {
			e.stopPropagation();
		}

		if ( e.preventDefault ) {
			e.preventDefault();
		}

		return false;
	} ).on( 'blur', function ( e ) {
			if ( this.lastKey && 9 === this.lastKey ) {
				this.focus();
			}
		} );
} );

(function ( $ ) {
	var safe = document.querySelector( '.tenup_css' ),
		$safe = $( safe ),
		$win = $( window );

	function safecssResize () {
		if ( null === safe ) {
			return;
		}

		$safe.height( win.height() - $safe.offset().top - 250 );
	};

	window.onresize = safecssResize;
	addLoadEvent( safecssResize );
})( jQuery );
