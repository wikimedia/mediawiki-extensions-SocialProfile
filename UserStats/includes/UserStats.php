<?php

use MediaWiki\Logger\LoggerFactory;

class UserStats {
	/**
	 * @param int $user_id ID number of the user that we want to track stats for
	 * @param mixed $user_name user's name; if not supplied, then the user ID will be used to get the user name from DB.
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

	static $stats_name = [
		'monthly_winner_count' => 'Monthly Wins',
		'weekly_winner_count' => 'Weekly Wins',
		'vote_count' => 'Votes',
		'edit_count' => 'Edits',
		'comment_count' => 'Comments',
		'referrals_completed' => 'Referrals',
		'friends_count' => 'Friends',
		'foe_count' => 'Foes',
		'opinions_published' => 'Published Opinions',
		'opinions_created' => 'Opinions',
		'comment_score_positive_rec' => 'Thumbs Up',
		'comment_score_negative_rec' => 'Thumbs Down',
		'comment_score_positive_given' => 'Thumbs Up Given',
		'comment_score_negative_given' => 'Thumbs Down Given',
		'gifts_rec_count' => 'Gifts Received',
		'gifts_sent_count' => 'Gifts Sent'
	];

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
		$stats['edits'] = isset( $row->stats_edit_count ) ? $row->stats_edit_count : 0;
		$stats['votes'] = isset( $row->stats_vote_count ) ? $row->stats_vote_count : 0;
		$stats['comments'] = isset( $row->stats_comment_count ) ? $row->stats_comment_count : 0;
		$stats['comment_score_plus'] = isset( $row->stats_comment_score_positive_rec ) ? $row->stats_comment_score_positive_rec : 0;
		$stats['comment_score_minus'] = isset( $row->stats_comment_score_negative_rec ) ? $row->stats_comment_score_negative_rec : 0;
		$stats['comment_score'] = ( $stats['comment_score_plus'] - $stats['comment_score_minus'] );
		$stats['opinions_created'] = isset( $row->stats_opinions_created ) ? $row->stats_opinions_created : 0;
		$stats['opinions_published'] = isset( $row->stats_opinions_published ) ? $row->stats_opinions_published : 0;
		$stats['points'] = isset( $row->stats_total_points ) ? $row->stats_total_points : 0;
		$stats['recruits'] = isset( $row->stats_referrals_completed ) ? $row->stats_referrals_completed : 0;
		$stats['challenges_won'] = isset( $row->stats_challenges_won ) ? $row->stats_challenges_won : 0;
		$stats['friend_count'] = isset( $row->stats_friends_count ) ? $row->stats_friends_count : 0;
		$stats['foe_count'] = isset( $row->stats_foe_count ) ? $row->stats_foe_count : 0;
		$stats['user_board'] = isset( $row->user_board_count ) ? $row->user_board_count : 0;
		$stats['user_board_priv'] = isset( $row->user_board_count_priv ) ? $row->user_board_count_priv : 0;
		$stats['user_board_sent'] = isset( $row->user_board_sent ) ? $row->user_board_sent : 0;
		$stats['weekly_wins'] = isset( $row->stats_weekly_winner_count ) ? $row->stats_weekly_winner_count : 0;
		$stats['monthly_wins'] = isset( $row->stats_monthly_winner_count ) ? $row->stats_monthly_winner_count : 0;
		$stats['poll_votes'] = isset( $row->stats_poll_votes ) ? $row->stats_poll_votes : 0;
		$stats['currency'] = isset( $row->stats_currency ) ? $row->stats_currency : 0;
		$stats['picture_game_votes'] = isset( $row->stats_picturegame_votes ) ? $row->stats_picturegame_votes : 0;
		$stats['quiz_created'] = isset( $row->stats_quiz_questions_created ) ? $row->stats_quiz_questions_created : 0;
		$stats['quiz_answered'] = isset( $row->stats_quiz_questions_answered ) ? $row->stats_quiz_questions_answered : 0;
		$stats['quiz_correct'] = isset( $row->stats_quiz_questions_correct ) ? $row->stats_quiz_questions_correct : 0;
		$stats['quiz_points'] = isset( $row->stats_quiz_points ) ? $row->stats_quiz_points : 0;
		$stats['quiz_correct_percent'] = number_format( ( isset( $row->stats_quiz_questions_correct_percent ) ? $row->stats_quiz_questions_correct_percent : 0 ) * 100, 2 );
		$stats['user_status_count'] = isset( $row->user_status_count ) ? $row->user_status_count : 0;
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
