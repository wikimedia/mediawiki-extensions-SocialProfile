<?php

use MediaWiki\Logger\LoggerFactory;

/**
 * This object allows for updating the amount (by increasing,
 * decreasing, and clearing) as well as retrieving the amount
 * of user gifts for a user based on their ID.
 */
class UserGiftCount {
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
	 * Increase the amount of new gifts for the user.
	 */
	public function increase() {
		$this->cache->incr( $this->makeKey() );
	}

	/**
	 * Decrease the amount of new gifts for the user.
	 */
	public function decrease() {
		$this->cache->decr( $this->makeKey() );
	}

	/**
	 * Clear the new gift counter for the user.
	 * This is done by setting the value of the memcached key to 0.
	 */
	public function clear() {
		$this->cache->set( $this->makeKey(), 0 );
	}

	/**
	 * Get the amount of new gifts for the user given an ID.
	 * First tries cache (memcached) and if that succeeds, returns the cached
	 * data. If that fails, the count is fetched from the database.
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
	 * Get the amount of new gifts for the user with ID = $user_id
	 * from memcached. If successful, returns the amount of new gifts.
	 *
	 * @return int Amount of new gifts
	 */
	private function getFromCache() {
		$data = $this->cache->get( $this->makeKey() );

		if ( $data != '' ) {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got new gift count of {data} for id {user_id} from cache\n", [
				'data' => $data,
				'user_id' => $this->userId
			] );

			return $data;
		}
	}

	/**
	 * Get the amount of new gifts for the user with ID = $user_id from the
	 * database and stores it in memcached.
	 *
	 * @return int Amount of new gifts
	 */
	private function getFromDatabase() {
		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "Got new gift count for id {user_id} from DB\n", [
			'user_id' => $this->userId
		] );

		$dbr = wfGetDB( DB_REPLICA );
		$newGiftCount = 0;

		$s = $dbr->selectRow(
			'user_gift',
			[  'COUNT(*) AS count' ],
			[
				'ug_user_id_to' => $this->userId,
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
		return $this->cache->makeKey( 'user_gifts', 'new_count', $this->userId );
	}
}