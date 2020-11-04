<?php
/**
 * Allows querying the database to get info on lists of user gifts.
 */
class UserGiftListLookup {
	/**
	 * @var IContextSource|RequestContext
	 */
	private $context;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var int
	 */
	private $limit;

	/**
	 * @var int
	 */
	private $page;

	public function __construct( $context, $limit = 0, $page = 0 ) {
		$this->context = $context;
		$this->limit = $limit;
		$this->page = $page;
		$this->user = $this->context->getUser();
	}

	/**
	 * @param string $order Used by the ORDER BY statement in the
	 * Database#select call
	 * @return array
	 */
	function getGiftList( $order = 'gift_createdate DESC' ) {
		$dbr = wfGetDB( DB_REPLICA );
		$params = [];

		if ( $this->limit > 0 ) {
			$offset = 0;
			if ( $this->page ) {
				$offset = $this->page * $this->limit - ( $this->limit );
			}
			$params['LIMIT'] = $this->limit;
			$params['OFFSET'] = $offset;
		}

		$params['ORDER BY'] = $order;
		$res = $dbr->select(
			'gift',
			[
				'gift_id', 'gift_createdate', 'gift_name', 'gift_description',
				'gift_given_count'
			],
			[ "gift_access = 0 OR gift_creator_actor = {$this->user->getActorId()}" ],
			__METHOD__,
			$params
		);

		$gifts = [];
		foreach ( $res as $row ) {
			$gifts[] = [
				'id' => $row->gift_id,
				'timestamp' => $row->gift_createdate,
				'gift_name' => $row->gift_name,
				'gift_description' => $row->gift_description,
				'gift_given_count' => $row->gift_given_count
			];
		}
		return $gifts;
	}

	/**
	 * @return array
	 */
	public function getManagedGiftList() {
		$dbr = wfGetDB( DB_REPLICA );

		$where = []; // Prevent E_NOTICE
		$params = [];
		$params['ORDER BY'] = 'gift_createdate';
		if ( $this->limit ) {
			$params['LIMIT'] = $this->limit;
		}

		// If the user isn't allowed to perform administrative tasks to gifts
		// and isn't allowed to delete pages, only show them the gifts they've
		// created
		if (
			!$this->user->isAllowed( 'giftadmin' ) &&
			!$this->user->isAllowed( 'delete' )
		) {
			$where = [ 'gift_creator_actor' => $this->user->getActorId() ];
		}

		$res = $dbr->select(
			'gift',
			[
				'gift_id', 'gift_createdate', 'gift_name', 'gift_description',
				'gift_given_count', 'gift_access', 'gift_creator_actor'
			],
			$where,
			__METHOD__,
			$params
		);

		$gifts = [];
		foreach ( $res as $row ) {
			$gifts[] = [
				'id' => $row->gift_id,
				'timestamp' => $row->gift_createdate,
				'gift_name' => $row->gift_name,
				'gift_description' => $row->gift_description,
				'gift_given_count' => $row->gift_given_count
			];
		}
		return $gifts;
	}
}
