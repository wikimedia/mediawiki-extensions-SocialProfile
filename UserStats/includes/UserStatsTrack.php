<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

class UserStatsTrack {

	/**
	 * @var array Literally $wgUserStatsPointValues
	 */
	public $point_values;

	/**
	 * @var User The user (object) whose stats we're dealing with here
	 */
	public $user;

	/**
	 * @var array For referencing purposes
	 *   key: statistic name in wgUserStatsPointValues -> database column name in user_stats table
	 */
	public $stats_fields = [
		'edit' => 'stats_edit_count',
		'vote' => 'stats_vote_count',
		'comment' => 'stats_comment_count',
		'comment_plus' => 'stats_comment_score_positive_rec',
		'comment_neg' => 'stats_comment_score_negative_rec',
		'comment_give_plus' => 'stats_comment_score_positive_given',
		'comment_give_neg' => 'stats_comment_score_negative_given',
		'comment_ignored' => 'stats_comment_blocked',
		'opinions_created' => 'stats_opinions_created',
		'opinions_pub' => 'stats_opinions_published',
		'referral_complete' => 'stats_referrals_completed',
		'friend' => 'stats_friends_count',
		'foe' => 'stats_foe_count',
		'gift_rec' => 'stats_gifts_rec_count',
		'gift_sent' => 'stats_gifts_sent_count',
		'challenges' => 'stats_challenges_count',
		'challenges_won' => 'stats_challenges_won',
		'challenges_rating_positive' => 'stats_challenges_rating_positive',
		'challenges_rating_negative' => 'stats_challenges_rating_negative',
		'points_winner_weekly' => 'stats_weekly_winner_count',
		'points_winner_monthly' => 'stats_monthly_winner_count',
		'total_points' => 'stats_total_points',
		'user_image' => 'stats_user_image_count',
		'user_board_count' => 'user_board_count',
		'user_board_count_priv' => 'user_board_count_priv',
		'user_board_sent' => 'user_board_sent',
		'picturegame_created' => 'stats_picturegame_created',
		'picturegame_vote' => 'stats_picturegame_votes',
		'poll_vote' => 'stats_poll_votes',
		'user_status_count' => 'user_status_count',
		'quiz_correct' => 'stats_quiz_questions_correct',
		'quiz_answered' => 'stats_quiz_questions_answered',
		'quiz_created' => 'stats_quiz_questions_created',
		'quiz_points' => 'stats_quiz_points',
		'currency' => 'stats_currency',
		'links_submitted' => 'stats_links_submitted',
		'links_approved' => 'stats_links_approved'
	];

	/**
	 * Constructor -- can be called with:
	 * - a User object
	 * - just an actor ID
	 * - user ID + user name combo (legacy b/c)
	 * - user ID + empty string (weird legacy special case)
	 */
	public function __construct() {
		global $wgUserStatsPointValues;

		$args = func_get_args();
		if ( count( $args ) < 2 ) {
			// Maybe it's an actor ID?
			if ( $args[0] instanceof User ) {
				$this->user = $args[0];
			} else {
				$this->user = User::newFromActorId( $args[0] );
			}
		} elseif ( count( $args ) === 2 ) {
			// Old-school style of passing an UID and a name, in that order
			$this->user = User::newFromId( $args[0] );
		}

		$this->user->load();

		$this->point_values = $wgUserStatsPointValues;

		$this->initStatsTrack();
	}

	/**
	 * Checks if records for the given user are present in user_stats table and if not, adds them
	 */
	private function initStatsTrack() {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_stats',
			[ 'stats_actor' ],
			[ 'stats_actor' => $this->user->getActorId() ],
			__METHOD__
		);

		if ( $s === false ) {
			$this->addStatRecord();
		}
	}

	/**
	 * Adds a record for the given user into the user_stats table
	 */
	private function addStatRecord() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'user_stats',
			[
				'stats_actor' => $this->user->getActorId(),
				'stats_total_points' => 1000
			],
			__METHOD__
		);
	}

	/**
	 * Deletes cache entries
	 */
	public function clearCache() {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		// clear stats cache for current user
		$key = $cache->makeKey( 'user', 'stats', 'actor_id', $this->user->getActorId() );
		$cache->delete( $key );
	}

	/**
	 * Increase a given social statistic field by $val.
	 *
	 * @param string $field Field name key, e.g. 'edit' for referencing the 'stats_edit_count' field
	 *   in user_stats database table
	 * @param int $val Increase $field by this amount, defaults to 1
	 */
	function incStatField( $field, $val = 1 ) {
		global $wgUserStatsTrackWeekly, $wgUserStatsTrackMonthly;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		if ( !$this->user->isBot() && !$this->user->isAnon() && $this->stats_fields[$field] ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->update(
				'user_stats',
				[ $this->stats_fields[$field] . '=' . $this->stats_fields[$field] . "+{$val}" ],
				[ 'stats_actor' => $this->user->getActorId() ],
				__METHOD__
			);
			$this->updateTotalPoints();

			$this->clearCache();

			// update weekly/monthly points
			if ( isset( $this->point_values[$field] ) && !empty( $this->point_values[$field] ) ) {
				if ( $wgUserStatsTrackWeekly ) {
					$this->updateWeeklyPoints( $this->point_values[$field] );
				}
				if ( $wgUserStatsTrackMonthly ) {
					$this->updateMonthlyPoints( $this->point_values[$field] );
				}
			}

			$s = $dbw->selectRow(
				'user_stats',
				[ $this->stats_fields[$field] ],
				[ 'stats_actor' => $this->user->getActorId() ],
				__METHOD__
			);
			$stat_field = $this->stats_fields[$field];
			$field_count = $s->$stat_field;

			$key = $cache->makeKey( 'system_gift', 'id', $field . '-' . $field_count );
			$data = $cache->get( $key );

			if ( $data ) {
				$logger = LoggerFactory::getInstance( 'SocialProfile' );
				$logger->debug( "Got system gift ID from cache\n" );

				$systemGiftID = $data;
			} else {
				$g = new SystemGifts();
				$systemGiftID = $g->doesGiftExistForThreshold( $field, $field_count );
				if ( $systemGiftID ) {
					$cache->set( $key, $systemGiftID, 60 * 30 );
				}
			}

			if ( $systemGiftID ) {
				$sg = new UserSystemGifts( $this->user );
				$sg->sendSystemGift( $systemGiftID );
			}
		}
	}

	/**
	 * Decrease a given social statistic field by $val.
	 *
	 * @param string $field Field name key, e.g. 'edit' for referencing the 'stats_edit_count' field
	 *   in user_stats database table
	 * @param int $val Decrease $field by this amount, defaults to 1
	 */
	function decStatField( $field, $val = 1 ) {
		global $wgUserStatsTrackWeekly, $wgUserStatsTrackMonthly;

		if ( !$this->user->isBot() && !$this->user->isAnon() && $this->stats_fields[$field] ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->update(
				'user_stats',
				[ $this->stats_fields[$field] . '=' . $this->stats_fields[$field] . "-{$val}" ],
				[ 'stats_actor' => $this->user->getActorId() ],
				__METHOD__
			);

			if ( !empty( $this->point_values[$field] ) ) {
				$this->updateTotalPoints();
				if ( $wgUserStatsTrackWeekly ) {
					$this->updateWeeklyPoints( 0 - ( $this->point_values[$field] ) );
				}
				if ( $wgUserStatsTrackMonthly ) {
					$this->updateMonthlyPoints( 0 - ( $this->point_values[$field] ) );
				}
			}

			$this->clearCache();
		}
	}

	/**
	 * Updates the comment scores for the current user.
	 *
	 * @param int $voteType
	 * - if 1, sets the amount of positive comment scores
	 * - ..else sets the amount of negative comment scores
	 */
	function updateCommentScoreRec( $voteType ) {
		if ( !$this->user->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );

			if ( $voteType == 1 ) {
				$columnName = 'stats_comment_score_positive_rec';
			} else {
				$columnName = 'stats_comment_score_negative_rec';
			}

			$commentIDs = $dbw->select(
				'Comments',
				'CommentID',
				[ 'Comment_actor' => $this->user->getActorId() ],
				__METHOD__
			);

			$ids = [];
			foreach ( $commentIDs as $commentID ) {
				$ids[] = $commentID->CommentID;
			}

			$comments = $dbw->selectField(
				'Comments_Vote',
				'COUNT(*) AS CommentVoteCount',
				[
					'Comment_Vote_ID' => $ids,
					'Comment_Vote_Score' => $voteType
				],
				__METHOD__
			);

			$res = $dbw->update(
				'user_stats',
				[ $columnName => $comments ],
				[ 'stats_actor' => $this->user->getActorId() ],
				__METHOD__
			);

			$this->clearCache();
		}
	}

	/**
	 * Updates the amount of relationships (friends or foes) if the user isn't
	 * an anonymous one.
	 * This is called by UserRelationship::removeRelationshipBy(), which
	 * in turn is called when removing friends or foes.
	 *
	 * @param int $relType 1 for updating friends
	 */
	function updateRelationshipCount( $relType ) {
		if ( !$this->user->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );
			if ( $relType == 1 ) {
				$col = 'stats_friends_count';
			} else {
				$col = 'stats_foe_count';
			}
			$relationships = $dbw->selectField(
				'user_relationship',
				'COUNT(*) AS rel_count',
				[ 'r_actor' => $this->user->getActorId(), 'r_type' => $relType ],
				__METHOD__
			);
			$res = $dbw->update(
				'user_stats',
				[ $col => $relationships ],
				[ 'stats_actor' => $this->user->getActorId() ],
				__METHOD__,
				[ 'LOW_PRIORITY' ]
			);
		}
	}

	public function updateWeeklyPoints( $points ) {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			'user_points_weekly',
			'up_actor',
			[ 'up_actor' => $this->user->getActorId() ],
			__METHOD__
		);
		$row = $dbw->fetchObject( $res );

		if ( !$row ) {
			$this->addWeekly();
		}
		if ( is_int( $points ) ) {
			$dbw->update(
				'user_points_weekly',
				[ 'up_points=up_points+' . $points ],
				[ 'up_actor' => $this->user->getActorId() ],
				__METHOD__
			);
		}
	}

	/**
	 * Adds a record about the current user to the user_points_weekly database
	 * table.
	 */
	public function addWeekly() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'user_points_weekly',
			[ 'up_actor' => $this->user->getActorId() ],
			__METHOD__
		);
	}

	public function updateMonthlyPoints( $points ) {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			'user_points_monthly',
			'up_actor',
			[ 'up_actor' => $this->user->getActorId() ],
			__METHOD__
		);
		$row = $dbw->fetchObject( $res );
		if ( !$row ) {
			$this->addMonthly();
		}
		if ( is_int( $points ) ) {
			$dbw->update(
				'user_points_monthly',
				[ 'up_points=up_points+' . $points ],
				[ 'up_actor' => $this->user->getActorId() ],
				__METHOD__
			);
		}
	}

	/**
	 * Adds a record about the current user to the user_points_monthly database
	 * table.
	 */
	public function addMonthly() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'user_points_monthly',
			[ 'up_actor' => $this->user->getActorId() ],
			__METHOD__
		);
	}

	/**
	 * Updates the total amount of points the user has.
	 *
	 * @return array
	 */
	public function updateTotalPoints() {
		global $wgUserLevels;

		if ( $this->user->isAnon() ) {
			return [];
		}

		$stats_data = [];
		if ( is_array( $wgUserLevels ) ) {
			// Load points before update
			$stats = new UserStats( $this->user );
			$stats_data = $stats->getUserStats();
			$points_before = $stats_data['points'];

			// Load Honorific Level before update
			$user_level = new UserLevel( $points_before );
			$level_number_before = $user_level->getLevelNumber();
		}

		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			'user_stats',
			'*',
			[ 'stats_actor' => $this->user->getActorId() ],
			__METHOD__
		);
		$row = $dbw->fetchObject( $res );
		if ( $row ) {
			// recaculate point total
			$new_total_points = 1000;

			if ( $this->point_values ) {
				foreach ( $this->point_values as $point_field => $point_value ) {
					if ( $this->stats_fields[$point_field] ) {
						$field = $this->stats_fields[$point_field];
						$new_total_points += $point_value * $row->$field;
					}
				}
			}

			$dbw->update(
				'user_stats',
				[ 'stats_total_points' => $new_total_points ],
				[ 'stats_actor' => $this->user->getActorId() ],
				__METHOD__
			);

			// If user levels is in settings, check to see if user advanced with update
			if ( is_array( $wgUserLevels ) ) {
				// Get New Honorific Level
				$user_level = new UserLevel( $new_total_points );
				$level_number_after = $user_level->getLevelNumber();

				// Check if the user advanced to a new level on this update
				if ( $level_number_after > $level_number_before ) {
					$m = new UserSystemMessage();
					$m->addMessage(
						$this->user,
						UserSystemMessage::TYPE_LEVELUP,
						wfMessage( 'level-advanced-to', $user_level->getLevelName() )->inContentLanguage()->parse()
					);
					$m->sendAdvancementNotificationEmail(
						$this->user,
						$user_level->getLevelName()
					);

					if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
						EchoEvent::create( [
							'type' => 'social-level-up',
							'agent' => $this->user,
							'extra' => [
								'notifyAgent' => true, // backwards compatibility for MW 1.32 and below
								'new-level' => $user_level->getLevelName()
							]
						] );
					}
				}
			}

			$this->clearCache();
		}

		return $stats_data;
	}
}
