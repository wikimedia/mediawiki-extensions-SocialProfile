<?php

use MediaWiki\Logger\LoggerFactory;

class UserStats {
	/**
	 * @var string $user_name Name of the person whose stats we're dealing with here
	 */
	public $user_name;

	/**
	 * @var int $user_id User ID of the aforementioned person
	 */
	public $user_id;

	/**
	 * @param int $user_id ID number of the user that we want to track stats for
	 * @param string|null $user_name User's name; if not supplied, then the user ID will be used to get the user name from DB.
	 */
	function __construct( $user_id, $user_name ) {
		$this->user_id = $user_id;
		if ( !$user_name ) {
			$user = User::newFromId( $this->user_id );
			$user->loadFromDatabase();
			$user_name = $user->getName();
		}
		$this->user_name = $user_name;
	}

	/**
	 * Retrieves per-user statistics, either from Memcached or from the database
	 */
	public function getUserStats() {
		$stats = $this->getUserStatsCache();
		if ( !$stats ) {
			$stats = $this->getUserStatsDB();
		}
		return $stats;
	}

	/**
	 * Retrieves cached per-user statistics from Memcached, if possible
	 *
	 * @return array
	 */
	public function getUserStatsCache() {
		global $wgMemc;
		$key = $wgMemc->makeKey( 'user', 'stats', $this->user_id );
		$data = $wgMemc->get( $key );
		if ( $data ) {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got user stats for {user_name} from cache\n", [
				'user_name' => $this->user_name
			] );

			return $data;
		}
	}

	/**
	 * Retrieves per-user statistics from the database
	 *
	 * @return array
	 */
	public function getUserStatsDB() {
		global $wgMemc;

		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "Got user stats for {user_name} from DB\n", [
			'user_name' => $this->user_name
		] );

		$dbr = wfGetDB( DB_MASTER );
		$res = $dbr->select(
			'user_stats',
			'*',
			[ 'stats_user_id' => $this->user_id ],
			__METHOD__,
			[
				'LIMIT' => 1,
				'OFFSET' => 0
			]
		);
		$row = $dbr->fetchObject( $res );
		$stats = [];
		$stats['edits'] = $row->stats_edit_count ?? 0;
		$stats['votes'] = $row->stats_vote_count ?? 0;
		$stats['comments'] = $row->stats_comment_count ?? 0;
		$stats['comment_score_plus'] = $row->stats_comment_score_positive_rec ?? 0;
		$stats['comment_score_minus'] = $row->stats_comment_score_negative_rec ?? 0;
		$stats['comment_score'] = ( $stats['comment_score_plus'] - $stats['comment_score_minus'] );
		$stats['opinions_created'] = $row->stats_opinions_created ?? 0;
		$stats['opinions_published'] = $row->stats_opinions_published ?? 0;
		$stats['points'] = $row->stats_total_points ?? 0;
		$stats['recruits'] = $row->stats_referrals_completed ?? 0;
		$stats['challenges_won'] = $row->stats_challenges_won ?? 0;
		$stats['friend_count'] = $row->stats_friends_count ?? 0;
		$stats['foe_count'] = $row->stats_foe_count ?? 0;
		$stats['user_board'] = $row->user_board_count ?? 0;
		$stats['user_board_priv'] = $row->user_board_count_priv ?? 0;
		$stats['user_board_sent'] = $row->user_board_sent ?? 0;
		$stats['weekly_wins'] = $row->stats_weekly_winner_count ?? 0;
		$stats['monthly_wins'] = $row->stats_monthly_winner_count ?? 0;
		$stats['poll_votes'] = $row->stats_poll_votes ?? 0;
		$stats['currency'] = $row->stats_currency ?? 0;
		$stats['picture_game_votes'] = $row->stats_picturegame_votes ?? 0;
		$stats['quiz_created'] = $row->stats_quiz_questions_created ?? 0;
		$stats['quiz_answered'] = $row->stats_quiz_questions_answered ?? 0;
		$stats['quiz_correct'] = $row->stats_quiz_questions_correct ?? 0;
		$stats['quiz_points'] = $row->stats_quiz_points ?? 0;
		$stats['quiz_correct_percent'] = number_format( ( $row->stats_quiz_questions_correct_percent ?? 0 ) * 100, 2 );
		$stats['user_status_count'] = $row->user_status_count ?? 0;
		if ( !$row ) {
			$stats['points'] = '1000';
		}

		$key = $wgMemc->makeKey( 'user', 'stats', $this->user_id );
		$wgMemc->set( $key, $stats );
		return $stats;
	}

	/**
	 * Gets the amount of friends relative to points.
	 *
	 * @param int $user_id user ID
	 * @param int $points
	 * @param int $limit LIMIT for SQL queries, defaults to 3
	 * @param int $condition if 1, the query operator for ORDER BY clause
	 * 	will be set to > and the results are
	 * 	ordered in ascending order, otherwise it'll
	 * 	be set to < and results are ordered in
	 * 	descending order
	 * @return array
	 */
	static function getFriendsRelativeToPoints( $user_id, $points, $limit = 3, $condition = 1 ) {
		if ( $condition == 1 ) {
			$op = '>';
			$sort = 'ASC';
		} else {
			$op = '<';
			$sort = 'DESC';
		}

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'user_stats', 'user_relationship' ],
			[ 'stats_user_id', 'stats_user_name', 'stats_total_points' ],
			[
				'r_user_id' => $user_id,
				"stats_total_points {$op} {$points}"
			],
			__METHOD__,
			[
				'ORDER BY' => "stats_total_points {$sort}",
				'LIMIT' => $limit
			],
			[
				'user_relationship' => [
					'INNER JOIN', 'stats_user_id = r_user_id_relation'
				]
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

		if ( $condition == 1 ) {
			$list = array_reverse( $list );
		}

		return $list;
	}
}
