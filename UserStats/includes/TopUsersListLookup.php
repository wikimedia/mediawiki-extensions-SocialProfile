<?php

class TopUsersListLookup {

	/**
	 * @var int LIMIT for SQL query, defaults to 10
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
			[ 'user_stats', 'actor' ],
			[ 'stats_actor', 'actor_user', 'actor_name', 'stats_total_points' ],
			[ 'stats_actor IS NOT NULL' ],
			__METHOD__,
			[
				'ORDER BY' => 'stats_total_points DESC',
				'LIMIT' => $this->getLimit()
			],
			[ 'actor' => [ 'JOIN', 'stats_actor = actor_id' ] ]
		);

		$list = [];
		foreach ( $res as $row ) {
			$list[] = [
				'actor' => $row->stats_actor,
				'user_id' => $row->actor_user,
				'user_name' => $row->actor_name,
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
			[ 'up_actor', 'up_points' ],
			[ 'up_actor IS NOT NULL' ],
			__METHOD__,
			[
				'ORDER BY' => 'up_points DESC',
				'LIMIT' => $limit
			]
		);

		$loop = 0;
		$list = [];
		foreach ( $res as $row ) {
			$user = User::newFromId( $row->up_actor );
			// Ensure that the user exists for real.
			// Otherwise we'll be happily displaying entries for users that
			// once existed by no longer do (account merging is a thing,
			// sadly), since user_stats entries for users are *not* purged
			// and/or merged during the account merge process (which is a
			// different bug with a different extension).
			// Also ignore flagged bot accounts, no point in showing those
			// in the top lists.
			$exists = $user->load();

			if ( !$user->isBlocked() && $exists && !$user->isBot() ) {
				$list[] = [
					'actor' => $row->up_actor,
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
