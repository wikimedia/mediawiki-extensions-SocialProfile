/**
 * JavaScript functions used by UserProfile
 */
var posted = 0;
function send_message(){
	if( document.getElementById('message').value && !posted ){
		posted = 1;
		sajax_request_type = 'POST';
		sajax_do_call( 'wfSendBoardMessage', [
			document.getElementById('user_name_to').value,
			encodeURIComponent( document.getElementById('message').value ),
			document.getElementById('message_type').value,
			10 ], function( originalRequest ){
				document.getElementById('user-page-board').innerHTML = originalRequest.responseText;
				posted = 0;
				document.getElementById('message').value = '';
			}
		);
	}
}

function delete_message( id ){
	if( confirm( 'Are you sure you want to delete this message?' ) ){
		sajax_request_type = 'POST';
		sajax_do_call( 'wfDeleteBoardMessage', [ id ], function( originalRequest ){
			window.location.reload();
		} );
	}
}

var numReplaces = 0;
var replaceID = 0;
var replaceSrc = '';
var oldHtml = '';

function showUploadFrame(){
	new YAHOO.widget.Effects.Show('upload-container');
}

function uploadError( message ){
	document.getElementById('mini-gallery-' + replaceID).innerHTML = oldHtml;
	document.getElementById('upload-frame-errors').innerHTML = message;
	document.getElementById('imageUpload-frame').src = 'index.php?title=Special:MiniAjaxUpload&wpThumbWidth=75';

	new YAHOO.widget.Effects.Show('upload-container');
}

function textError( message ){
	document.getElementById('upload-frame-errors').innerHTML = message;
	new YAHOO.widget.Effects.Show('upload-frame-errors');
}

function completeImageUpload(){
	new YAHOO.widget.Effects.Hide('upload-frame-errors');
	document.getElementById('upload-frame-errors').innerHTML = '';
	oldHtml = document.getElementById('mini-gallery-' + replaceID).innerHTML;

	for( x = 7; x > 0; x-- ){
		document.getElementById('mini-gallery-' + (x) ).innerHTML = document.getElementById('mini-gallery-' + (x-1) ).innerHTML.replace('slideShowLink('+(x-1)+')','slideShowLink('+(x)+')');
	}
	document.getElementById('mini-gallery-0').innerHTML = '<a><img height="75" width="75" src="http://images.wikia.com/common/wikiany/images/ajax-loader-white.gif" alt="" /></a>';

	//new YAHOO.widget.Effects.Hide('mini-gallery-nopics');
	if( document.getElementById( 'no-pictures-containers' ) ) {
		new YAHOO.widget.Effects.Hide( 'no-pictures-containers' );
	}
	new YAHOO.widget.Effects.Show( 'pictures-containers' );
}

function uploadComplete( imgSrc, imgName, imgDesc ){
	replaceSrc = imgSrc;

	document.getElementById('upload-frame-errors').innerHTML = '';

	//document.getElementById('imageUpload-frame').onload = function(){
		var idOffset = -1 - numReplaces;
		//$D.addClass('mini-gallery-0','mini-gallery');
		//document.getElementById('mini-gallery-0').innerHTML = '<a href=\"javascript:slideShowLink(' + idOffset + ')\">' + replaceSrc + '</a>';
		document.getElementById('mini-gallery-0').innerHTML = '<a href=\"' + __image_prefix + imgName + '\">' + replaceSrc + '</a>';

		//replaceID = (replaceID == 7) ? 0 : (replaceID + 1);
		numReplaces += 1;

	//}
	//if (document.getElementById('imageUpload-frame').captureEvents) document.getElementById('imageUpload-frame').captureEvents(Event.LOAD);

	document.getElementById('imageUpload-frame').src = 'index.php?title=Special:MiniAjaxUpload&wpThumbWidth=75&extra=' + numReplaces;
}

function slideShowLink( id ){
	//window.location = 'index.php?title=Special:UserSlideShow&user=' + __slideshow_user + '&picture=' + ( numReplaces + id );
	window.location = 'Image:' + id;
}

function doHover( divID ) {
	$El(divID).setStyle('backgroundColor', '#4B9AF6');
}

function endHover( divID ){
	$El(divID).setStyle('backgroundColor', '');
}