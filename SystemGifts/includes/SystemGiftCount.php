<?php

use MediaWiki\Logger\LoggerFactory;

/**
 * This object allows for increasing, decreasing, and getting
 * the amount of system gifts for a user based on their ID.
 */
class SystemGiftCount {
	/**
	 * @var BagOStuff $cache
	 */
	private $cache;

	/**
	 * @var int $userId
	 */
	private $userId;

	public function __construct( $cache, $userId ) {
		$this->cache = $cache;
		$this->userId = $userId;
	}

	/**
	 * Increase the amount of new system gifts for the user
	 */
	public function increase() {
		$this->cache->incr( $this->makeKey() );
	}

	/**
	 * Decrease the amount of new system gifts for the user
	 */
	public function decrease() {
		$this->cache->decr( $this->makeKey() );
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
	 * @return int Amount of new system gifts
	 */
	private function getFromCache() {
		$data = $this->cache->get( $this->makeKey() );

		if ( $data != '' ) {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got new award count of {data} for {user_id} from cache\n", [
				'data' => $data,
				'user_id' => $this->userId
			] );

			return $data;
		}
	}

	/**
	 * Get the amount of new system gifts for the user from
	 * the database and stores it in memcached.
	 *
	 * @return int Amount of new system gifts
	 */
	private function getFromDatabase() {
		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "Got new award count for id {user_id} from DB\n", [
			'user_id' => $this->userId
		] );

		$dbr = wfGetDB( DB_REPLICA );
		$newCount = 0;
		$s = $dbr->selectRow(
			'user_system_gift',
			[ 'COUNT(*) AS count' ],
			[
				'sg_user_id' => $this->userId,
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
		return $this->cache->makeKey( 'system_gifts', 'new_count', $this->userId );
	}
}