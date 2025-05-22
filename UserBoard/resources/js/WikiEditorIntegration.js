/*
 * JavaScript for WikiEditor toolbar on Special:UserBoard, Special:SendBoardBlast and social profile pages
 * Adapted from CreateAPage
 */
$( () => {
	// @todo FIXME: using mw.loader.moduleRegistry like this feels like a filthy hack
	// *but* it works...
	const dialogsConfig = mw.loader.moduleRegistry[ 'ext.wikiEditor' ].packageExports[ 'jquery.wikiEditor.dialogs.config.js' ],
		// Special:UserBoard, Special:SendBoardBlast and social profile pages use the same selector, so this works,
		// but we really should change the ID to something a little bit less generic one of these days...
		$textarea = $( '#message' );

	dialogsConfig.replaceIcons( $textarea );

	// Add dialogs module
	$textarea.wikiEditor(
		'addModule',
		dialogsConfig.getDefaultConfig()
	);
	$textarea.wikiEditor(
		'addModule',
		mw.loader.moduleRegistry[ 'ext.wikiEditor' ].packageExports[ 'jquery.wikiEditor.toolbar.config.js' ]
	);
} );
