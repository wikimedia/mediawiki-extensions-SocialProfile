<?php
/**
 * Gifts class
 * Functions for managing individual social gifts
 * (add to/fetch/remove from database etc.)
 */
class Gifts {

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Adds a gift to the database
	 * @param string $gift_name name of the gift, as supplied by the user
	 * @param string $gift_description a short description about the gift, as supplied by the user
	 * @param int $gift_access 0 by default
	 */
	static function addGift( $gift_name, $gift_description, $gift_access = 0 ) {
		global $wgUser;

		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'gift',
			array(
				'gift_name' => $gift_name,
				'gift_description' => $gift_description,
				'gift_createdate' => date( 'Y-m-d H:i:s' ),
				'gift_creator_user_id' => $wgUser->getId(),
				'gift_creator_user_name' => $wgUser->getName(),
				'gift_access' => $gift_access,
			), __METHOD__
		);
		return $dbw->insertId();
	}

	/**
	 * Updates a gift's info in the database
	 * @param $id int internal ID number of the gift that we want to update
	 * @param $gift_namemixed name of the gift, as supplied by the user
	 * @param $gift_descriptionmixed a short description about the gift, as supplied by the user
	 * @param $gift_access int 0 by default
	 */
	public static function updateGift( $id, $gift_name, $gift_description, $access = 0 ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( 'gift',
			/* SET */array(
				'gift_name' => $gift_name,
				'gift_description' => $gift_description,
				'gift_access' => $access
			),
			/* WHERE */array( 'gift_id' => $id ),
			__METHOD__
		);
	}

	/**
	 * Gets information, such as name and description, about a given gift from the database
	 *
	 * @param int $id internal ID number of the gift
	 * @return Gift information, including ID number, name, description,
	 * creator's user name and ID and gift access
	 */
	static function getGift( $id ) {
		if ( !is_numeric( $id ) ) {
			return '';
		}
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'gift',
			array(
				'gift_id', 'gift_name', 'gift_description',
				'gift_creator_user_id', 'gift_creator_user_name', 'gift_access'
			),
			array( "gift_id = {$id}" ),
			__METHOD__,
			array( 'LIMIT' => 1, 'OFFSET' => 0 )
		);
		$row = $dbr->fetchObject( $res );
		$gift = array();
		if ( $row ) {
			$gift['gift_id'] = $row->gift_id;
			$gift['gift_name'] = $row->gift_name;
			$gift['gift_description'] = $row->gift_description;
			$gift['creator_user_id'] = $row->gift_creator_user_id;
			$gift['creator_user_name'] = $row->gift_creator_user_name;
			$gift['access'] = $row->gift_access;
		}
		return $gift;
	}

	static function getCustomCreatedGiftCount( $user_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$gift_count = 0;
		$s = $dbr->selectRow(
			'gift',
			array( 'COUNT(gift_id) AS count' ),
			array( 'gift_creator_user_id' => $user_id ),
			__METHOD__
		);
		if ( $s !== false ) {
			$gift_count = $s->count;
		}
		return $gift_count;
	}

	static function getGiftCount() {
		$dbr = wfGetDB( DB_REPLICA );
		$gift_count = 0;
		$s = $dbr->selectRow(
			'gift',
			array( 'COUNT(gift_id) AS count' ),
			array( 'gift_given_count' => $gift_count ),
			__METHOD__
		);
		if ( $s !== false ) {
			$gift_count = $s->count;
		}
		return $gift_count;
	}
}
