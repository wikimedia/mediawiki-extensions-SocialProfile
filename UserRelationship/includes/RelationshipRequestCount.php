<?php

use MediaWiki\Logger\LoggerFactory;

/**
 * This object allows for increasing, decreasing, and getting
 * the amount of relationship requests present in a user's queue.
 */
class RelationshipRequestCount {

	/**
	 * @var WANObjectCache
	 */
	private $cache;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var int
	 * - 1 for friends
	 * - 2 (or anything else but 1) for foes
	 */
	private $type;

	public function __construct( $cache, $user ) {
		$this->cache = $cache;
		$this->user = $user;
	}

	/**
	 * Allows setting the relationship type. This should
	 * only be used if you're dynamically setting the
	 * relationship type.
	 *
	 * @param int $type
	 * @return RelationshipRequestCount
	 */
	public function setType( $type ) {
		$this->type = $type;

		return $this;
	}

	/**
	 * Sets the type as friends
	 *
	 * @return RelationshipRequestCount
	 */
	public function setFriends() {
		$this->type = 1;

		return $this;
	}

	/**
	 * Sets the type as foes
	 *
	 * @return RelationshipRequestCount
	 */
	public function setFoes() {
		$this->type = 2;

		return $this;
	}

	/**
	 * Purge the cache of the amount of open relationship requests for a user.
	 */
	public function clear() {
		$this->cache->delete( $this->makeKey() );
	}

	/**
	 * Get the amount of open user relationship requests; first tries cache,
	 * and if that fails, fetches the count from the database.
	 *
	 * @return int
	 */
	public function get() {
		$data = $this->getFromCache();

		if ( $data != '' ) {
			if ( $data == -1 ) {
				$data = 0;
			}
			$count = $data;
		} else {
			$count = $this->getFromDatabase();
		}

		return $count;
	}

	/**
	 * Get the amount of open user relationship requests for a user from the
	 * database and cache it.
	 *
	 * @return int
	 */
	private function getFromDatabase() {
		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "Got open request count (type={relType}) for user name {userName} from DB\n", [
			'relType' => $this->type,
			'userName' => $this->user->getName()
		] );

		$dbr = wfGetDB( DB_REPLICA );
		$requestCount = 0;

		$s = $dbr->selectRow(
			'user_relationship_request',
			[ 'COUNT(*) AS count' ],
			[
				'ur_actor_to' => $this->user->getActorId(),
				'ur_status' => 0,
				'ur_type' => $this->type
			],
			__METHOD__
		);

		if ( $s !== false ) {
			$requestCount = $s->count;
		}

		$this->cache->set( $this->makeKey(), $requestCount );

		return $requestCount;
	}

	/**
	 * Get the amount of open user relationship requests from cache.
	 *
	 * @return int|false
	 */
	private function getFromCache() {
		$data = $this->cache->get( $this->makeKey() );

		if ( $data != '' ) {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got open request count of {data} (type={relType}) for user name {userName} from cache\n", [
				'data' => $data,
				'relType' => $this->type,
				'userName' => $this->user->getName()
			] );

		}

		return $data;
	}

	/**
	 * @return string
	 */
	private function makeKey() {
		return $this->cache->makeKey(
			'user_relationship',
			'open_request',
			$this->type,
			$this->user->getActorId()
		);
	}
}
