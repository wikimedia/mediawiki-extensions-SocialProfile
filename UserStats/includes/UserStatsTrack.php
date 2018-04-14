<?php

use MediaWiki\Logger\LoggerFactory;

class UserStatsTrack {

	// for referencing purposes
	// key: statistic name in wgUserStatsPointValues -> database column name
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
	 * @param $user_id Integer: ID number of the user that we want to track stats for
	 * @param $user_name Mixed: user's name; if not supplied, then the user ID
	 * 							will be used to get the user name from DB.
	 */
	function __construct( $user_id, $user_name = '' ) {
		global $wgUserStatsPointValues;

		$this->user_id = $user_id;
		if ( !$user_name ) {
			$user = User::newFromId( $this->user_id );
			$user->loadFromDatabase();
			$user_name = $user->getName();
		}

		$this->user_name = $user_name;
		$this->point_values = $wgUserStatsPointValues;
		$this->initStatsTrack();
	}

	/**
	 * Checks if records for the given user are present in user_stats table and if not, adds them
	 */
	function initStatsTrack() {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_stats',
			[ 'stats_user_id' ],
			[ 'stats_user_id' => $this->user_id ],
			__METHOD__
		);

		if ( $s === false ) {
			$this->addStatRecord();
		}
	}

	/**
	 * Adds a record for the given user into the user_stats table
	 */
	function addStatRecord() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'user_stats',
			[
				'stats_year_id' => 0,
				'stats_user_id' => $this->user_id,
				'stats_user_name' => $this->user_name,
				'stats_total_points' => 1000
			],
			__METHOD__
		);
	}

	/**
	 * Deletes Memcached entries
	 */
	function clearCache() {
		global $wgMemc;

		// clear stats cache for current user
		$key = $wgMemc->makeKey( 'user', 'stats', $this->user_id );
		$wgMemc->delete( $key );
	}

	/**
	 * Increase a given social statistic field by $val.
	 *
	 * @param $field String: field name in user_stats database table
	 * @param $val Integer: increase $field by this amount, defaults to 1
	 */
	function incStatField( $field, $val = 1 ) {
		global $wgUser, $wgMemc, $wgSystemGifts, $wgUserStatsTrackWeekly, $wgUserStatsTrackMonthly;

		if ( !$wgUser->isBot() && !$wgUser->isAnon() && $this->stats_fields[$field] ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->update(
				'user_stats',
				[ $this->stats_fields[$field] . '=' . $this->stats_fields[$field] . "+{$val}" ],
				[ 'stats_user_id' => $this->user_id  ],
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
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__
			);
			$stat_field = $this->stats_fields[$field];
			$field_count = $s->$stat_field;

			$key = $wgMemc->makeKey( 'system_gift', 'id', $field . '-' . $field_count );
			$data = $wgMemc->get( $key );

			if ( $data ) {
				$logger = LoggerFactory::getInstance( 'SocialProfile' );
				$logger->debug( "Got system gift ID from cache\n" );

				$systemGiftID = $data;
			} else {
				$g = new SystemGifts();
				$systemGiftID = $g->doesGiftExistForThreshold( $field, $field_count );
				if ( $systemGiftID ) {
					$wgMemc->set( $key, $systemGiftID, 60 * 30 );
				}
			}

			if ( $systemGiftID ) {
				$sg = new UserSystemGifts( $this->user_name );
				$sg->sendSystemGift( $systemGiftID );
			}
		}
	}

	/**
	 * Decrease a given social statistic field by $val.
	 *
	 * @param string $field field name in user_stats database table
	 * @param int $val decrease $field by this amount, defaults to 1
	 */
	function decStatField( $field, $val = 1 ) {
		global $wgUser, $wgUserStatsTrackWeekly, $wgUserStatsTrackMonthly;

		if ( !$wgUser->isBot() && !$wgUser->isAnon() && $this->stats_fields[$field] ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->update(
				'user_stats',
				[ $this->stats_fields[$field] . '=' . $this->stats_fields[$field] . "-{$val}" ],
				[ 'stats_user_id' => $this->user_id ],
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
	 * Update the amount of comments the user has submitted.
	 * Comment count is fetched from the Comments table, which is introduced by
	 * the extension with the same name.
	 */
	function updateCommentCount() {
		global $wgUser;

		if ( !$wgUser->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );
			$comments = $dbw->select(
				'Comments',
				'COUNT(*) AS CommentCount',
				[ 'Comment_user_id' => $this->user_id ],
				__METHOD__
			);
			$res = $dbw->update(
				'user_stats',
				[
					'stats_comment_count' => $comments->CommentCount
				],
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__
			);

			$this->clearCache();
		}
	}

	/**
	 * Update the amount of times the user has been added into someone's
	 * comment ignore list by fetching data from the Comments_block table,
	 * which is introduced by the Comments extension.
	 */
	function updateCommentIgnored() {
		global $wgUser;

		if ( !$wgUser->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );
			$blockedComments = $dbw->select(
				'Comments_block',
				'COUNT(*) AS CommentCount',
				[ 'cb_user_id_blocked' => $this->user_id ],
				__METHOD__
			);
			$res = $dbw->update(
				'user_stats',
				[
					'stats_comment_blocked' => $blockedComments->CommentCount
				],
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__
			);

			$this->clearCache();
		}
	}

	/**
	 * Update the amount of edits for a given user
	 * Edit count is fetched from revision table
	 */
	function updateEditCount() {
		global $wgUser;

		if ( !$wgUser->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );
			$edits = $dbw->select(
				'revision',
				'COUNT(*) AS EditsCount',
				[ 'rev_user' => $this->user_id ],
				__METHOD__
			);
			$res = $dbw->update(
				'user_stats',
				[
					'stats_edit_count' => $edits->EditsCount
				],
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__
			);

			$this->clearCache();
		}
	}

	/**
	 * Update the amount of votes for a given user.
	 * Vote count is fetched from the Vote table, which is introduced
	 * by a separate extension.
	 */
	function updateVoteCount() {
		global $wgUser;

		if ( !$wgUser->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );
			$votes = $dbw->select(
				'Vote',
				'COUNT(*) AS VoteCount',
				[ 'vote_user_id' => $this->user_id ],
				__METHOD__
			);
			$res = $dbw->update(
				'user_stats',
				[ 'stats_vote_count' => $votes->VoteCount ],
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__
			);

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
		if ( $this->user_id != 0 ) {
			$dbw = wfGetDB( DB_MASTER );

			if ( $voteType == 1 ) {
				$columnName = 'stats_comment_score_positive_rec';
			} else {
				$columnName = 'stats_comment_score_negative_rec';
			}

			$commentIDs = $dbw->select(
				'Comments',
				'CommentID',
				[ 'Comment_user_id' => $this->user_id ],
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
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__
			);

			$this->clearCache();
		}
	}

	/**
	 * Updates the amount of relationships (friends or foes) if the user isn't
	 * an anonymous one.
	 * This is called by UserRelationship::removeRelationshipByUserID(), which
	 * in turn is called when removing friends or foes.
	 *
	 * @param int $relType 1 for updating friends
	 */
	function updateRelationshipCount( $relType ) {
		global $wgUser;

		if ( !$wgUser->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );
			if ( $relType == 1 ) {
				$col = 'stats_friends_count';
			} else {
				$col = 'stats_foe_count';
			}
			$relationships = $dbw->selectField(
				'user_relationship',
				'COUNT(*) AS rel_count',
				[ 'r_user_id' => $this->user_id, 'r_type' => $relType ],
				__METHOD__
			);
			$res = $dbw->update(
				'user_stats',
				[ $col => $relationships ],
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__,
				[ 'LOW_PRIORITY' ]
			);
		}
	}

	/**
	 * Updates the amount of received gifts if the user isn't an anon.
	 */
	function updateGiftCountRec() {
		global $wgUser;

		if ( !$wgUser->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );
			$gifts = $dbw->select(
				'user_gift',
				'COUNT(*) AS gift_count',
				[ 'ug_user_id_to' => $this->user_id ],
				__METHOD__
			);
			$res = $dbw->update(
				'user_stats',
				[ 'stats_gifts_rec_count' => $gifts->gift_count ],
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__,
				[ 'LOW_PRIORITY' ]
			);
		}
	}

	/**
	 * Updates the amount of sent gifts if the user isn't an anon.
	 */
	function updateGiftCountSent() {
		global $wgUser;

		if ( !$wgUser->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );
			$gifts = $dbw->select(
				'user_gift',
				'COUNT(*) AS gift_count',
				[ 'ug_user_id_from' => $this->user_id ],
				__METHOD__
			);
			$res = $dbw->update(
				'user_stats',
				[ 'stats_gifts_sent_count' => $gifts->gift_count ],
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__,
				[ 'LOW_PRIORITY' ]
			);
		}
	}

	/**
	 * Update the amount of users our user has referred to the wiki.
	 */
	public function updateReferralComplete() {
		global $wgUser;

		if ( !$wgUser->isAnon() ) {
			$dbw = wfGetDB( DB_MASTER );
			$referrals = $dbw->select(
				'user_register_track',
				'COUNT(*) AS thecount',
				[ 'ur_user_id_referral' => $this->user_id ],
				__METHOD__
			);
			$res = $dbw->update(
				'user_stats',
				[ 'stats_referrals_completed' => $referrals->thecount ],
				[ 'stats_user_id' => $this->user_id ],
				__METHOD__,
				[ 'LOW_PRIORITY' ]
			);
		}
	}

	public function updateWeeklyPoints( $points ) {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			'user_points_weekly',
			'up_user_id',
			[ "up_user_id = {$this->user_id}" ],
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
				[ 'up_user_id' => $this->user_id ],
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
			[
				'up_user_id' => $this->user_id,
				'up_user_name' => $this->user_name
			],
			__METHOD__
		);
	}

	public function updateMonthlyPoints( $points ) {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			'user_points_monthly',
			'up_user_id',
			[ "up_user_id = {$this->user_id}" ],
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
				[ 'up_user_id' => $this->user_id ],
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
			[
				'up_user_id' => $this->user_id,
				'up_user_name' => $this->user_name
			],
			__METHOD__
		);
	}

	/**
	 * Updates the total amount of points the user has.
	 *
	 * @return Array
	 */
	public function updateTotalPoints() {
		global $wgUserLevels;

		if ( $this->user_id == 0 ) {
			return [];
		}

		$stats_data = [];
		if ( is_array( $wgUserLevels ) ) {
			// Load points before update
			$stats = new UserStats( $this->user_id, $this->user_name );
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
			[ "stats_user_id = {$this->user_id}" ],
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
				[ 'stats_user_id' => $this->user_id ],
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
						$this->user_name,
						2,
						wfMessage( 'level-advanced-to', $user_level->getLevelName() )->inContentLanguage()->parse()
					);
					$m->sendAdvancementNotificationEmail(
						$this->user_id,
						$user_level->getLevelName()
					);

					if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
						$userFrom = User::newFromId( $this->user_id );

						EchoEvent::create( [
							'type' => 'social-level-up',
							'agent' => $userFrom,
							'extra' => [
								'notifyAgent' => true,
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