(function ( global, $ ) {
	// shared scope insied IIFE in case it's needed.
	var editor;
	var syncCSS = function () {
		$( ".tenup_css" ).val( editor.getSession().getValue() );
	};
	var loadAce = function () {
		// Set up ACE editor
		editor = ace.edit( 'safecss-ace' );
		// Globalize it so we can access it other places
		global.safecss_editor = editor;
		// Word-wrap, othewise the initial comments are borked.
		editor.getSession().setUseWrapMode( true );
		// This adds an annoying vertical line to the editor; get rid of it.
		editor.setShowPrintMargin( false );
		// Grab straight from the textarea
		editor.getSession().setValue( $( ".tenup_css" ).val() );
		// We're editing CSS content
		var CSSMode = ace.require( 'ace/mode/css' ).Mode;
		editor.getSession().setMode( new CSSMode() );
		// ace.js comes with the textmate coloring scheme already.
		// kill the spinner
		jQuery.fn.spin && $( "#safecss-container" ).spin( false );

		// When submitting, make sure to include the updated CSS
		// The Ace editor unfortunately doesn't handle this for us
		$( '.tenup_css_form' ).submit( syncCSS );
		$( '#post' ).submit( syncCSS );
	}

	// exit if we're on IE <= 7
	if ( ( $.browser.msie && parseInt( $.browser.version, 10 ) <= 7 ) || navigator.userAgent.match( /iPad/i ) != null ) {
		$( "#safecss-container" ).hide();
		$( ".tenup_css" ).removeClass( 'hide-if-js' );
		return false;
	}
	// syntaxy goodness.
	else {
		$( '#safecss-ace, #safecss-container' ).css( 'height',
			Math.max( 250, $( window ).height() - $( '#safecss-container' ).offset().top - $( '#wpadminbar' ).height() - 150 )
		);

		$( global ).load( loadAce );
	}

	// for now, expose the syncCSS function.
	global.aceSyncCSS = syncCSS;

})( this, jQuery );


