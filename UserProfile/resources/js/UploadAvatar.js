$( function () {
	var $btn = $( 'input[name="wpUpload"]' );
	$btn.hide(); // Hide by default (T159623)
	$( '#wpUploadFile' ).on( 'change', function () {
		$btn.show();
	} );
} );
