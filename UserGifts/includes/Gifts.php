<?php
/**
 * Gifts class
 * Functions for managing individual social gifts
 * (add to/fetch/remove from database etc.)
 */
class Gifts {

	/**
	 * Adds a gift to the database
	 *
	 * @param User $user User who created the gift
	 * @param string $gift_name Name of the gift, as supplied by the user
	 * @param string $gift_description A short description about the gift, as supplied by the user
	 * @param int $gift_access 0 by default
	 *
	 * @return int
	 */
	public static function addGift(
		User $user,
		$gift_name,
		$gift_description,
		$gift_access = 0
	) {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'gift',
			[
				'gift_name' => $gift_name,
				'gift_description' => $gift_description,
				'gift_createdate' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) ),
				'gift_creator_actor' => $user->getActorId(),
				'gift_access' => $gift_access,
			],
			__METHOD__
		);
		return $dbw->insertId();
	}

	/**
	 * Updates a gift's info in the database
	 *
	 * @param int $id Internal ID number of the gift that we want to update
	 * @param string $gift_name Name of the gift, as supplied by the user
	 * @param string $gift_description A short description about the gift, as supplied by the user
	 * @param int $access 0 by default
	 */
	public static function updateGift( $id, $gift_name, $gift_description, $access = 0 ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'gift',
			/* SET */[
				'gift_name' => $gift_name,
				'gift_description' => $gift_description,
				'gift_access' => $access
			],
			/* WHERE */[ 'gift_id' => $id ],
			__METHOD__
		);
	}

	/**
	 * Gets information, such as name and description, about a given gift from the database
	 *
	 * @param int $id internal ID number of the gift
	 * @return array Gift information, including ID number, name, description,
	 * creator's user name and ID and gift access
	 */
	public static function getGift( $id ) {
		if ( !is_numeric( $id ) ) {
			return [];
		}
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'gift',
			[
				'gift_id', 'gift_name', 'gift_description', 'gift_creator_actor',
				'gift_access'
			],
			[ 'gift_id' => $id ],
			__METHOD__,
			[ 'LIMIT' => 1, 'OFFSET' => 0 ]
		);
		$row = $dbr->fetchObject( $res );
		$gift = [];
		if ( $row ) {
			$gift['gift_id'] = $row->gift_id;
			$gift['gift_name'] = $row->gift_name;
			$gift['gift_description'] = $row->gift_description;
			$gift['creator_actor'] = $row->gift_creator_actor;
			$gift['access'] = $row->gift_access;
		}
		return $gift;
	}

	/**
	 * Get the amount of custom gifts the given user has created.
	 *
	 * @param User $user
	 * @return int
	 */
	public static function getCustomCreatedGiftCount( $user ) {
		$dbr = wfGetDB( DB_REPLICA );
		$gift_count = 0;
		$s = $dbr->selectRow(
			'gift',
			[ 'COUNT(gift_id) AS count' ],
			[ 'gift_creator_actor' => $user->getActorId() ],
			__METHOD__
		);
		if ( $s !== false ) {
			$gift_count = $s->count;
		}
		return $gift_count;
	}

	/**
	 * Get the total amount of gifts that have never been given out (?!).
	 *
	 * @todo FIXME: I don't understand this method at all. It's used by GiftManager
	 * special page for pagination, but it makes no sense because $gift_count is
	 * literally zero. Look into this and update documentation or remove this
	 * whole method as appropriate.
	 *
	 * @return int
	 */
	public static function getGiftCount() {
		$dbr = wfGetDB( DB_REPLICA );
		$gift_count = 0;
		$s = $dbr->selectRow(
			'gift',
			[ 'COUNT(gift_id) AS count' ],
			[ 'gift_given_count' => $gift_count ],
			__METHOD__
		);
		if ( $s !== false ) {
			$gift_count = $s->count;
		}
		return $gift_count;
	}
}
