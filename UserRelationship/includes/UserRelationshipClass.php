<?php

use MediaWiki\MediaWikiServices;

/**
 * Functions for managing relationship data
 */
class UserRelationship {
	/** @var User */
	public $user;
	/** @var int */
	public $user_id;
	/** @var string */
	public $user_name;

	/**
	 * @param User|string $username User object (preferred) or user name (legacy b/c)
	 */
	public function __construct( $username ) {
		if ( $username instanceof User ) {
			$this->user = $username;
		} else {
			$this->user = User::newFromName( $username );
		}
		$this->user_name = $this->user->getName();
		$this->user_id = $this->user->getId();
	}

	/**
	 * Add a relationship request to the database.
	 *
	 * @param User $userTo Recipient of the relationship request
	 * @param int $type
	 * - 1 for friend request
	 * - 2 (or anything else than 1) for foe request
	 * @param string|null $message User-supplied message
	 * to the recipient; may be empty
	 * @param bool $email Send out email to the recipient of the request?
	 * @return int ID of the new relationship request
	 */
	public function addRelationshipRequest( $userTo, $type, $message, $email = true ) {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'user_relationship_request',
			[
				'ur_actor_from' => $this->user->getActorId(),
				'ur_actor_to' => $userTo->getActorId(),
				'ur_type' => $type,
				'ur_message' => $message,
				'ur_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) )
			], __METHOD__
		);
		$requestId = $dbw->insertId();

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$requestCount = new RelationshipRequestCount( $cache, $userTo );
		$requestCount->setType( $type )->clear();

		if ( $email ) {
			$this->sendRelationshipRequestEmail( $userTo, $type );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$userFrom = User::newFromId( $this->user_id );

			EchoEvent::create( [
				'type' => 'social-rel-add',
				'agent' => $userFrom,
				'title' => $userFrom->getUserPage(),
				'extra' => [
					'target' => $userTo->getId(),
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
	 * @param User $userTo Recipient User object
	 * @param int $type
	 * - 1 for friend request
	 * - 2 (or anything else than 1) for foe request
	 */
	public function sendRelationshipRequestEmail( $userTo, $type ) {
		$userTo->load();

		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ?
			$userOptionsLookup->getBoolOption( $userTo, 'echo-subscriptions-email-social-rel' ) :
			$userOptionsLookup->getIntOption( $userTo, 'notifyfriendrequest', 1 );
		if ( $userTo->getEmail() && $wantsEmail ) {
			$requestLink = SpecialPage::getTitleFor( 'ViewRelationshipRequests' );
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );

			if ( trim( $userTo->getRealName() ) ) {
				$name = $userTo->getRealName();
			} else {
				$name = $userTo->getName();
			}
			$userFrom = $this->user->getName();

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

			$userTo->sendMail( $subject, $body );
		}
	}

	/**
	 * Send an e-mail to the user whose user ID is $userIdTo about a new user
	 * relationship.
	 *
	 * @param User $user The recipient of the e-mail
	 * @param int $type
	 * - 1 for friend
	 * - 2 (or anything else but 1) for foe
	 */
	public function sendRelationshipAcceptEmail( $user, $type ) {
		$user->load();

		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ?
			$userOptionsLookup->getBoolOption( $user, 'echo-subscriptions-email-social-rel' ) :
			$userOptionsLookup->getIntOption( $user, 'notifyfriendrequest', 1 );
		if ( $user->getEmail() && $wantsEmail ) {
			$userFrom = $this->user;
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );

			if ( trim( $user->getRealName() ) ) {
				$name = $user->getRealName();
			} else {
				$name = $user->getName();
			}

			if ( $type == 1 ) {
				$subject = wfMessage( 'friend_accept_subject', $userFrom->getName() )->text();
				$body = [
					'html' => wfMessage(
						'friend_accept_body_html',
						$name,
						$userFrom->getName()
					)->parse(),
					'text' => wfMessage(
						'friend_accept_body',
						$name,
						$userFrom->getName(),
						$userFrom->getUserPage()->getFullURL(),
						$updateProfileLink->getFullURL()
					)->text()
				];
			} else {
				$subject = wfMessage( 'foe_accept_subject', $userFrom )->text();
				$body = [
					'html' => wfMessage(
						'foe_accept_body_html',
						$name,
						$userFrom->getName()
					)->parse(),
					'text' => wfMessage(
						'foe_accept_body',
						$name,
						$userFrom->getName(),
						$userFrom->getUserPage()->getFullURL(),
						$updateProfileLink->getFullURL()
					)->text()
				];
			}

			$user->sendMail( $subject, $body );
		}
	}

	/**
	 * Send an e-mail to the given user about a removed relationship.
	 *
	 * @param User $user The recipient of the e-mail
	 * @param int $type
	 * - 1 for friend
	 * - 2 (or anything else but 1) for foe
	 */
	public function sendRelationshipRemoveEmail( $user, $type ) {
		$user->load();

		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ? $user->getBoolOption( 'echo-subscriptions-email-social-rel' ) : $user->getIntOption( 'notifyfriendrequest', 1 );
		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$userFrom = $this->user;
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );

			if ( trim( $user->getRealName() ) ) {
				$name = $user->getRealName();
			} else {
				$name = $user->getName();
			}

			if ( $type == 1 ) {
				$subject = wfMessage( 'friend_removed_subject', $userFrom->getName() )->text();
				$body = [
					'html' => wfMessage(
						'friend_removed_body_html',
						$name,
						$userFrom->getName()
					)->parse(),
					'text' => wfMessage(
						'friend_removed_body',
						$name,
						$userFrom->getName(),
						$userFrom->getUserPage()->getFullURL(),
						$updateProfileLink->getFullURL()
					)->text()
				];
			} else {
				$subject = wfMessage( 'foe_removed_subject', $userFrom->getName() )->text();
				$body = [
					'html' => wfMessage(
						'foe_removed_body_html',
						$name,
						$userFrom->getName()
					)->parse(),
					'text' => wfMessage(
						'foe_removed_body',
						$name,
						$userFrom->getName(),
						$userFrom->getUserPage()->getFullURL(),
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
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_relationship_request',
			[ 'ur_actor_from', 'ur_type' ],
			[ 'ur_id' => $relationshipRequestId ],
			__METHOD__
		);

		if ( $s ) {
			$userFrom = User::newFromActorId( $s->ur_actor_from );
			$ur_type = $s->ur_type;

			if ( self::getUserRelationshipByID( $this->user, $userFrom ) > 0 ) {
				return '';
			}

			$dbw->insert(
				'user_relationship',
				[
					'r_actor' => $this->user->getActorId(),
					'r_actor_relation' => $userFrom->getActorId(),
					'r_type' => $ur_type,
					'r_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) )
				],
				__METHOD__
			);

			$dbw->insert(
				'user_relationship',
				[
					'r_actor' => $userFrom->getActorId(),
					'r_actor_relation' => $this->user->getActorId(),
					'r_type' => $ur_type,
					'r_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) )
				],
				__METHOD__
			);

			$stats = new UserStatsTrack( $this->user->getActorId() );
			if ( $ur_type == 1 ) {
				$stats->incStatField( 'friend' );
			} else {
				$stats->incStatField( 'foe' );
			}

			$stats = new UserStatsTrack( $userFrom->getActorId() );
			if ( $ur_type == 1 ) {
				$stats->incStatField( 'friend' );
			} else {
				$stats->incStatField( 'foe' );
			}

			if ( $email ) {
				$this->sendRelationshipAcceptEmail( $userFrom, $ur_type );
			}

			// Purge caches
			$cache->delete( $cache->makeKey( 'relationship', 'profile', 'actor_id', "{$this->user->getActorId()}-{$ur_type}" ) );
			$cache->delete( $cache->makeKey( 'relationship', 'profile', 'actor_id', "{$userFrom->getActorId()}-{$ur_type}" ) );

			if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
				EchoEvent::create( [
					'type' => 'social-rel-accept',
					'agent' => $this->user,
					'title' => $this->user->getUserPage(),
					'extra' => [
						'target' => $userFrom->getId(),
						'from' => $this->user_id,
						'rel_type' => $ur_type
					]
				] );
			}

			// Hooks (for Semantic SocialProfile mostly)
			if ( $ur_type == 1 ) {
				MediaWikiServices::getInstance()->getHookContainer()->run( 'NewFriendAccepted', [ $userFrom, $this->user ] );
			} else {
				MediaWikiServices::getInstance()->getHookContainer()->run( 'NewFoeAccepted', [ $userFrom, $this->user ] );
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Remove a relationship between two users and clear caches afterwards.
	 *
	 * @param User $user1 User who is removing a friend/foe
	 * @param User $user2 The friend/foe being removed
	 */
	public function removeRelationship( $user1, $user2 ) {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		if (
			$user1->getActorId() != $this->user->getActorId() &&
			$user2->getActorId() != $this->user->getActorId()
		) {
			return; // only logged in user should be able to delete
		}

		// must delete record for each user involved in relationship
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'user_relationship',
			[ 'r_actor' => $user1->getActorId(), 'r_actor_relation' => $user2->getActorId() ],
			__METHOD__
		);
		$dbw->delete(
			'user_relationship',
			[ 'r_actor' => $user2->getActorId(), 'r_actor_relation' => $user1->getActorId() ],
			__METHOD__
		);

		$cache->delete( $cache->makeKey( 'relationship', 'profile', 'actor_id', "{$user1->getActorId()}-1" ) );
		$cache->delete( $cache->makeKey( 'relationship', 'profile', 'actor_id', "{$user2->getActorId()}-1" ) );

		$cache->delete( $cache->makeKey( 'relationship', 'profile', 'actor_id', "{$user1->getActorId()}-2" ) );
		$cache->delete( $cache->makeKey( 'relationship', 'profile', 'actor_id', "{$user2->getActorId()}-2" ) );

		// RelationshipRemovedByUserID hook
		MediaWikiServices::getInstance()->getHookContainer()->run( 'RelationshipRemovedByUserID', [ $user1, $user2 ] );

		// Update social statistics for both users
		$stats = new UserStatsTrack( $user1->getActorId() );
		$stats->updateRelationshipCount( 1 );
		$stats->updateRelationshipCount( 2 );
		$stats->clearCache();

		$stats = new UserStatsTrack( $user2->getActorId() );
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
		$request = $this->getRequest( $id );
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$requestCount = new RelationshipRequestCount( $cache, $this->user );
		$requestCount->setType( $request[0]['rel_type'] )->clear();

		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'user_relationship_request',
			[ 'ur_id' => $id ],
			__METHOD__
		);
	}

	/**
	 * @param int $relationshipRequestId Relationship request ID number
	 * @param int $status
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
			[ 'ur_actor_to' ],
			[ 'ur_id' => $relationshipRequestId ],
			__METHOD__
		);
		if ( $s !== false ) {
			if ( $this->user->getActorId() == $s->ur_actor_to ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param User $user1
	 * @param User $user2
	 * @return int|bool false
	 */
	public static function getUserRelationshipByID( $user1, $user2 ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_relationship',
			[ 'r_type' ],
			[ 'r_actor' => $user1->getActorId(), 'r_actor_relation' => $user2->getActorId() ],
			__METHOD__
		);
		if ( $s !== false ) {
			return $s->r_type;
		} else {
			return false;
		}
	}

	/**
	 * @param User $user1 The recipient of the request
	 * @param User $user2 The sender of the request
	 * @return bool
	 */
	public static function userHasRequestByID( $user1, $user2 ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_relationship_request',
			[ 'ur_type' ],
			[
				'ur_actor_to' => $user1->getActorId(),
				'ur_actor_from' => $user2->getActorId(),
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
			[ 'ur_id', 'ur_actor_from', 'ur_type', 'ur_message', 'ur_date' ],
			[ 'ur_id' => $id ],
			__METHOD__
		);

		$request = [];
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
				'timestamp' => $row->ur_date,
				'actor_from' => $row->ur_actor_from
			];
		}

		return $request;
	}

	/**
	 * Get the relationship actor IDs for the current user.
	 *
	 * @param int $type
	 * - 1 for friends
	 * - 2 (or anything else but 1) for foes
	 * @return array Array of actor ID numbers
	 */
	public function getRelationshipIDs( $type ) {
		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select(
			[ 'user_relationship', 'actor' ],
			[ 'r_id', 'r_actor_relation', 'r_date' ],
			[ 'r_actor' => $this->user->getActorId(), 'r_type' => $type ],
			__METHOD__,
			[ 'ORDER BY' => 'actor_name' ],
			[ 'actor' => [ 'JOIN', 'r_actor_relation = actor_id' ] ]
		);

		$rel = [];
		foreach ( $res as $row ) {
			$rel[] = $row->r_actor_relation;
		}

		return $rel;
	}
}
