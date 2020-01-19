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
		$user = $this->getUser();

		// Make sure user has the correct permissions
		$this->checkPermissions();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, they don't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// set headers
		$this->setHeaders();

		$dbw = wfGetDB( DB_MASTER );
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

		$out->addHTML( $this->msg( 'populate-user-profile-done' )->numParams( $count )->parse() );
	}
}
