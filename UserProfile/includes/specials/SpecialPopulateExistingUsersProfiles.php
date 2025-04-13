<?php
/**
 * A special page for initializing social profiles for existing wikis
 * This is to be run once if you want to preserve existing user pages at User:xxx (otherwise
 * they will be moved to UserWiki:xxx)
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license GPL-2.0-or-later
 */

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;

class SpecialPopulateUserProfiles extends SpecialPage {

	public function __construct() {
		parent::__construct( 'PopulateUserProfiles', 'populate-user-profiles' );
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

		// Make sure user has the correct permissions
		$this->checkPermissions();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, they don't need to access this page
		$block = $user->getBlock();
		if ( $block ) {
			throw new UserBlockedError( $block );
		}

		// Set the page title, robot policy, etc.
		$this->setHeaders();

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			$count = $this->populateProfiles();

			// @todo Handle $count === 0 more gracefully
			$out->addHTML( $this->msg( 'populate-user-profile-done' )->numParams( $count )->parse() );
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
		$form = '<form method="post" name="populate-profiles-form" action="">';
		$form .= $this->msg( 'populateuserprofiles-confirm' )->escaped();
		$form .= '<br />';
		$form .= Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() );
		$form .= Html::submitButton( $this->msg( 'htmlform-submit' )->text(), [ 'name' => 'wpSubmit' ] );
		$form .= '</form>';
		return $form;
	}

	/**
	 * Get all users who have a User: page and populate the user_profile DB table
	 * with information about them, namely their actor ID and that they prefer
	 * a wikitext user page.
	 *
	 * @return int Amount of profiles populated
	 */
	private function populateProfiles() {
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$res = $dbw->select(
			'page',
			[ 'page_title' ],
			[ 'page_namespace' => NS_USER ],
			__METHOD__
		);

		$count = 0; // To avoid an annoying PHP notice

		foreach ( $res as $row ) {
			$userBeingProcessed = User::newFromName( $row->page_title );
			if ( !$userBeingProcessed ) {
				continue;
			}

			$s = $dbw->selectRow(
				'user_profile',
				[ 'up_actor' ],
				[ 'up_actor' => $userBeingProcessed->getActorId() ],
				__METHOD__
			);
			if ( $s === false ) {
				$dbw->insert(
					'user_profile',
					[
						'up_actor' => $userBeingProcessed->getActorId(),
						'up_type' => 0
					],
					__METHOD__
				);
				$count++;
			}
		}

		return $count;
	}
}
