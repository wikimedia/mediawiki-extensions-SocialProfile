/**
 * Provides a method for checking whether the user's browser supports Flash or
 * not in order to display the correct "Loading..." image (.gif for mobile
 * devices and other clients which don't support Flash, and .swf for the ones
 * that have Flash installed).
 *
 * @file
 * @date 14 August 2015
 */
/* global ActiveXObject, mediaWiki */
( function ( mw, $ ) {
	'use strict';

	/**
	 * @see http://stackoverflow.com/questions/998245/how-can-i-detect-if-flash-is-installed-and-if-not-display-a-hidden-div-that-inf/20095467#20095467
	 * @return {Boolean}
	 */
	window.isFlashSupported = function () {
		var hasFlash = false;
		try {
			var fo = new ActiveXObject( 'ShockwaveFlash.ShockwaveFlash' );
			if ( fo ) {
				hasFlash = true;
			}
		} catch ( e ) {
			if (
				navigator.mimeTypes &&
				navigator.mimeTypes['application/x-shockwave-flash'] !== undefined &&
				navigator.mimeTypes['application/x-shockwave-flash'].enabledPlugin
			)
			{
				hasFlash = true;
			}
		}
		return hasFlash;
	};

} ( mediaWiki, jQuery ) );