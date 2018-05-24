<?php

/**
 * UserGifts class
 * @todo document
 */
class UserGifts {

	public $user_id; # Text form (spaces not underscores) of the main part
	public $user_name; # Text form (spaces not underscores) of the main part

	public function __construct( $username ) {
		$title1 = Title::newFromDBkey( $username );
		$this->user_name = $title1->getText();
		$this->user_id = User::idFromName( $this->user_name );
	}

	/**
	 * Sends a gift to the specified user.
	 *
	 * @param int $user_to User ID of the recipient
	 * @param int $gift_id Gift ID number
	 * @param int $type Gift type
	 * @param mixed $message Message as supplied by the sender
	 */
	public function sendGift( $user_to, $gift_id, $type, $message ) {
		global $wgMemc;

		$user_id_to = User::idFromName( $user_to );
		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'user_gift',
			[
				'ug_gift_id' => $gift_id,
				'ug_user_id_from' => $this->user_id,
				'ug_user_name_from' => $this->user_name,
				'ug_user_id_to' => $user_id_to,
				'ug_user_name_to' => $user_to,
				'ug_type' => $type,
				'ug_status' => 1,
				'ug_message' => $message,
				'ug_date' => date( 'Y-m-d H:i:s' ),
			], __METHOD__
		);
		$ug_gift_id = $dbw->insertId();
		$this->incGiftGivenCount( $gift_id );
		$this->sendGiftNotificationEmail( $user_id_to, $this->user_name, $gift_id, $type );

		// Add to new gift count cache for receiving user
		$giftCount = new UserGiftCount( $wgMemc, $user_id_to );
		$giftCount->increase();

		$stats = new UserStatsTrack( $user_id_to, $user_to );
		$stats->incStatField( 'gift_rec' );

		$stats = new UserStatsTrack( $this->user_id, $this->user_name );
		$stats->incStatField( 'gift_sent' );

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$userFrom = User::newFromId( $this->user_id );

			EchoEvent::create( [
				'type' => 'social-gift-send',
				'agent' => $userFrom,
				'extra' => [
					'target' => $user_id_to,
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
	 * @param int $user_id_to User ID of the receiver of the gift
	 * @param mixed $user_from Name of the user who sent the gift
	 * @param int $gift_id ID Number of the given gift
	 * @param int $type Gift type; unused
	 */
	public function sendGiftNotificationEmail( $user_id_to, $user_from, $gift_id, $type ) {
		$gift = Gifts::getGift( $gift_id );
		$user = User::newFromId( $user_id_to );
		$user->loadFromDatabase();

		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ? $user->getBoolOption( 'echo-subscriptions-email-social-gift' ) : $user->getIntOption( 'notifygift', 1 );
		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$giftsLink = SpecialPage::getTitleFor( 'ViewGifts' );
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );

			if ( trim( $user->getRealName() ) ) {
				$name = $user->getRealName();
			} else {
				$name = $user->getName();
			}

			$subject = wfMessage( 'gift_received_subject',
				$user_from,
				$gift['gift_name']
			)->parse();

			$body = [
				'html' => wfMessage( 'gift_received_body_html',
					$name,
					$user_from,
					$gift['gift_name']
				)->parse(),
				'text' => wfMessage( 'gift_received_body',
					$name,
					$user_from,
					$gift['gift_name'],
					$giftsLink->getFullURL(),
					$updateProfileLink->getFullURL()
				)->text()
			];

			$user->sendMail( $subject, $body );
		}
	}

	public function clearAllUserGiftStatus() {
		global $wgMemc;

		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( 'user_gift',
			/* SET */[ 'ug_status' => 0 ],
			/* WHERE */[ 'ug_user_id_to' => $this->user_id ],
			__METHOD__
		);

		$giftCount = new UserGiftCount( $wgMemc, $this->user_id );
		$giftCount->clear();
	}

	public function clearUserGiftStatus( $id ) {
		global $wgMemc;

		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( 'user_gift',
			/* SET */[ 'ug_status' => 0 ],
			/* WHERE */[ 'ug_id' => $id ],
			__METHOD__
		);

		$giftCount = new UserGiftCount( $wgMemc, $this->user_id );
		$giftCount->decrease();
	}

	/**
	 * Checks if a given user owns the gift, which is specified by its ID.
	 *
	 * @param int $user_id User ID of the given user
	 * @param int $ug_id ID Number of the gift that we're checking
	 * @return bool True if the user owns the gift, otherwise false
	 */
	public function doesUserOwnGift( $user_id, $ug_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_gift',
			[ 'ug_user_id_to' ],
			[ 'ug_id' => $ug_id ],
			__METHOD__
		);
		if ( $s !== false ) {
			if ( $user_id == $s->ug_user_id_to ) {
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
	static function deleteGift( $ug_id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'user_gift', [ 'ug_id' => $ug_id ], __METHOD__ );
	}

	/**
	 * Gets the user gift with the ID = $id.
	 *
	 * @param int $id Gift ID number
	 * @return array Array containing gift info, such as its ID, sender, etc.
	 */
	static function getUserGift( $id ) {
		if ( !is_numeric( $id ) ) {
			return '';
		}

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'user_gift', 'gift' ],
			[
				'ug_id', 'ug_user_id_from', 'ug_user_name_from',
				'ug_user_id_to', 'ug_user_name_to', 'ug_message', 'gift_id',
				'ug_date', 'ug_status', 'gift_name', 'gift_description',
				'gift_given_count'
			],
			[ "ug_id = {$id}" ],
			__METHOD__,
			[ 'LIMIT' => 1, 'OFFSET' => 0 ],
			[ 'gift' => [ 'INNER JOIN', 'ug_gift_id = gift_id' ] ]
		);
		$row = $dbr->fetchObject( $res );
		if ( $row ) {
			$gift['id'] = $row->ug_id;
			$gift['user_id_from'] = $row->ug_user_id_from;
			$gift['user_name_from'] = $row->ug_user_name_from;
			$gift['user_id_to'] = $row->ug_user_id_to;
			$gift['user_name_to'] = $row->ug_user_name_to;
			$gift['message'] = $row->ug_message;
			$gift['gift_count'] = $row->gift_given_count;
			$gift['timestamp'] = $row->ug_date;
			$gift['gift_id'] = $row->gift_id;
			$gift['name'] = $row->gift_name;
			$gift['description'] = $row->gift_description;
			$gift['status'] = $row->ug_status;
		}

		return $gift;
	}

	public function getUserGiftList( $type, $limit = 0, $page = 0 ) {
		$dbr = wfGetDB( DB_REPLICA );
		$params = [];

		if ( $limit > 0 ) {
			$limitvalue = 0;
			if ( $page ) {
				$limitvalue = $page * $limit - ( $limit );
			}
			$params['LIMIT'] = $limit;
			$params['OFFSET'] = $limitvalue;
		}

		$params['ORDER BY'] = 'ug_id DESC';
		$res = $dbr->select(
			[ 'user_gift', 'gift' ],
			[
				'ug_id', 'ug_user_id_from', 'ug_user_name_from', 'ug_gift_id',
				'ug_date', 'ug_status', 'gift_name', 'gift_description',
				'gift_given_count'
			],
			[ "ug_user_id_to = {$this->user_id}" ],
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
				'user_id_from' => $row->ug_user_id_from,
				'user_name_from' => $row->ug_user_name_from,
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
	 * @param mixed $userName Username whose gift count we're looking up
	 * @return int Amount of gifts the specified user has
	 */
	static function getGiftCountByUsername( $userName ) {
		$dbr = wfGetDB( DB_REPLICA );
		$userId = User::idFromName( $userName );

		$res = $dbr->select(
			'user_gift',
			'COUNT(*) AS count',
			[ "ug_user_id_to = {$userId}" ],
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
