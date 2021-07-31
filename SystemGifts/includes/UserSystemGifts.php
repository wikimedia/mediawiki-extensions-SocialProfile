<?php

use MediaWiki\MediaWikiServices;

/**
 * Class for managing awards (a.k.a system gifts)
 */
class UserSystemGifts {

	/**
	 * @var User The user (object) whose awards we're dealing with here
	 */
	public $user;

	/**
	 * @var int User ID of the user (object) whose awards we're dealing with here
	 */
	public $user_id;

	/**
	 * @var string User name of the user (object) whose awards we're dealing with here
	 */
	public $user_name;

	/**
	 * @var int Actor ID of the user (object) whose awards we're dealing with here
	 */
	public $actorId;

	/**
	 * Constructor, sets the appropriate class member variables
	 *
	 * @param string|User $user User instance object (preferred) or a string (user name)
	 */
	public function __construct( $user ) {
		if ( $user instanceof User ) {
			$this->user = $user;
		} else {
			$this->user = User::newFromName( $user );
		}

		$this->user_id = $this->user->getId();
		$this->user_name = $this->user->getName();
		$this->actorId = $this->user->getActorId();
	}

	/**
	 * Gives out a system gift with the ID of $gift_id, purges memcached and
	 * optionally sends out e-mail to the user about their new system gift.
	 *
	 * @param int $gift_id ID number of the system gift
	 * @param bool $email True to send out notification e-mail to users,
	 * otherwise false
	 *
	 * @return int|string
	 */
	public function sendSystemGift( $gift_id, $email = true ) {
		if ( $this->doesUserHaveGift( $gift_id ) ) {
			return '';
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'user_system_gift',
			[
				'sg_gift_id' => $gift_id,
				'sg_actor' => $this->actorId,
				'sg_status' => 1,
				'sg_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) ),
			],
			__METHOD__
		);
		$sg_gift_id = $dbw->insertId();
		self::incGiftGivenCount( $gift_id );

		// Add to new gift count cache for receiving user
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$giftCount = new SystemGiftCount( $cache, $this->user );
		$giftCount->clear();

		if ( $email && !empty( $sg_gift_id ) ) {
			$this->sendGiftNotificationEmail( $gift_id );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$giftObj = SystemGifts::getGift( $gift_id );
			EchoEvent::create( [
				'type' => 'social-award-rec',
				'agent' => $this->user,
				'extra' => [
					'notifyAgent' => true, // backwards compatibility for MW 1.32 and below
					'target' => $this->user_id,
					'mastergiftid' => $gift_id,
					'giftid' => $sg_gift_id,
					'giftname' => $giftObj['gift_name']
				]
			] );
		}

		// @todo There should be a sensible method for getting this cache key because
		// it is called in three places:
		// 1) SystemGifts/includes/SystemGifts.php
		// 2) SystemGifts/includes/UserSystemGifts.php
		// 3) UserProfile/includes/UserProfilePage.php
		$cache->delete( $cache->makeKey( 'user', 'profile', 'system_gifts', 'actor_id', $this->actorId ) );

		return $sg_gift_id;
	}

	/**
	 * Sends notification e-mail to the user with the ID $user_id_to whenever
	 * they get a new system gift (award) if their e-mail address is confirmed
	 * and they have opted in to these notifications on their social
	 * preferences.
	 *
	 * @param int $gift_id System gift ID number
	 */
	public function sendGiftNotificationEmail( $gift_id ) {
		$gift = SystemGifts::getGift( $gift_id );
		$this->user->load();

		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ?
			$userOptionsLookup->getBoolOption( $this->user, 'echo-subscriptions-email-social-award' ) :
			$userOptionsLookup->getIntOption( $this->user, 'notifygift', 1 );
		if ( $this->user->isEmailConfirmed() && $wantsEmail ) {
			$gifts_link = SpecialPage::getTitleFor( 'ViewSystemGifts' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage( 'system_gift_received_subject',
				$gift['gift_name']
			)->text();
			if ( trim( $this->user->getRealName() ) ) {
				$name = $this->user->getRealName();
			} else {
				$name = $this->user->getName();
			}
			$body = [
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
			];

			$this->user->sendMail( $subject, $body );
		}
	}

	/**
	 * Checks if the user has the system gift with the supplied ID
	 * by querying the user_system_gift table.
	 *
	 * @todo Merge this and SystemGifts#doesUserHaveGift! Note the slightly
	 *  different output (this returns only bools whereas the other method
	 *  return the system gift ID if the user has the requested system gift).
	 *
	 * @param int $gift_id System gift ID
	 * @return bool True if the user has the gift, otherwise false
	 */
	public function doesUserHaveGift( $gift_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_system_gift',
			[ 'sg_status' ],
			[ 'sg_actor' => $this->actorId, 'sg_gift_id' => $gift_id ],
			__METHOD__
		);
		if ( $s !== false ) {
			return true;
		}
		return false;
	}

	public function clearUserGiftStatus( $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'user_system_gift',
			[ 'sg_status' => 0 ],
			[ 'sg_id' => $id ],
			__METHOD__
		);

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$giftCount = new SystemGiftCount( $cache, $this->user );
		$giftCount->clear();
	}

	/**
	 * Deletes the system gift that has the ID $ug_id.
	 *
	 * @param int $ug_id Gift ID of the system gift that we're about to delete
	 */
	public static function deleteGift( $ug_id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'user_system_gift',
			[ 'sg_id' => $ug_id ],
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
	public static function getUserGift( $id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'user_system_gift', 'system_gift', 'actor' ],
			[
				'sg_id', 'sg_actor', 'actor_name', 'actor_user', 'gift_id', 'sg_date',
				'gift_name', 'gift_description', 'gift_given_count', 'sg_status'
			],
			[ 'sg_id' => $id ],
			__METHOD__,
			[
				'LIMIT' => 1,
				'OFFSET' => 0
			],
			[
				'system_gift' => [ 'INNER JOIN', 'sg_gift_id = gift_id' ],
				'actor' => [ 'JOIN', 'sg_actor = actor_id' ]
			]
		);
		$row = $dbr->fetchObject( $res );
		$gift = [];
		if ( $row ) {
			$gift['id'] = $row->sg_id;
			$gift['user_id'] = $row->actor_user;
			$gift['user_name'] = $row->actor_name;
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
			[ 'gift_given_count = gift_given_count + 1' ],
			[ 'gift_id' => $giftId ],
			__METHOD__
		);
	}

	/**
	 * Get the amount of system gifts for the specified user.
	 *
	 * @param User $user
	 * @return int System gift count for the specified user
	 */
	public function getGiftCountByUsername( User $user ) {
		$dbr = wfGetDB( DB_REPLICA );
		$actorId = $user->getActorId();
		$res = $dbr->select(
			'user_system_gift',
			[ 'COUNT(*) AS count' ],
			[ 'sg_actor' => $actorId ],
			__METHOD__,
			[ 'LIMIT' => 1, 'OFFSET' => 0 ]
		);
		$row = $dbr->fetchObject( $res );
		$gift_count = 0;
		if ( $row ) {
			$gift_count = $row->count;
		}
		return $gift_count;
	}
}
