<?php

use MediaWiki\Logger\LoggerFactory;

/**
 * This object allows for updating the amount (by increasing,
 * decreasing, and clearing) as well as retrieving the amount
 * of user gifts for a given user.
 */
class UserGiftCount {
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
	 * Purge the cache for increase the amount of new gifts for the user.
	 */
	public function clear() {
		$this->cache->delete( $this->makeKey() );
	}

	/**
	 * Get the amount of new gifts for the user given an ID.
	 *
	 * First tries cache (memcached) and if that succeeds, returns the cached
	 * data. If that fails, the count is fetched from the database.
	 *
	 * UserWelcome.php calls this function.
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
	 * Get the amount of new gifts for the user from cache.
	 * If successful, returns the amount of new gifts.
	 *
	 * @return int|false Amount of new gifts
	 */
	private function getFromCache() {
		$data = $this->cache->get( $this->makeKey() );

		if ( $data != '' ) {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got new gift count of {data} for user name {user_name} from cache\n", [
				'data' => $data,
				'user_name' => $this->user->getName()
			] );

		}

		return $data;
	}

	/**
	 * Get the amount of new gifts for the user from the database and cache it.
	 *
	 * @return int Amount of new gifts
	 */
	private function getFromDatabase() {
		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "Got new gift count for id {user_name} from DB\n", [
			'user_name' => $this->user->getName()
		] );

		$dbr = wfGetDB( DB_REPLICA );
		$newGiftCount = 0;

		$s = $dbr->selectRow(
			'user_gift',
			[ 'COUNT(*) AS count' ],
			[
				'ug_actor_to' => $this->user->getActorId(),
				'ug_status' => 1
			],
			__METHOD__
		);
		if ( $s !== false ) {
			$newGiftCount = $s->count;
		}

		$this->cache->set( $this->makeKey(), $newGiftCount );

		return $newGiftCount;
	}

	/**
	 * @return string
	 */
	private function makeKey() {
		return $this->cache->makeKey( 'user_gifts', 'new_count', 'actor_id', $this->user->getActorId() );
	}
}
