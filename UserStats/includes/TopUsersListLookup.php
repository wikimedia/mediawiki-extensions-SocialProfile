<?php

class TopUsersListLookup {

	/**
	 * @var int $limit LIMIT for SQL query, defaults to 10
	 */
	private $limit;

	public function __construct( $limit = 10 ) {
		$this->limit = $limit;
	}

	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Get the list of top users, based on social statistics.
	 *
	 * @return array List of top users, contains the user IDs,
	 * names and amount of points the user has
	 */
	public function getList() {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'user_stats',
			[ 'stats_user_id', 'stats_user_name', 'stats_total_points' ],
			[ 'stats_user_id <> 0' ],
			__METHOD__,
			[
				'ORDER BY' => 'stats_total_points DESC',
				'LIMIT' => $this->getLimit()
			]
		);

		$list = [];
		foreach ( $res as $row ) {
			$list[] = [
				'user_id' => $row->stats_user_id,
				'user_name' => $row->stats_user_name,
				'points' => $row->stats_total_points
			];
		}

		return $list;
	}

	/**
	 * Get the top users for a given period.
	 *
	 * @param string $period Period for which we're getting the top users,
	 * can be either 'weekly' or 'monthly'
	 * @return array List of top users
	 */
	public function getListByTimePeriod( $period = 'weekly' ) {
		if ( $period == 'monthly' ) {
			$pointsTable = 'user_points_monthly';
		} else {
			$pointsTable = 'user_points_weekly';
		}

		$limit = $this->getLimit();
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			$pointsTable,
			[ 'up_user_id', 'up_user_name', 'up_points' ],
			[ 'up_user_id <> 0' ],
			__METHOD__,
			[
				'ORDER BY' => 'up_points DESC',
				'LIMIT' => $limit
			]
		);

		$loop = 0;
		$list = [];
		foreach ( $res as $row ) {
			$user = User::newFromId( $row->up_user_id );
			// Ensure that the user exists for real.
			// Otherwise we'll be happily displaying entries for users that
			// once existed by no longer do (account merging is a thing,
			// sadly), since user_stats entries for users are *not* purged
			// and/or merged during the account merge process (which is a
			// different bug with a different extension).
			// Also ignore flagged bot accounts, no point in showing those
			// in the top lists.
			$exists = $user->loadFromId();

			if ( !$user->isBlocked() && $exists && !$user->isBot() ) {
				$list[] = [
					'user_id' => $row->up_user_id,
					'user_name' => $row->up_user_name,
					'points' => $row->up_points
				];
				$loop++;
			}

			if ( $loop >= $limit ) {
				break;
			}
		}

		return $list;
	}
}
