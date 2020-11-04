<?php
/**
 * Object for easily querying the user_relationship
 * and user_relationship_request tables
 */
class RelationshipListLookup {
	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var int Used as the LIMIT in the SQL query
	 */
	private $limit;

	public function __construct( User $user, $limit = 0 ) {
		$this->user = $user;
		$this->limit = $limit;
	}

	/**
	 * Get the list of open relationship requests.
	 *
	 * @param int $status
	 * @return array Array of open relationship requests
	 */
	public function getRequestList( $status ) {
		$dbr = wfGetDB( DB_REPLICA );

		$options = [];
		if ( $this->limit > 0 ) {
			$options['OFFSET'] = 0;
			$options['LIMIT'] = $this->limit;
		}

		$options['ORDER BY'] = 'ur_id DESC';
		$res = $dbr->select(
			'user_relationship_request',
			[ 'ur_id', 'ur_actor_from', 'ur_type', 'ur_message', 'ur_date' ],
			[
				'ur_actor_to' => $this->user->getActorId(),
				'ur_status' => $status
			],
			__METHOD__,
			$options
		);

		$requests = [];
		foreach ( $res as $row ) {
			if ( $row->ur_type == 1 ) {
				$type_name = 'Friend';
			} else {
				$type_name = 'Foe';
			}
			$requests[] = [
				'id' => $row->ur_id,
				'type' => $type_name,
				'message' => $row->ur_message,
				'timestamp' => $row->ur_date,
				'actor_from' => $row->ur_actor_from
			];
		}

		return $requests;
	}

	/**
	 * Get the relationship list for the current user.
	 * This function should only be used if you're dynamically
	 * retrieving the relationship type; otherwise use getFriendList()
	 * or getFoeList().
	 *
	 * @param int $type
	 * - 1 for friends
	 * - 2 (or anything else but 1) for foes
	 * @param int $page If greater than 0, will be used to
	 * calculate the OFFSET for the SQL query
	 * @return array Array of relationship information
	 */
	public function getRelationshipList( $type = 0, $page = 0 ) {
		$dbr = wfGetDB( DB_REPLICA );

		$where = [];
		$options = [];
		$where['r_actor'] = $this->user->getActorId();

		if ( $type ) {
			$where['r_type'] = $type;
		}

		if ( $this->limit > 0 ) {
			$offset = 0;
			if ( $page ) {
				$offset = $page * $this->limit - ( $this->limit );
			}
			$options['LIMIT'] = $this->limit;
			$options['OFFSET'] = $offset;
		}

		$res = $dbr->select(
			'user_relationship',
			[ 'r_id', 'r_actor_relation', 'r_date', 'r_type' ],
			$where,
			__METHOD__,
			$options
		);

		$requests = [];
		foreach ( $res as $row ) {
			$requests[] = [
				'id' => $row->r_id,
				'timestamp' => $row->r_date,
				'actor' => $row->r_actor_relation,
				'type' => $row->r_type
			];
		}

		return $requests;
	}

	/**
	 * Gets the list of friends for the current user
	 *
	 * @param int $page See getRelationshipList()
	 * @return array See getRelationshipList()
	 */
	public function getFriendList( $page = 0 ) {
		return $this->getRelationshipList( 1, $page );
	}

	/**
	 * Gets the list of foes for the current user
	 *
	 * @param int $page See getRelationshipList()
	 * @return array See getRelationshipList()
	 */
	public function getFoeList( $page = 0 ) {
		return $this->getRelationshipList( 2, $page );
	}
}
