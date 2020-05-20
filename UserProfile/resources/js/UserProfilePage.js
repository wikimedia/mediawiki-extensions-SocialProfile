/**
 * JavaScript functions used by UserProfile
 */
var UserProfilePage = {
	posted: 0,
	numReplaces: 0,
	replaceID: 0,
	replaceSrc: '',
	oldHtml: '',

	changeUserPageType: function () {
		( new mw.Api() ).postWithToken( 'csrf', {
			action: 'smpuserprofiletype',
			format: 'json',
			do: 'set'
		} ).done( function () {
			// @todo This works, but is kinda crude. Ideally we'd show a spinner and maybe
			// even load the requested page's content (wikitext page or social profile)
			// using AJAX, if possible.
			window.location.reload();
		} );
	},

	sendMessage: function () {
		var userTo = decodeURIComponent( mw.config.get( 'wgTitle' ) ), // document.getElementById( 'user_name_to' ).value;
			encMsg = encodeURIComponent( document.getElementById( 'message' ).value ),
			msgType = document.getElementById( 'message_type' ).value;
		if ( document.getElementById( 'message' ).value && !UserProfilePage.posted ) {
			UserProfilePage.posted = 1;
			( new mw.Api() ).postWithToken( 'csrf', {
				action: 'socialprofile-send-message',
				format: 'json',
				username: userTo,
				message: encMsg,
				type: msgType
			} ).done( function ( data ) {
				$( data.result ).prependTo( '#user-page-board' );
				UserProfilePage.posted = 0;
				$( '#message' ).val( '' );
			} );
		}
	},

	deleteMessage: function ( id ) {
		if ( window.confirm( mw.msg( 'user-board-confirm-delete' ) ) ) {
			( new mw.Api() ).postWithToken( 'csrf', {
				action: 'socialprofile-delete-message',
				format: 'json',
				id: id
			} ).done( function () {
				// window.location.reload();
				// 1st parent = span.user-board-red
				// 2nd parent = div.user-board-message-links
				// 3rd parent = div.user-board-message = the container of a msg
				$( '[data-message-id="' + id + '"]' ).parent().parent().parent().hide( 100 );
			} );
		}
	},

	showUploadFrame: function () {
		document.getElementById( 'upload-container' ).style.display = 'block';
		document.getElementById( 'upload-container' ).style.visibility = 'visible';
	},

	uploadError: function ( message ) {
		document.getElementById( 'mini-gallery-' + UserProfilePage.replaceID ).innerHTML = UserProfilePage.oldHtml;
		document.getElementById( 'upload-frame-errors' ).innerHTML = message;
		document.getElementById( 'imageUpload-frame' ).src = 'index.php?title=Special:MiniAjaxUpload&wpThumbWidth=75';

		document.getElementById( 'upload-container' ).style.display = 'block';
		document.getElementById( 'upload-container' ).style.visibility = 'visible';
	},

	textError: function ( message ) {
		document.getElementById( 'upload-frame-errors' ).innerHTML = message;
		document.getElementById( 'upload-frame-errors' ).style.display = 'block';
		document.getElementById( 'upload-frame-errors' ).style.visibility = 'visible';
	},

	completeImageUpload: function () {
		document.getElementById( 'upload-frame-errors' ).style.display = 'none';
		document.getElementById( 'upload-frame-errors' ).style.visibility = 'hidden';
		document.getElementById( 'upload-frame-errors' ).innerHTML = '';
		UserProfilePage.oldHtml = document.getElementById( 'mini-gallery-' + UserProfilePage.replaceID ).innerHTML;

		for ( var x = 7; x > 0; x-- ) {
			document.getElementById( 'mini-gallery-' + ( x ) ).innerHTML =
				document.getElementById( 'mini-gallery-' + ( x - 1 ) ).innerHTML.replace( 'slideShowLink(' + ( x - 1 ) + ')', 'slideShowLink(' + ( x ) + ')' );
		}
		document.getElementById( 'mini-gallery-0' ).innerHTML =
			'<a><img height="75" width="75" src="' +
			mw.config.get( 'wgExtensionAssetsPath' ) +
			'/SocialProfile/images/ajax-loader-white.gif" alt="" /></a>';

		if ( document.getElementById( 'no-pictures-containers' ) ) {
			document.getElementById( 'no-pictures-containers' ).style.display = 'none';
			document.getElementById( 'no-pictures-containers' ).style.visibility = 'hidden';
		}
		document.getElementById( 'pictures-containers' ).style.display = 'block';
		document.getElementById( 'pictures-containers' ).style.visibility = 'visible';
	},

	uploadComplete: function ( imgSrc, imgName ) {
		UserProfilePage.replaceSrc = imgSrc;

		document.getElementById( 'upload-frame-errors' ).innerHTML = '';

		// document.getElementById( 'imageUpload-frame' ).onload = function() {
		// var idOffset = -1 - UserProfilePage.numReplaces;
		var __image_prefix;
		// $D.addClass( 'mini-gallery-0', 'mini-gallery' );
		// document.getElementById('mini-gallery-0').innerHTML = '<a href=\"javascript:slideShowLink(' + idOffset + ')\">' + UserProfilePage.replaceSrc + '</a>';
		document.getElementById( 'mini-gallery-0' ).innerHTML = '<a href="' + __image_prefix + imgName + '">' + UserProfilePage.replaceSrc + '</a>';

		// UserProfilePage.replaceID = ( UserProfilePage.replaceID == 7 ) ? 0 : ( UserProfilePage.replaceID + 1 );
		UserProfilePage.numReplaces += 1;
		// }
		// if ( document.getElementById( 'imageUpload-frame' ).captureEvents ) document.getElementById( 'imageUpload-frame' ).captureEvents( Event.LOAD );

		document.getElementById( 'imageUpload-frame' ).src = 'index.php?title=Special:MiniAjaxUpload&wpThumbWidth=75&extra=' + UserProfilePage.numReplaces;
	},

	slideShowLink: function ( id ) {
		// window.location = 'index.php?title=Special:UserSlideShow&user=' + __slideshow_user + '&picture=' + ( numReplaces + id );
		window.location = 'Image:' + id;
	},

	doHover: function ( divID ) {
		document.getElementById( divID ).style.backgroundColor = '#4B9AF6';
	},

	endHover: function ( divID ) {
		document.getElementById( divID ).style.backgroundColor = '';
	}
};

$( function () {
	// "Use social profile" / "Use wikitext userpage" button on your own profile
	$( '#profile-toggle-button a' ).on( 'click', function ( e ) {
		e.preventDefault();
		UserProfilePage.changeUserPageType();
	} );

	// "Send message" button on (other users') profile pages
	$( 'div.user-page-message-box-button input[type="submit"]' ).on( 'click', function ( e ) {
		e.preventDefault();
		UserProfilePage.sendMessage();
	} );

	// Board messages' "Delete" link
	$( 'span.user-board-red a' ).on( 'click', function ( e ) {
		e.preventDefault();
		UserProfilePage.deleteMessage( $( this ).data( 'message-id' ) );
	} );
} );
