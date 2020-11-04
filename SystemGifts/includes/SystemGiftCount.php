<?php

use MediaWiki\Logger\LoggerFactory;

/**
 * This object allows for increasing, decreasing, and getting
 * the amount of system gifts for a given user.
 */
class SystemGiftCount {
	/**
	 * @var WANObjectCache
	 */
	private $cache;

	/**
	 * @var User
	 */
	private $user;

	public function __construct( $cache, $user ) {
		$this->cache = $cache;
		$this->user = $user;
	}

	/**
	 * Purge the cache of the amount of new system gifts for the user
	 */
	public function clear() {
		$this->cache->delete( $this->makeKey() );
	}

	/**
	 * Get the amount of new system gifts for the user.
	 * First tries cache (memcached) and if that succeeds, returns the cached
	 * data. If that fails, the count is fetched from the database.
	 *
	 * @return int Amount of new gifts
	 */
	public function get() {
		$data = $this->getFromCache();

		if ( $data != '' ) {
			$count = $data;
		} else {
			$count = $this->getFromDatabase();
		}
		return $count;
	}

	/**
	 * Get the amount of new system gifts for the user from memcached.
	 * If successful, returns the amount of new system gifts.
	 *
	 * @return int|false Amount of new system gifts
	 */
	private function getFromCache() {
		$data = $this->cache->get( $this->makeKey() );

		if ( $data != '' ) {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got new award count of {data} for {user_name} (ID: {user_id}, actor ID: {actor_id}) from cache\n", [
				'data' => $data,
				'user_name' => $this->user->getName(),
				'user_id' => $this->user->getId(),
				'actor_id' => $this->user->getActorId()
			] );

		}

		return $data;
	}

	/**
	 * Get the amount of new system gifts for the user from
	 * the database and stores it in memcached.
	 *
	 * @return int Amount of new system gifts
	 */
	private function getFromDatabase() {
		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$actorId = $this->user->getActorId();
		$logger->debug( "Got new award count for id {user_name} (ID: {user_id}, actor ID: {actor_id}) from DB\n", [
			'user_name' => $this->user->getName(),
			'user_id' => $this->user->getId(),
			'actor_id' => $actorId
		] );

		$dbr = wfGetDB( DB_REPLICA );
		$newCount = 0;
		$s = $dbr->selectRow(
			'user_system_gift',
			[ 'COUNT(*) AS count' ],
			[
				'sg_actor' => $actorId,
				'sg_status' => 1
			],
			__METHOD__
		);
		if ( $s !== false ) {
			$newCount = $s->count;
		}

		$this->cache->set( $this->makeKey(), $newCount );

		return $newCount;
	}

	/**
	 * @return string
	 */
	private function makeKey() {
		return $this->cache->makeKey( 'system_gifts', 'new_count', 'actor_id', $this->user->getActorId() );
	}
}
