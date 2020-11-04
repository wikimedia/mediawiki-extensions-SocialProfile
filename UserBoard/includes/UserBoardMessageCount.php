<?php

use MediaWiki\Logger\LoggerFactory;

/**
 * This object allows for updating and retrieving the amount
 * of UserBoard messages for a given user.
 */
class UserBoardMessageCount {
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
	 * Clear the new board messages counter for the user.
	 * This is done by setting the value of the memcached key to 0.
	 */
	public function clear() {
		$this->cache->set( $this->makeKey(), 0 );
	}

	/**
	 * Get the amount of new board messages for the user.
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
	 * Get the amount of new board messages for the user from cache.
	 * If successful, returns the amount of new messages.
	 *
	 * @return int|false Amount of new messages
	 */
	private function getFromCache() {
		$data = $this->cache->get( $this->makeKey() );

		if ( $data != '' ) {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got new message count of {data} for user name {user_name} from cache\n", [
				'data' => $data,
				'user_name' => $this->user->getName()
			] );

		}

		return $data;
	}

	/**
	 * Get the amount of new board messages for the user
	 * from the database.
	 *
	 * @todo This SQL query is commented out, so this function
	 * doesn't quite work the way it should: since $newCount
	 * doesn't get updated, it actually clears the counter instead
	 * as it stays 0.
	 *
	 * @return int Amount of new messages
	 */
	private function getFromDatabase() {
		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "Got new message count for user name {user_name} from DB\n", [
			'user_name' => $this->user->getName()
		] );

		$newCount = 0;
		/*
		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_board',
			[ 'COUNT(*) AS count' ],
			[
				'ug_actor_to' => $this->user->getActorId(),
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
		return $this->cache->makeKey( 'user', 'newboardmessage', 'actor_id', $this->user->getActorId() );
	}
}
