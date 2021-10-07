$( function () {
	var $btn = $( 'input[name="wpUpload"]' );
	$btn.hide(); // Hide by default (T159623)
	$( '#wpUploadFile,#wpUploadFileURL' ).on( 'change', function () {
		$btn.show();
	} );
} );
