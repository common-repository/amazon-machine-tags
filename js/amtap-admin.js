/**
 * @author Martin Kliehm
 * JavaScript Object for the WordPress Amazon Machine Tags plugin
 */
var AMTAP = {
	// Initialize
	init: function() {
		// Get key value
		if ( oKeyField = document.getElementById( 'aws_access_key_id' ) ) {
			// Add event listener for the key field
			YAHOO.util.Event.addListener( oKeyField, 'blur', AMTAP.verify_key );
		}
	},
	
	// Key verification: create response field and send AJAX request
	verify_key: function() {
	
		// Hide non-AJAX status messages
		if ( oMessage = document.getElementById( 'message' ) )
			oMessage.style.display = 'none';
		if ( oAmtapResponse = document.getElementById( 'amtap-feedback' ) )
			oAmtapResponse.style.display = 'none';
		
		// Response area exists: fill with loading text
		if ( oAjaxResponse = document.getElementById( 'responseArea' ) ) {
			// Set class names
			oAjaxResponse.setAttribute( 'class', 'loading fade' );
			oAjaxResponse.className = 'loading fade';
			// Set XHTML role attribute
			oAjaxResponse.setAttribute( 'role', 'progressbar' );
			// Reset style attribute from Fat color fade
			oAjaxResponse.setAttribute( 'style', '' );
			// Display loading message
			var oTextNode = document.createTextNode( sLoadingMessage );
			var oReplaceNode = oElm.childNodes[0];
			oElm.replaceChild( oTextNode, oReplaceNode );
		} else {
			// Create response area and loading text
			oKeyField = document.getElementById( 'aws_access_key_id' );
			oElm = document.createElement( 'div' );
			oElm.setAttribute( 'id', 'responseArea' );
			// Set class name and XHTML role attribute
			oElm.setAttribute( 'class', 'loading' );
			oElm.className = 'loading fade';
			oElm.setAttribute( 'role', 'progressbar' );
			// Display loading message
			oTextNode = document.createTextNode( sLoadingMessage );
			oElm.appendChild( oTextNode );
			oKeyField.parentNode.appendChild( oElm );
		}

		// Send AJAX request
		var result = YAHOO.util.Connect.asyncRequest('GET', '../wp-content/plugins/amazon-machine-tags/amtap.php?ajax=true&AWSAccessKeyId=' + this.value, amtapCallback, null);
	},
	
	// Display response
	response: function(o) {
		var oResponseArea = document.getElementById( 'responseArea' );
		var oInput = document.getElementById( 'aws_access_key_id' );
		// Split response into status and text
		o.responseText = o.responseText.split( ';;' );
		var oTextNode = document.createTextNode( o.responseText[1] );
		var oReplaceNode = oResponseArea.childNodes[0];
		// Set response class and role
		oResponseArea.setAttribute( 'class', o.responseText[0] + ' fade' );
		oResponseArea.className = o.responseText[0] + ' fade';
		oResponseArea.setAttribute( 'role', 'alert' );
		// Set ARIA invalid parameter on input field
		if ( o.responseText[0].indexOf( 'invalid' ) != -1 || o.responseText[0].indexOf( 'empty' ) != -1 ) {
			oInput.setAttribute( 'aria-invalid', 'true' );
		} else {
			oInput.removeAttribute( 'aria-invalid' );
		}
		// Set response text
		oResponseArea.replaceChild( oTextNode, oReplaceNode );
		// Yellow fade: use built-in WordPress function
		if ( typeof( Fat ) != 'undefined' ) {
			Fat.fade_element( 'responseArea' );
		}
	}
};

// YAHOO Connect AJAX callback object
var amtapCallback = {
	timeout: 10000,
	success: AMTAP.response,
	failure: AMTAP.response,
	scope: AMTAP
};

// Initialize AMTAP object
if ( typeof( YAHOO ) != 'undefined' ) {
	AMTAP.init();
}