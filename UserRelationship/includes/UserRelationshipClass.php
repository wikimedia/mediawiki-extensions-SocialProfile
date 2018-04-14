<?php

/**
 * Functions for managing relationship data
 */
class UserRelationship {
	public $user_id;
	public $user_name;

	public function __construct( $username ) {
		$title1 = Title::newFromDBkey( $username );
		$this->user_name = $title1->getText();
		$this->user_id = User::idFromName( $this->user_name );
	}

	/**
	 * Add a relationship request to the database.
	 *
	 * @param string $user_to User name of the
	 * recipient of the relationship request
	 * @param int $type
	 * - 1 for friend request
	 * - 2 (or anything else than 1) for foe request
	 * @param string|null $message User-supplied message
	 * to the recipient; may be empty
	 * @param bool $email Send out email to the recipient of the request?
	 * @return int ID of the new relationship request
	 */
	public function addRelationshipRequest( $userTo, $type, $message, $email = true ) {
		global $wgMemc;

		$userIdTo = User::idFromName( $userTo );
		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'user_relationship_request',
			[
				'ur_user_id_from' => $this->user_id,
				'ur_user_name_from' => $this->user_name,
				'ur_user_id_to' => $userIdTo,
				'ur_user_name_to' => $userTo,
				'ur_type' => $type,
				'ur_message' => $message,
				'ur_date' => date( 'Y-m-d H:i:s' )
			], __METHOD__
		);
		$requestId = $dbw->insertId();

		$requestCount = new RelationshipRequestCount( $wgMemc, $userIdTo );
		$requestCount->setType( $type )->increase();

		if ( $email ) {
			$this->sendRelationshipRequestEmail( $userIdTo, $this->user_name, $type );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$userFrom = User::newFromId( $this->user_id );

			EchoEvent::create( [
				'type' => 'social-rel-add',
				'agent' => $userFrom,
				'title' => $userFrom->getUserPage(),
				'extra' => [
					'target' => $userIdTo,
					'from' => $this->user_id,
					'rel_type' => $type,
					'message' => $message
				]
			] );
		}

		return $requestId;
	}

	/**
	 * Send e-mail about a new relationship request to the user whose user ID
	 * is $userIdTo if they have opted in for these notification e-mails.
	 *
	 * @param int $userIdTo User ID of the recipient
	 * @param string $userFrom Name of the user who requested the relationship
	 * @param int $type
	 * - 1 for friend request
	 * - 2 (or anything else than 1) for foe request
	 */
	public function sendRelationshipRequestEmail( $userIdTo, $userFrom, $type ) {
		$user = User::newFromId( $userIdTo );
		$user->loadFromDatabase();

		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ? $user->getBoolOption( 'echo-subscriptions-email-social-rel' ) : $user->getIntOption( 'notifyfriendrequest', 1 );
		if ( $user->getEmail() && $wantsEmail ) {
			$requestLink = SpecialPage::getTitleFor( 'ViewRelationshipRequests' );
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );

			if ( trim( $user->getRealName() ) ) {
				$name = $user->getRealName();
			} else {
				$name = $user->getName();
			}

			if ( $type == 1 ) {
				$subject = wfMessage( 'friend_request_subject', $userFrom )->text();
				$body = [
					'html' => wfMessage( 'friend_request_body_html',
						$name,
						$userFrom
					)->parse(),
					'text' => wfMessage( 'friend_request_body',
						$name,
						$userFrom,
						$requestLink->getFullURL(),
						$updateProfileLink->getFullURL()
					)->text()
				];
			} else {
				$subject = wfMessage( 'foe_request_subject', $userFrom )->text();
				$body = [
					'html' => wfMessage( 'foe_request_body_html',
						$name,
						$userFrom
					)->parse(),
					'text' => wfMessage( 'foe_request_body',
						$name,
						$userFrom,
						$requestLink->getFullURL(),
						$updateProfileLink->getFullURL()
					)->text()
				];
			}

			$user->sendMail( $subject, $body );
		}
	}

	/**
	 * Send an e-mail to the user whose user ID is $userIdTo about a new user
	 * relationship.
	 *
	 * @param int $userIdTo User ID of the recipient of the e-mail
	 * @param string $userFrom Name of the user who removed the relationship
	 * @param int $type
	 * - 1 for friend
	 * - 2 (or anything else but 1) for foe
	 */
	public function sendRelationshipAcceptEmail( $userIdTo, $userFrom, $type ) {
		$user = User::newFromId( $userIdTo );
		$user->loadFromDatabase();

		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ? $user->getBoolOption( 'echo-subscriptions-email-social-rel' ) : $user->getIntOption( 'notifyfriendrequest', 1 );
		if ( $user->getEmail() && $wantsEmail ) {
			$userLink = Title::makeTitle( NS_USER, $userFrom );
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );

			if ( trim( $user->getRealName() ) ) {
				$name = $user->getRealName();
			} else {
				$name = $user->getName();
			}

			if ( $type == 1 ) {
				$subject = wfMessage( 'friend_accept_subject', $userFrom )->text();
				$body = [
					'html' => wfMessage( 'friend_accept_body_html',
						$name,
						$userFrom
					)->parse(),
					'text' => wfMessage( 'friend_accept_body',
						$name,
						$userFrom,
						$userLink->getFullURL(),
						$updateProfileLink->getFullURL()
					)->text()
				];
			} else {
				$subject = wfMessage( 'foe_accept_subject', $userFrom )->text();
				$body = [
					'html' => wfMessage( 'foe_accept_body_html',
						$name,
						$userFrom
					)->parse(),
					'text' => wfMessage( 'foe_accept_body',
						$name,
						$userFrom,
						$userLink->getFullURL(),
						$updateProfileLink->getFullURL()
					)->text()
				];
			}

			$user->sendMail( $subject, $body );
		}
	}

	/**
	 * Send an e-mail to the user whose user ID is $userIdTo about a removed
	 * relationship.
	 *
	 * @param string $userIdTo User ID of the recipient of the e-mail
	 * @param string $userFrom Name of the user who removed the relationship
	 * @param int $type
	 * - 1 for friend
	 * - 2 (or anything else but 1) for foe
	 */
	public function sendRelationshipRemoveEmail( $userIdTo, $userFrom, $type ) {
		$user = User::newFromId( $userIdTo );
		$user->loadFromDatabase();

		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ? $user->getBoolOption( 'echo-subscriptions-email-social-rel' ) : $user->getIntOption( 'notifyfriendrequest', 1 );
		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$userLink = Title::makeTitle( NS_USER, $userFrom );
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );

			if ( trim( $user->getRealName() ) ) {
				$name = $user->getRealName();
			} else {
				$name = $user->getName();
			}

			if ( $type == 1 ) {
				$subject = wfMessage( 'friend_removed_subject', $userFrom )->text();
				$body = [
					'html' => wfMessage( 'friend_removed_body_html',
						$name,
						$userFrom
					)->parse(),
					'text' => wfMessage( 'friend_removed_body',
						$name,
						$userFrom,
						$userLink->getFullURL(),
						$updateProfileLink->getFullURL()
					)->text()
				];
			} else {
				$subject = wfMessage( 'foe_removed_subject', $userFrom )->text();
				$body = [
					'html' => wfMessage( 'foe_removed_body_html',
						$name,
						$userFrom
					)->parse(),
					'text' => wfMessage( 'foe_removed_body',
						$name,
						$userFrom,
						$userLink->getFullURL(),
						$updateProfileLink->getFullURL()
					)->text()
				];
			}

			$user->sendMail( $subject, $body );
		}
	}

	/**
	 * Add a new relationship to the database.
	 *
	 * @param int $relationshipRequestId Relationship request ID number
	 * @param bool $email Send out email to the recipient of the request?
	 * @return bool True if successful, otherwise false
	 */
	public function addRelationship( $relationshipRequestId, $email = true ) {
		global $wgMemc;

		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_relationship_request',
			[ 'ur_user_id_from', 'ur_user_name_from', 'ur_type' ],
			[ 'ur_id' => $relationshipRequestId ],
			__METHOD__
		);

		if ( $s == true ) {
			$ur_user_id_from = $s->ur_user_id_from;
			$ur_user_name_from = $s->ur_user_name_from;
			$ur_type = $s->ur_type;

			if ( self::getUserRelationshipByID( $this->user_id, $ur_user_id_from ) > 0 ) {
				return '';
			}

			$dbw->insert(
				'user_relationship',
				[
					'r_user_id' => $this->user_id,
					'r_user_name' => $this->user_name,
					'r_user_id_relation' => $ur_user_id_from,
					'r_user_name_relation' => $ur_user_name_from,
					'r_type' => $ur_type,
					'r_date' => date( 'Y-m-d H:i:s' )
				],
				__METHOD__
			);

			$dbw->insert(
				'user_relationship',
				[
					'r_user_id' => $ur_user_id_from,
					'r_user_name' => $ur_user_name_from,
					'r_user_id_relation' => $this->user_id,
					'r_user_name_relation' => $this->user_name,
					'r_type' => $ur_type,
					'r_date' => date( 'Y-m-d H:i:s' )
				],
				__METHOD__
			);

			$stats = new UserStatsTrack( $this->user_id, $this->user_name );
			if ( $ur_type == 1 ) {
				$stats->incStatField( 'friend' );
			} else {
				$stats->incStatField( 'foe' );
			}

			$stats = new UserStatsTrack( $ur_user_id_from, $ur_user_name_from );
			if ( $ur_type == 1 ) {
				$stats->incStatField( 'friend' );
			} else {
				$stats->incStatField( 'foe' );
			}

			if ( $email ) {
				$this->sendRelationshipAcceptEmail( $ur_user_id_from, $this->user_name, $ur_type );
			}

			// Purge caches
			$wgMemc->delete( $wgMemc->makeKey( 'relationship', 'profile', "{$this->user_id}-{$ur_type}" ) );
			$wgMemc->delete( $wgMemc->makeKey( 'relationship', 'profile', "{$ur_user_id_from}-{$ur_type}" ) );

			if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
				$userFrom = User::newFromId( $this->user_id );

				EchoEvent::create( [
					'type' => 'social-rel-accept',
					'agent' => $userFrom,
					'title' => $userFrom->getUserPage(),
					'extra' => [
						'target' => $ur_user_id_from,
						'from' => $this->user_id,
						'rel_type' => $ur_type
					]
				] );
			}

			// Hooks (for Semantic SocialProfile mostly)
			if ( $ur_type == 1 ) {
				Hooks::run( 'NewFriendAccepted', [ $ur_user_name_from, $this->user_name ] );
			} else {
				Hooks::run( 'NewFoeAccepted', [ $ur_user_name_from, $this->user_name ] );
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Remove a relationship between two users and clear caches afterwards.
	 *
	 * @param int $user1 User ID of the first user
	 * @param int $user2 User ID of the second user
	 */
	public function removeRelationshipByUserID( $user1, $user2 ) {
		global $wgUser, $wgMemc;

		if ( $user1 != $wgUser->getId() && $user2 != $wgUser->getId() ) {
			return false; // only logged in user should be able to delete
		}

		// must delete record for each user involved in relationship
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'user_relationship',
			[ 'r_user_id' => $user1, 'r_user_id_relation' => $user2 ],
			__METHOD__
		);
		$dbw->delete(
			'user_relationship',
			[ 'r_user_id' => $user2, 'r_user_id_relation' => $user1 ],
			__METHOD__
		);

		$wgMemc->delete( $wgMemc->makeKey( 'relationship', 'profile', "{$user1}-1" ) );
		$wgMemc->delete( $wgMemc->makeKey( 'relationship', 'profile', "{$user2}-1" ) );

		$wgMemc->delete( $wgMemc->makeKey( 'relationship', 'profile', "{$user1}-2" ) );
		$wgMemc->delete( $wgMemc->makeKey( 'relationship', 'profile', "{$user2}-2" ) );

		// RelationshipRemovedByUserID hook
		Hooks::run( 'RelationshipRemovedByUserID', [ $user1, $user2 ] );

		// Update social statistics for both users
		$stats = new UserStatsTrack( $user1, '' );
		$stats->updateRelationshipCount( 1 );
		$stats->updateRelationshipCount( 2 );
		$stats->clearCache();

		$stats = new UserStatsTrack( $user2, '' );
		$stats->updateRelationshipCount( 1 );
		$stats->updateRelationshipCount( 2 );
		$stats->clearCache();
	}

	/**
	 * Delete a user relationship request from the database.
	 *
	 * @param int $id Relationship request ID number
	 */
	public function deleteRequest( $id ) {
		global $wgMemc;

		$request = $this->getRequest( $id );
		$requestCount = new RelationshipRequestCount( $wgMemc, $this->user_id );
		$requestCount->setType( $request[0]['rel_type'] )->decrease();

		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'user_relationship_request',
			[ 'ur_id' => $id ],
			__METHOD__
		);
	}

	/**
	 * @param int $relationshipRequestId Relationship request ID number
	 * @param $status
	 */
	public function updateRelationshipRequestStatus( $relationshipRequestId, $status ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'user_relationship_request',
			/* SET */[ 'ur_status' => $status ],
			/* WHERE */[ 'ur_id' => $relationshipRequestId ],
			__METHOD__
		);
	}

	/**
	 * Make sure that there is a pending user relationship request with the
	 * given ID.
	 *
	 * @param int $relationshipRequestId Relationship request ID number
	 * @return bool
	 */
	public function verifyRelationshipRequest( $relationshipRequestId ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_relationship_request',
			[ 'ur_user_id_to' ],
			[ 'ur_id' => $relationshipRequestId ],
			__METHOD__
		);
		if ( $s !== false ) {
			if ( $this->user_id == $s->ur_user_id_to ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param int $user1
	 * @param int $user2
	 * @return int|bool false
	 */
	static function getUserRelationshipByID( $user1, $user2 ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_relationship',
			[ 'r_type' ],
			[ 'r_user_id' => $user1, 'r_user_id_relation' => $user2 ],
			__METHOD__
		);
		if ( $s !== false ) {
			return $s->r_type;
		} else {
			return false;
		}
	}

	/**
	 * @param int $user1 User ID of the recipient of the request
	 * @param int $user2 User ID of the sender of the request
	 * @return bool
	 */
	static function userHasRequestByID( $user1, $user2 ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_relationship_request',
			[ 'ur_type' ],
			[
				'ur_user_id_to' => $user1,
				'ur_user_id_from' => $user2,
				'ur_status' => 0
			],
			__METHOD__
		);
		if ( $s === false ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Get an individual user relationship request via its ID.
	 *
	 * @param int $id Relationship request ID
	 * @return array Array containing relationship request info,
	 * such as its ID, type, requester, etc.
	 */
	public function getRequest( $id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'user_relationship_request',
			[
				'ur_id', 'ur_user_id_from', 'ur_user_name_from', 'ur_type',
				'ur_message', 'ur_date'
			],
			[ 'ur_id' => $id ],
			__METHOD__
		);

		foreach ( $res as $row ) {
			if ( $row->ur_type == 1 ) {
				$typeName = 'Friend';
			} else {
				$typeName = 'Foe';
			}
			$request[] = [
				'id' => $row->ur_id,
				'rel_type' => $row->ur_type,
				'type' => $typeName,
				'timestamp' => ( $row->ur_date ),
				'user_id_from' => $row->ur_user_id_from,
				'user_name_from' => $row->ur_user_name_from
			];
		}

		return $request;
	}

	/**
	 * Get the relationship IDs for the current user.
	 *
	 * @param int $type
	 * - 1 for friends
	 * - 2 (or anything else but 1) for foes
	 * @return array Array of relationship ID numbers
	 */
	public function getRelationshipIDs( $type ) {
		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select(
			'user_relationship',
			[
				'r_id', 'r_user_id_relation',
				'r_user_name_relation', 'r_date'
			],
			[ 'r_user_id' => $this->user_id, 'r_type' => $type ],
			__METHOD__,
			[ 'ORDER BY' => 'r_user_name_relation' ]
		);

		$rel = [];
		foreach ( $res as $row ) {
			$rel[] = $row->r_user_id_relation;
		}

		return $rel;
	}
}
