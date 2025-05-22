/*
	Lightbox JS: Fullsize Image Overlays
	by Lokesh Dhakar - http://www.huddletogether.com
	For more information on this script, visit:
	http://huddletogether.com/projects/lightbox/
	Licensed under the Creative Commons Attribution 2.5 License - http://creativecommons.org/licenses/by/2.5/
	(basically, do anything you want, just leave my name and link)
	Stripped this down a bit - Ashish
	Rewritten to be more object-oriented by Jack Phoenix
	on 21 June 2011 and JSHinted on 14 August 2015
*/
window.LightBox = {
	/**
	 * Core code from quirksmode.org
	 *
	 * @return Array with x,y page scroll values.
	 */
	getPageScroll: function () {
		let yScroll;

		if ( this.pageYOffset ) {
			yScroll = this.pageYOffset;
		} else if ( document.documentElement && document.documentElement.scrollTop ) { // Explorer 6 Strict
			yScroll = document.documentElement.scrollTop;
		} else if ( document.body ) { // all other Explorers
			yScroll = document.body.scrollTop;
		}

		const arrayPageScroll = new Array( '', yScroll );
		return arrayPageScroll;
	},

	/**
	 * Core code from quirksmode.org
	 * Edit for Firefox by pHaez
	 *
	 * @return Array with page width, height and window width, height
	 */
	getPageSize: function () {
		let xScroll, yScroll;

		if ( window.innerHeight && window.scrollMaxY ) {
			xScroll = document.body.scrollWidth;
			yScroll = window.innerHeight + window.scrollMaxY;
		} else if ( document.body.scrollHeight > document.body.offsetHeight ) { // all but Explorer Mac
			xScroll = document.body.scrollWidth;
			yScroll = document.body.scrollHeight;
		} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
			xScroll = document.body.offsetWidth;
			yScroll = document.body.offsetHeight;
		}

		let windowWidth, windowHeight;
		if ( this.innerHeight ) { // all except Explorer
			windowWidth = this.innerWidth;
			windowHeight = this.innerHeight;
		} else if ( document.documentElement && document.documentElement.clientHeight ) { // Explorer 6 Strict Mode
			windowWidth = document.documentElement.clientWidth;
			windowHeight = document.documentElement.clientHeight;
		} else if ( document.body ) { // other Explorers
			windowWidth = document.body.clientWidth;
			windowHeight = document.body.clientHeight;
		}

		// for small pages with total height less then height of the viewport
		let pageHeight, pageWidth;
		if ( yScroll < windowHeight ) {
			pageHeight = windowHeight;
		} else {
			pageHeight = yScroll;
		}

		// for small pages with total width less then width of the viewport
		if ( xScroll < windowWidth ) {
			pageWidth = windowWidth;
		} else {
			pageWidth = xScroll;
		}

		const arrayPageSize = new Array( pageWidth, pageHeight, windowWidth, windowHeight );
		return arrayPageSize;
	},

	/**
	 * Pauses code execution for specified time. Uses busy code, not good.
	 * Code from http://www.faqts.com/knowledge_base/view.phtml/aid/1602
	 *
	 * @param numberMillis
	 */
	pause: function ( numberMillis ) {
		let now = new Date();
		const exitTime = now.getTime() + numberMillis;
		while ( true ) {
			now = new Date();
			if ( now.getTime() > exitTime ) {
				return;
			}
		}
	},

	/**
	 * Preloads images. Places new image in lightbox then centers and displays.
	 *
	 * @param objLink
	 */
	show: function ( objLink ) {
		const lb = this;
		// prepare objects
		const objOverlay = document.getElementById( 'overlay' );
		const objLightbox = document.getElementById( 'lightbox' );
		const objImage = document.getElementById( 'lightboxImage' );
		const objLightboxText = document.getElementById( 'lightboxText' );

		let arrayPageSize = lb.getPageSize();
		// var arrayPageScroll = lb.getPageScroll();

		objLightboxText.style.display = 'none';
		// set height of Overlay to take up whole page and show
		objOverlay.style.height = ( arrayPageSize[ 1 ] + 'px' );
		objOverlay.style.display = 'block';

		// preload image
		const imgPreload = new Image();

		imgPreload.onload = function () {
			objImage.src = objLink.href;

			// center lightbox and make sure that the top and left values are not negative
			// and the image placed outside the viewport
			// var lightboxTop = arrayPageScroll[1] + ( ( arrayPageSize[3] - 35 - imgPreload.height ) / 2 );
			// var lightboxLeft = ( ( arrayPageSize[0] - 20 - imgPreload.width ) / 2 );

			// objLightbox.style.top = ( lightboxTop < 0 ) ? '0px' : lightboxTop + 'px';
			// objLightbox.style.left = ( lightboxLeft < 0 ) ? '0px' : lightboxLeft + 'px';

			// A small pause between the image loading and displaying is required with IE,
			// this prevents the previous image displaying for a short burst causing flicker.
			if ( navigator.appVersion.includes( 'MSIE' ) ) {
				lb.pause( 250 );
			}

			// Hide select boxes as they will 'peek' through the image in IE
			const selects = document.getElementsByTagName( 'select' );

			for ( let i = 0; i !== selects.length; i++ ) {
				selects[ i ].style.visibility = 'hidden';
			}

			objLightbox.style.display = 'block';
			objImage.style.display = 'block';

			// After image is loaded, update the overlay height as the new image might have
			// increased the overall page height.
			arrayPageSize = lb.getPageSize();
			objOverlay.style.height = ( arrayPageSize[ 1 ] + 'px' );

			this.onload = function () {
				return;
			};
		};

		imgPreload.src = objLink.href;
	},

	hide: function () {
		// get objects
		const objOverlay = document.getElementById( 'overlay' );
		const objLightbox = document.getElementById( 'lightbox' );
		const objLightBoxImg = document.getElementById( 'lightboxImage' );

		// hide lightbox and overlay
		objOverlay.style.display = 'none';
		objLightbox.style.display = 'none';

		objLightBoxImg.style.display = 'none';

		// make select boxes visible
		const selects = document.getElementsByTagName( 'select' );
		for ( let i = 0; i !== selects.length; i++ ) {
			selects[ i ].style.visibility = 'visible';
		}

		// disable keypress listener
		document.onkeypress = '';
	},

	/**
	 * Function runs on window load, going through link tags looking for rel="lightbox".
	 * These links receive onclick events that enable the lightbox display for their targets.
	 * The function also inserts html markup at the top of the page which will be used as a
	 * container for the overlay pattern and the inline image.
	 */
	init: function () {
		if ( !document.getElementsByTagName ) {
			return;
		}

		const objBody = document.getElementsByTagName( 'body' ).item( 0 );
		const lb = this;

		// create overlay div and hardcode some functional styles
		// (aesthetic styles are in CSS file)
		const objOverlay = document.createElement( 'div' );
		objOverlay.setAttribute( 'id', 'overlay' );
		objOverlay.onclick = function () {
			lb.hide();
			return false;
		};
		objOverlay.style.display = 'none';
		objOverlay.style.position = 'absolute';
		objOverlay.style.top = '0';
		objOverlay.style.left = '0';
		objOverlay.style.zIndex = '101';
		objOverlay.style.width = '100%';
		objBody.insertBefore( objOverlay, objBody.firstChild );

		// var arrayPageSize = lb.getPageSize();
		// var arrayPageScroll = lb.getPageScroll();

		// preload and create loader image
		// var imgPreloader = new Image();

		// create lightbox div, same note about styles as above
		const objLightbox = document.createElement( 'div' );
		objLightbox.setAttribute( 'id', 'lightbox' );
		objLightbox.style.display = 'none';
		objLightbox.style.position = 'absolute';
		objLightbox.style.zIndex = '102';
		objBody.insertBefore( objLightbox, objOverlay.nextSibling );

		// create lightbox div, same note about styles as above
		const objLightboxText = document.createElement( 'div' );
		objLightboxText.setAttribute( 'id', 'lightboxText' );
		objLightboxText.style.display = 'none';
		objLightboxText.style.zIndex = '102';
		objLightboxText.style.textAlign = 'center';
		objLightbox.appendChild( objLightboxText );

		// create image
		const objImage = document.createElement( 'img' );
		objImage.setAttribute( 'id', 'lightboxImage' );
		objImage.style.display = 'none';
		objLightbox.appendChild( objImage );
	},

	setText: function ( message ) {
		const lb = this;
		// prep objects
		// var objOverlay = document.getElementById( 'overlay' );
		const objLightbox = document.getElementById( 'lightbox' );
		const objImage = document.getElementById( 'lightboxImage' );
		const objLightboxText = document.getElementById( 'lightboxText' );

		const arrayPageSize = lb.getPageSize();
		const arrayPageScroll = lb.getPageScroll();

		objImage.style.display = 'none';
		objLightboxText.style.opacity = 0.01; // added
		objLightbox.style.display = 'block';
		objLightboxText.style.display = 'block';
		objLightboxText.innerHTML = message;

		// center lightbox and make sure that the top and left values are not negative
		// and the image placed outside the viewport
		const dimensionsObj = lb.getDimensions( objLightboxText );
		const lightboxTop = arrayPageScroll[ 1 ] + ( ( arrayPageSize[ 3 ] - 35 - dimensionsObj.height ) / 2 );
		const lightboxLeft = ( ( arrayPageSize[ 0 ] - 20 - dimensionsObj.width ) / 2 );

		objLightbox.style.top = ( lightboxTop < 0 ) ? '0px' : lightboxTop + 'px';
		objLightbox.style.left = ( lightboxLeft < 0 ) ? '0px' : lightboxLeft + 'px';
		objLightboxText.style.opacity = 1.00; // added
	},

	/**
	 * This function is from the YUI library.
	 *
	 * @param element The element whose width and height we want to get
	 * @return Array
	 */
	getDimensions: function ( element ) {
		const display = element.style.display;

		if ( display !== 'none' && display !== null ) { // Safari bug
			return { width: element.offsetWidth, height: element.offsetHeight };
		}

		// All *Width and *Height properties give 0 on elements with display none,
		// so enable the element temporarily
		const els = element.style;
		const originalVisibility = els.visibility;
		const originalPosition = els.position;
		const originalDisplay = els.display;
		els.visibility = 'hidden';
		els.position = 'absolute';
		els.display = 'block';

		const originalWidth = element.clientWidth;
		const originalHeight = element.clientHeight;
		els.display = originalDisplay;
		els.position = originalPosition;
		els.visibility = originalVisibility;

		return { width: originalWidth, height: originalHeight };
	}
};
