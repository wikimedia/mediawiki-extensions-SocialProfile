<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

class UserStats {
	/**
	 * @var User User object whose stats we're dealing with here
	 */
	public $user;

	/**
	 * @param User|int $user User instance object (preferred) or a user ID
	 * @param string|null $user_name User's name [legacy, unused]
	 */
	public function __construct( $user, $user_name = '' ) {
		if ( $user instanceof User ) {
			$this->user = $user;
		} else {
			$this->user = User::newFromId( $user );
		}

		$this->user->load();
		$this->user_id = $this->user->getId();
		$this->user_name = $this->user->getName();
	}

	/**
	 * Retrieves per-user statistics, either from Memcached or from the database
	 *
	 * @return array
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
	 * @return array|false
	 */
	private function getUserStatsCache() {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$key = $cache->makeKey( 'user', 'stats', 'actor_id', $this->user->getActorId() );
		$data = $cache->get( $key );
		if ( $data ) {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got user stats for {user_name} from cache\n", [
				'user_name' => $this->user->getName()
			] );
		}
		return $data;
	}

	/**
	 * Retrieves per-user statistics from the database
	 *
	 * @return array
	 */
	public function getUserStatsDB() {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "Got user stats for {user_name} from DB\n", [
			'user_name' => $this->user->getName()
		] );

		$dbr = wfGetDB( DB_MASTER );
		$res = $dbr->select(
			'user_stats',
			'*',
			[ 'stats_actor' => $this->user->getActorId() ],
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

		$key = $cache->makeKey( 'user', 'stats', 'actor_id', $this->user->getActorId() );
		$cache->set( $key, $stats );
		return $stats;
	}

	/**
	 * Gets the amount of friends relative to points.
	 *
	 * @param User $user User whose friends to get
	 * @param int $points
	 * @param int $limit LIMIT for SQL queries, defaults to 3
	 * @param int $condition if 1, the query operator for ORDER BY clause
	 * 	will be set to > and the results are
	 * 	ordered in ascending order, otherwise it'll
	 * 	be set to < and results are ordered in
	 * 	descending order
	 * @return array
	 */
	public static function getFriendsRelativeToPoints( $user, $points, $limit = 3, $condition = 1 ) {
		if ( $condition == 1 ) {
			$op = '>';
			$sort = 'ASC';
		} else {
			$op = '<';
			$sort = 'DESC';
		}

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'user_stats', 'user_relationship', 'actor' ],
			[ 'stats_actor', 'actor_name', 'actor_user', 'stats_total_points' ],
			[
				'r_actor' => $user->getActorId(),
				"stats_total_points {$op} {$points}"
			],
			__METHOD__,
			[
				'ORDER BY' => "stats_total_points {$sort}",
				'LIMIT' => $limit
			],
			[
				'user_relationship' => [
					'INNER JOIN', 'stats_actor = r_actor_relation'
				],
				'actor' => [ 'JOIN', 'stats_actor = actor_id' ]
			]
		);

		$list = [];
		foreach ( $res as $row ) {
			$list[] = [
				'user_id' => $row->actor_user,
				'user_name' => $row->actor_name,
				'points' => $row->stats_total_points
			];
		}

		if ( $condition == 1 ) {
			$list = array_reverse( $list );
		}

		return $list;
	}
}
