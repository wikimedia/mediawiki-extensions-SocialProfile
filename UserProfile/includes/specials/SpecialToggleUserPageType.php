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
 * @license GPL-2.0-or-later
 */

class SpecialToggleUserPage extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'ToggleUserPage' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $params
	 */
	public function execute( $params ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// This feature is only available to logged-in users.
		$this->requireLogin();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// Set headers (robot policy, page title, etc.)
		$this->setHeaders();

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			$dbw = wfGetDB( DB_MASTER );
			$s = $dbw->selectRow(
				'user_profile',
				[ 'up_actor' ],
				[ 'up_actor' => $user->getActorId() ],
				__METHOD__
			);
			if ( $s === false ) {
				$dbw->insert(
					'user_profile',
					[ 'up_actor' => $user->getActorId() ],
					__METHOD__
				);
			}

			$profile = new UserProfile( $user );
			$profile_data = $profile->getProfile();

			// If type is currently 1 (social profile), the user will want to change it to
			// 0 (wikitext page), and vice-versa
			$user_page_type = ( ( $profile_data['user_page_type'] == 1 ) ? 0 : 1 );

			$dbw->update(
				'user_profile',
				/* SET */[
					'up_type' => $user_page_type
				],
				/* WHERE */[
					'up_actor' => $user->getActorId()
				],
				__METHOD__
			);

			UserProfile::clearCache( $user );

			if ( $user_page_type == 1 && !$user->isBlocked() ) {
				self::importUserWiki( $user );
			}

			$title = Title::makeTitle( NS_USER, $user->getName() );
			$out->redirect( $title->getFullURL() );
		} else {
			$out->addHTML( $this->displayForm() );
		}
	}

	/**
	 * Render the confirmation form
	 *
	 * @return string HTML
	 */
	private function displayForm() {
		$form = '<form method="post" name="toggle-user-page-type-form" action="">';
		$form .= $this->msg( 'toggleuserpage-confirm' )->escaped();
		$form .= '<br />';
		$form .= Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() );
		$form .= Html::submitButton( $this->msg( 'confirmable-yes' )->text(), [ 'name' => 'wpSubmit' ] );
		$form .= '</form>';
		return $form;
	}

	/**
	 * If the given user has a User: page and they don't yet have a UserWiki: page,
	 * this method imports the contents of the User: page into the UserWiki:
	 * namespace, allowing the user to use the social profile as their default
	 * User: page while still preserving any and all wikitext the old wikitext
	 * User: page has had.
	 *
	 * Callers should check for things like permissions, read-only status, etc.
	 * before calling this method.
	 *
	 * @param User $user
	 */
	public static function importUserWiki( User $user ) {
		$article = new WikiPage( $user->getUserPage() );
		$contentObject = $article->getContent();
		$user_page_content = ContentHandler::getContentText( $contentObject );

		$user_wiki_title = Title::makeTitle( NS_USER_WIKI, $user->getName() );
		$user_wiki = WikiPage::factory( $user_wiki_title );
		if ( !$user_wiki->exists() ) {
			$user_wiki->doEditContent(
				ContentHandler::makeContent( $user_page_content, $user_wiki_title ),
				'import user wiki'
			);
		}
	}

}
