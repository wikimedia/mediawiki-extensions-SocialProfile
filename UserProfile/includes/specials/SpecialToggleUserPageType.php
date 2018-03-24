<?php
/**
 * A special page for updating a user's userpage preference
 * (If they want a wiki user page or social profile user page
 * when someone browses to User:xxx)
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class SpecialToggleUserPage extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'ToggleUserPage' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $params
	 */
	public function execute( $params ) {
		global $wgMemc;

		$out = $this->getOutput();
		$user = $this->getUser();

		// This feature is only available to logged-in users.
		$this->requireLogin();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// set header (robot policy, page title, etc)
		$this->setHeaders();

		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_profile',
			array( 'up_user_id' ),
			array( 'up_user_id' => $user->getId() ),
			__METHOD__
		);
		if ( $s === false ) {
			$dbw->insert(
				'user_profile',
				array( 'up_user_id' => $user->getId() ),
				__METHOD__
			);
		}

		$profile = new UserProfile( $user->getName() );
		$profile_data = $profile->getProfile();

		$user_page_type = ( ( $profile_data['user_page_type'] == 1 ) ? 0 : 1 );

		$dbw->update(
			'user_profile',
			/* SET */array(
				'up_type' => $user_page_type
			),
			/* WHERE */array(
				'up_user_id' => $user->getId()
			), __METHOD__
		);

		$key = $wgMemc->makeKey( 'user', 'profile', 'info', $user->getId() );
		$wgMemc->delete( $key );

		if ( $user_page_type == 1 && !$user->isBlocked() ) {
			$user_page = Title::makeTitle( NS_USER, $user->getName() );
			$article = new WikiPage( $user_page );
			$contentObject = $article->getContent();
			$user_page_content = ContentHandler::getContentText( $contentObject );

			$user_wiki_title = Title::makeTitle( NS_USER_WIKI, $user->getName() );
			$user_wiki = new Article( $user_wiki_title );
			if ( !$user_wiki->exists() ) {
				$user_wiki->doEditContent(
					ContentHandler::makeContent( $user_page_content, $user_wiki_title ),
					'import user wiki'
				);
			}
		}
		$title = Title::makeTitle( NS_USER, $user->getName() );
		$out->redirect( $title->getFullURL() );
	}
}
