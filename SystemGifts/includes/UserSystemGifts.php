<?php

/**
 * Class for managing awards (a.k.a system gifts)
 */
class UserSystemGifts {

	public $user_id;		# Text form (spaces not underscores) of the main part
	public $user_name;		# Text form (spaces not underscores) of the main part

	public function __construct( $username ) {
		$title1 = Title::newFromDBkey( $username );
		$this->user_name = $title1->getText();
		$this->user_id = User::idFromName( $this->user_name );
	}

	/**
	 * Gives out a system gift with the ID of $gift_id, purges memcached and
	 * optionally sends out e-mail to the user about their new system gift.
	 *
	 * @param int $gift_id ID number of the system gift
	 * @param bool $email True to send out notification e-mail to users,
	 * otherwise false
	 */
	public function sendSystemGift( $gift_id, $email = true ) {
		global $wgMemc;

		if ( $this->doesUserHaveGift( $this->user_id, $gift_id ) ) {
			return '';
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'user_system_gift',
			array(
				'sg_gift_id' => $gift_id,
				'sg_user_id' => $this->user_id,
				'sg_user_name' => $this->user_name,
				'sg_status' => 1,
				'sg_date' => date( 'Y-m-d H:i:s' ),
			),
			__METHOD__
		);
		$sg_gift_id = $dbw->insertId();
		self::incGiftGivenCount( $gift_id );

		// Add to new gift count cache for receiving user
		$giftCount = new SystemGiftCount( $wgMemc, $this->user_id );
		$giftCount->increase();

		if ( $email && !empty( $sg_gift_id ) ) {
			$this->sendGiftNotificationEmail( $this->user_id, $gift_id );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$userFrom = User::newFromId( $this->user_id );

			$giftObj = SystemGifts::getGift( $gift_id );
			EchoEvent::create( array(
				'type' => 'social-award-rec',
				'agent' => $userFrom,
				'extra' => array(
					'notifyAgent' => true,
					'target' => $this->user_id,
					'mastergiftid' => $gift_id,
					'giftid' => $sg_gift_id,
					'giftname' => $giftObj['gift_name']
				)
			) );
		}

		$wgMemc->delete( $wgMemc->makeKey( 'user', 'profile', 'system_gifts', $this->user_id ) );

		return $sg_gift_id;
	}

	/**
	 * Sends notification e-mail to the user with the ID $user_id_to whenever
	 * they get a new system gift (award) if their e-mail address is confirmed
	 * and they have opted in to these notifications on their social
	 * preferences.
	 *
	 * @param int $user_id_to User ID of the recipient
	 * @param int $gift_id System gift ID number
	 */
	public function sendGiftNotificationEmail( $user_id_to, $gift_id ) {
		$gift = SystemGifts::getGift( $gift_id );
		$user = User::newFromId( $user_id_to );
		$user->loadFromDatabase();

		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ? $user->getBoolOption( 'echo-subscriptions-email-social-award' ) : $user->getIntOption( 'notifygift', 1 );
		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$gifts_link = SpecialPage::getTitleFor( 'ViewSystemGifts' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage( 'system_gift_received_subject',
				$gift['gift_name']
			)->text();
			if ( trim( $user->getRealName() ) ) {
				$name = $user->getRealName();
			} else {
				$name = $user->getName();
			}
			$body = array(
				'html' => wfMessage( 'system_gift_received_body_html',
					$name,
					$gift['gift_name'],
					$gift['gift_description']
				)->parse(),
				'text' => wfMessage( 'system_gift_received_body',
					$name,
					$gift['gift_name'],
					$gift['gift_description'],
					$gifts_link->getFullURL(),
					$update_profile_link->getFullURL()
				)->text()
			);

			$user->sendMail( $subject, $body );
		}
	}

	/**
	 * Checks if the user with the ID $user_id has the system gift with the ID
	 * $gift_id by querying the user_system_gift table.
	 *
	 * @param int $user_id User ID
	 * @param int $gift_id System gift ID
	 * @return bool True if the user has the gift, otherwise false
	 */
	public function doesUserHaveGift( $user_id, $gift_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_system_gift',
			array( 'sg_status' ),
			array( 'sg_user_id' => $user_id, 'sg_gift_id' => $gift_id ),
			__METHOD__
		);
		if ( $s !== false ) {
			return true;
		}
		return false;
	}

	public function clearUserGiftStatus( $id ) {
		global $wgMemc;

		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( 'user_system_gift',
			[ 'sg_status' => 0 ],
			[ 'sg_id' => $id ],
			__METHOD__
		);

		$giftCount = new SystemGiftCount( $wgMemc, $this->user_id );
		$giftCount->decrease();
	}

	/**
	 * Checks if the user whose user ID is $user_id owns the system gift with
	 * the ID = $sg_id.
	 *
	 * @param int $user_id User ID
	 * @param int $sg_id ID Number of the system gift whose ownership
	 * we're trying to figure out here.
	 * @return bool True if the specified user owns the system gift,
	 * otherwise false
	 */
	public function doesUserOwnGift( $user_id, $sg_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_system_gift',
			array( 'sg_user_id' ),
			array( 'sg_id' => $sg_id ),
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
	 * Deletes the system gift that has the ID $ug_id.
	 *
	 * @param int $ug_id Gift ID of the system gift
	 * that we're about to delete.
	 */
	static function deleteGift( $ug_id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'user_system_gift',
			array( 'sg_id' => $ug_id ),
			__METHOD__
		);
	}

	/**
	 * Get information about the system gift with the ID $id from the database.
	 * This info includes, but is not limited to, the gift ID, its description,
	 * name, status and so on.
	 *
	 * @param int $id System gift ID number
	 * @return array Array containing information about the system gift
	 */
	static function getUserGift( $id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			array( 'user_system_gift', 'system_gift' ),
			array(
				'sg_id', 'sg_user_id', 'sg_user_name', 'gift_id', 'sg_date',
				'gift_name', 'gift_description', 'gift_given_count', 'sg_status'
			),
			array( "sg_id = {$id}" ),
			__METHOD__,
			array(
				'LIMIT' => 1,
				'OFFSET' => 0
			),
			array( 'system_gift' => array( 'INNER JOIN', 'sg_gift_id = gift_id' ) )
		);
		$row = $dbr->fetchObject( $res );
		$gift = array();
		if ( $row ) {
			$gift['id'] = $row->sg_id;
			$gift['user_id'] = $row->sg_user_id;
			$gift['user_name'] = $row->sg_user_name;
			$gift['gift_count'] = $row->gift_given_count;
			$gift['timestamp'] = $row->sg_date;
			$gift['gift_id'] = $row->gift_id;
			$gift['name'] = $row->gift_name;
			$gift['description'] = $row->gift_description;
			$gift['status'] = $row->sg_status;
		}

		return $gift;
	}

	/**
	 * Update the counter that tracks how many times a system gift has been
	 * given out.
	 *
	 * @param int $giftId ID number of the system gift that we're tracking
	 */
	public static function incGiftGivenCount( $giftId ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'system_gift',
			array( 'gift_given_count = gift_given_count + 1' ),
			array( 'gift_id' => $giftId ),
			__METHOD__
		);
	}

	/**
	 * Get the amount of system gifts for the specified user.
	 *
	 * @param mixed $user_name User name for the user whose gift
	 * count we're looking up; this is used to find out their UID.
	 * @return int Gift count for the specified user
	 */
	static function getGiftCountByUsername( $user_name ) {
		$dbr = wfGetDB( DB_REPLICA );
		$user_id = User::idFromName( $user_name );
		$res = $dbr->select(
			'user_system_gift',
			array( 'COUNT(*) AS count' ),
			array( "sg_user_id = {$user_id}" ),
			__METHOD__,
			array( 'LIMIT' => 1, 'OFFSET' => 0 )
		);
		$row = $dbr->fetchObject( $res );
		$gift_count = 0;
		if ( $row ) {
			$gift_count = $row->count;
		}
		return $gift_count;
	}
}
