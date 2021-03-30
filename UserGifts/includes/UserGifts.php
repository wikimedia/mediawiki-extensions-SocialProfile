<?php

use MediaWiki\MediaWikiServices;

/**
 * UserGifts class
 * @todo document
 */
class UserGifts {

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
	 * Sends a gift to the specified user.
	 *
	 * @param User $user_to Recipient user (object)
	 * @param int $gift_id Gift ID number
	 * @param int $type Gift type
	 * @param string $message Message as supplied by the sender; should be 255 characters or less
	 *
	 * @return int
	 */
	public function sendGift( $user_to, $gift_id, $type, $message ) {
		$dbw = wfGetDB( DB_MASTER );

		$services = MediaWikiServices::getInstance();
		// Ensure that if we received a $message longer than 255 only the first
		// 255 characters will be INSERTed into the DB; any longer than that and
		// we run into query errors if MySQL is running in strict mode
		// @see https://gerrit.wikimedia.org/r/606224
		$message = $services->getContentLanguage()->truncateForDatabase( $message, 255 );

		$dbw->insert(
			'user_gift',
			[
				'ug_gift_id' => $gift_id,
				'ug_actor_from' => $this->user->getActorId(),
				'ug_actor_to' => $user_to->getActorId(),
				'ug_type' => $type,
				'ug_status' => 1,
				'ug_message' => $message,
				'ug_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) ),
			], __METHOD__
		);
		$ug_gift_id = $dbw->insertId();
		$this->incGiftGivenCount( $gift_id );
		$this->sendGiftNotificationEmail( $user_to, $gift_id, $type );

		// Add to new gift count cache for receiving user
		$cache = $services->getMainWANObjectCache();
		$giftCount = new UserGiftCount( $cache, $user_to );
		$giftCount->clear();

		$stats = new UserStatsTrack( $user_to->getId(), $user_to->getName() );
		$stats->incStatField( 'gift_rec' );

		$stats = new UserStatsTrack( $this->user_id, $this->user_name );
		$stats->incStatField( 'gift_sent' );

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			EchoEvent::create( [
				'type' => 'social-gift-send',
				'agent' => $this->user,
				'extra' => [
					'target' => $user_to->getId(),
					'from' => $this->user_id,
					'mastergiftid' => $gift_id,
					'giftid' => $ug_gift_id,
					'type' => $type,
					'message' => $message
				]
			] );
		}

		return $ug_gift_id;
	}

	/**
	 * Sends the notification about a new gift to the user who received the
	 * gift, if the user wants notifications about new gifts and their e-mail
	 * is confirmed.
	 *
	 * @param User $user User (object) receiving the gift
	 * @param int $gift_id ID Number of the given gift
	 * @param int $type Gift type; unused
	 */
	private function sendGiftNotificationEmail( $user, $gift_id, $type ) {
		$gift = Gifts::getGift( $gift_id );
		$user->load();

		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ?
			$userOptionsLookup->getBoolOption( $user, 'echo-subscriptions-email-social-gift' ) :
			$userOptionsLookup->getIntOption( $user, 'notifygift', 1 );
		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$giftsLink = SpecialPage::getTitleFor( 'ViewGifts' );
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );

			if ( trim( $user->getRealName() ) ) {
				$name = $user->getRealName();
			} else {
				$name = $user->getName();
			}

			$subject = wfMessage( 'gift_received_subject',
				$this->user->getName(),
				$gift['gift_name']
			)->parse();

			$body = [
				'html' => wfMessage( 'gift_received_body_html',
					$name,
					$this->user->getName(),
					$gift['gift_name']
				)->parse(),
				'text' => wfMessage( 'gift_received_body',
					$name,
					$this->user->getName(),
					$gift['gift_name'],
					$giftsLink->getFullURL(),
					$updateProfileLink->getFullURL()
				)->text()
			];

			$user->sendMail( $subject, $body );
		}
	}

	public function clearAllUserGiftStatus() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( 'user_gift',
			/* SET */[ 'ug_status' => 0 ],
			/* WHERE */[ 'ug_actor_to' => $this->user->getActorId() ],
			__METHOD__
		);

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$giftCount = new UserGiftCount( $cache, $this->user );
		$giftCount->clear();
	}

	public function clearUserGiftStatus( $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( 'user_gift',
			/* SET */[ 'ug_status' => 0 ],
			/* WHERE */[ 'ug_id' => $id ],
			__METHOD__
		);

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$giftCount = new UserGiftCount( $cache, $this->user );
		$giftCount->clear();
	}

	/**
	 * Checks if a given user owns the gift, which is specified by its ID.
	 *
	 * @param User $user
	 * @param int $ug_id ID Number of the gift that we're checking
	 * @return bool True if the user owns the gift, otherwise false
	 */
	public function doesUserOwnGift( $user, $ug_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_gift',
			[ 'ug_actor_to' ],
			[ 'ug_id' => $ug_id ],
			__METHOD__
		);
		if ( $s !== false ) {
			if ( $user->getActorId() == $s->ug_actor_to ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Deletes a gift from the user_gift table.
	 *
	 * @param int $ug_id ID number of the gift to delete
	 */
	public static function deleteGift( $ug_id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'user_gift', [ 'ug_id' => $ug_id ], __METHOD__ );
	}

	/**
	 * Gets the user gift with the ID = $id.
	 *
	 * @param int $id Gift ID number
	 * @return array|false Array containing gift info, such as its ID, sender, etc.
	 */
	public static function getUserGift( $id ) {
		if ( !is_numeric( $id ) ) {
			return false;
		}

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'user_gift', 'gift' ],
			[
				'ug_id', 'ug_actor_from', 'ug_actor_to', 'ug_message', 'gift_id',
				'ug_date', 'ug_status', 'gift_name', 'gift_description',
				'gift_given_count'
			],
			[ 'ug_id' => $id ],
			__METHOD__,
			[ 'LIMIT' => 1, 'OFFSET' => 0 ],
			[ 'gift' => [ 'INNER JOIN', 'ug_gift_id = gift_id' ] ]
		);
		$row = $dbr->fetchObject( $res );
		if ( !$row ) {
			return false;
		}

		$gift = [];
		$gift['id'] = $row->ug_id;
		$gift['actor_from'] = $row->ug_actor_from;
		$gift['actor_to'] = $row->ug_actor_to;
		$gift['message'] = $row->ug_message;
		$gift['gift_count'] = $row->gift_given_count;
		$gift['timestamp'] = $row->ug_date;
		$gift['gift_id'] = $row->gift_id;
		$gift['name'] = $row->gift_name;
		$gift['description'] = $row->gift_description;
		$gift['status'] = $row->ug_status;

		return $gift;
	}

	public function getUserGiftList( $type, $limit = 0, $page = 0 ) {
		$dbr = wfGetDB( DB_REPLICA );
		$params = [];

		if ( $limit > 0 ) {
			$offset = 0;
			if ( $page ) {
				$offset = $page * $limit - ( $limit );
			}
			$params['LIMIT'] = $limit;
			$params['OFFSET'] = $offset;
		}

		$params['ORDER BY'] = 'ug_id DESC';
		$res = $dbr->select(
			[ 'user_gift', 'gift' ],
			[
				'ug_id', 'ug_actor_from', 'ug_gift_id',
				'ug_date', 'ug_status', 'gift_name', 'gift_description',
				'gift_given_count'
			],
			[ 'ug_actor_to' => $this->user->getActorId() ],
			__METHOD__,
			$params,
			[ 'gift' => [ 'INNER JOIN', 'ug_gift_id = gift_id' ] ]
		);

		$requests = [];
		foreach ( $res as $row ) {
			$requests[] = [
				'id' => $row->ug_id,
				'gift_id' => $row->ug_gift_id,
				'timestamp' => $row->ug_date,
				'status' => $row->ug_status,
				'actor_from' => $row->ug_actor_from,
				'gift_name' => $row->gift_name,
				'gift_description' => $row->gift_description,
				'gift_given_count' => $row->gift_given_count,
				'unix_timestamp' => wfTimestamp( TS_UNIX, $row->ug_date )
			];
		}

		return $requests;
	}

	/**
	 * Update the counter that tracks how many times a gift has been given out.
	 *
	 * @param int $gift_id ID number of the gift that we're tracking
	 */
	private function incGiftGivenCount( $gift_id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'gift',
			[ 'gift_given_count=gift_given_count+1' ],
			[ 'gift_id' => $gift_id ],
			__METHOD__
		);
	}

	/**
	 * Gets the amount of gifts a user has.
	 *
	 * @return int Amount of gifts the specified user has
	 */
	public function getGiftCountByUsername() {
		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select(
			'user_gift',
			'COUNT(*) AS count',
			[ 'ug_actor_to' => $this->user->getActorId() ],
			__METHOD__,
			[ 'LIMIT' => 1, 'OFFSET' => 0 ]
		);

		$row = $dbr->fetchObject( $res );
		$giftCount = 0;

		if ( $row ) {
			$giftCount = $row->count;
		}

		return $giftCount;
	}
}
