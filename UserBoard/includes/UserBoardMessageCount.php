<?php

use MediaWiki\Logger\LoggerFactory;

/**
 * This object allows for updating and retrieving the amount
 * of UserBoard messages for a given user based on their ID.
 */
class UserBoardMessageCount {
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
	 * Increase the amount of new messages for the user.
	 */
	public function increase() {
		$this->cache->incr( $this->makeKey() );
	}

	/**
	 * Clear the new board messages counter for the user.
	 * This is done by setting the value of the memcached key to 0.
	 */
	public function clear() {
		$this->cache->set( $this->makeKey(), 0 );
	}

	/**
	 * Get the amount of new board messages for the user with ID = $user_id.
	 * First tries cache (memcached) and if that succeeds, returns the cached
	 * data. If that fails, the count is fetched from the database.
	 *
	 * @return int Amount of new messages
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
	 * Get the amount of new board messages for the user with ID = $user_id
	 * from memcached. If successful, returns the amount of new messages.
	 *
	 * @return int Amount of new messages
	 */
	private function getFromCache() {
		$data = $this->cache->get( $this->makeKey() );

		if ( $data != '' ) {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got new message count of {data} for id {user_id} from cache\n", [
				'data' => $data,
				'user_id' => $this->userId
			] );

			return $data;
		}
	}

	/**
	 * Get the amount of new board messages for the user
	 * from the database.
	 *
	 * @TODO: This SQL query is commented out, so this function
	 * doesn't quite work the way it should: since $newCount
	 * doesn't get updated, it actually clears the counter instead
	 * as it stays 0.
	 *
	 * @return int Amount of new messages
	 */
	private function getFromDatabase() {
		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "Got new message count for id {user_id} from DB\n", [
			'user_id' => $this->userId
		] );

		$newCount = 0;
		/*
		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_board',
			[ 'COUNT(*) AS count' ],
			[
				'ug_user_id_to' => $this->userId,
				'ug_status' => 1
			],
			__METHOD__
		);
		if ( $s !== false ) {
			$newCount = $s->count;
		}
		*/

		$this->cache->set( $this->makeKey(), $newCount );

		return $newCount;
	}

	/**
	 * @return string
	 */
	private function makeKey() {
		return $this->cache->makeKey( 'user', 'newboardmessage', $this->userId );
	}
}