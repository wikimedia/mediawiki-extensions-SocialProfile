<?php
/**
 * Object for easily querying the system_gift and user_system_gift tables
 */
class SystemGiftListLookup {
	/**
	 * @var int LIMIT for the SQL query
	 */
	private $limit;

	/**
	 * @var int If greater than 0, used to determine
	 * the OFFSET for the SQL query
	 */
	private $page;

	public function __construct( $limit = 0, $page = 0 ) {
		$this->limit = $limit;
		$this->page = $page;
	}

	/**
	 * Get the list of all existing system gifts (awards).
	 *
	 * @return array array containing gift info, including
	 * (but not limited to) gift ID, creation timestamp, name,
	 * description, etc.
	 */
	public function getGiftList() {
		$dbr = wfGetDB( DB_REPLICA );

		$offset = 0;
		if ( $this->limit > 0 && $this->page ) {
			$offset = $this->page * $this->limit - ( $this->limit );
		}

		$res = $dbr->select(
			'system_gift',
			[
				'gift_id', 'gift_createdate', 'gift_name', 'gift_description',
				'gift_category', 'gift_threshold', 'gift_given_count'
			],
			[],
			__METHOD__,
			[
				'ORDER BY' => 'gift_createdate DESC',
				'LIMIT' => $this->limit,
				'OFFSET' => $offset
			]
		);

		$gifts = [];
		foreach ( $res as $row ) {
			$gifts[] = [
				'id' => $row->gift_id,
				'timestamp' => $row->gift_createdate,
				'gift_name' => $row->gift_name,
				'gift_description' => $row->gift_description,
				'gift_category' => $row->gift_category,
				'gift_threshold' => $row->gift_threshold,
				'gift_given_count' => $row->gift_given_count
			];
		}

		return $gifts;
	}

	/**
	 * Get the list of this user's system gifts.
	 *
	 * @param User $user
	 * @return array Array of system gift information
	 */
	public function getUserGiftList( User $user ) {
		$dbr = wfGetDB( DB_REPLICA );

		$offset = 0;
		if ( $this->limit > 0 && $this->page ) {
			$offset = $this->page * $this->limit - ( $this->limit );
		}

		$res = $dbr->select(
			[ 'user_system_gift', 'system_gift', 'actor' ],
			[
				'sg_id', 'sg_actor', 'actor_name', 'actor_user', 'sg_gift_id', 'sg_date',
				'sg_status', 'gift_name', 'gift_description',
				'gift_given_count'
			],
			[ 'sg_actor' => $user->getActorId() ],
			__METHOD__,
			[
				'ORDER BY' => 'sg_id DESC',
				'LIMIT' => $this->limit,
				'OFFSET' => $offset
			],
			[
				'system_gift' => [ 'INNER JOIN', 'sg_gift_id = gift_id' ],
				'actor' => [ 'JOIN', 'sg_actor = actor_id' ]
			]
		);

		$gifts = [];
		foreach ( $res as $row ) {
			$gifts[] = [
				'id' => $row->sg_id,
				'gift_id' => $row->sg_gift_id,
				'timestamp' => $row->sg_date,
				'status' => $row->sg_status,
				'user_id' => $row->actor_user,
				'user_name' => $row->actor_name,
				'gift_name' => $row->gift_name,
				'gift_description' => $row->gift_description,
				'gift_given_count' => $row->gift_given_count,
				'unix_timestamp' => wfTimestamp( TS_UNIX, $row->sg_date )
			];
		}
		return $gifts;
	}
}
