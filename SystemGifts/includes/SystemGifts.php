<?php
/**
 * SystemGifts class
 */
class SystemGifts {

	/**
	 * All member variables should be considered private
	 * Please use the accessor functions
	 */
	private $categories = [
		'edit' => 1,
		'vote' => 2,
		'comment' => 3,
		'comment_plus' => 4,
		'opinions_created' => 5,
		'opinions_pub' => 6,
		'referral_complete' => 7,
		'friend' => 8,
		'foe' => 9,
		'challenges_won' => 10,
		'gift_rec' => 11,
		'points_winner_weekly' => 12,
		'points_winner_monthly' => 13,
		'quiz_points' => 14
	];

	/**
	 * Accessor for the private $categories variable; used by
	 * SpecialSystemGiftManager.php at least.
	 */
	public function getCategories() {
		return $this->categories;
	}

	/**
	 * Adds awards for all registered users, updates statistics and purges
	 * caches.
	 * Special:PopulateAwards calls this function
	 */
	public function updateSystemGifts() {
		global $wgOut, $wgMemc;

		$dbw = wfGetDB( DB_MASTER );
		$stats = new UserStatsTrack( 1, '' );
		$this->categories = array_flip( $this->categories );

		$res = $dbw->select(
			'system_gift',
			[ 'gift_id', 'gift_category', 'gift_threshold', 'gift_name' ],
			[],
			__METHOD__,
			[ 'ORDER BY' => 'gift_category, gift_threshold ASC' ]
		);

		$x = 0;
		foreach ( $res as $row ) {
			if ( $row->gift_category ) {
				$res2 = $dbw->select(
					'user_stats',
					[ 'stats_user_id', 'stats_user_name' ],
					[
						$stats->stats_fields[$this->categories[$row->gift_category]] .
							" >= {$row->gift_threshold}",
						'stats_user_id <> 0'
					],
					__METHOD__
				);

				foreach ( $res2 as $row2 ) {
					// @todo FIXME: this needs refactoring and badly (see T131016 for details)
					if ( $this->doesUserHaveGift( $row2->stats_user_id, $row->gift_id ) == false ) {
						$dbw->insert(
							'user_system_gift',
							[
								'sg_gift_id' => $row->gift_id,
								'sg_user_id' => $row2->stats_user_id,
								'sg_user_name' => $row2->stats_user_name,
								'sg_status' => 0,
								'sg_date' => date( 'Y-m-d H:i:s', time() - ( 60 * 60 * 24 * 3 ) ),
							],
							__METHOD__
						);

						$sg_key = $wgMemc->makeKey( 'user', 'profile', 'system_gifts', "{$row2->stats_user_id}" );
						$wgMemc->delete( $sg_key );

						// Update counters (https://phabricator.wikimedia.org/T29981)
						UserSystemGifts::incGiftGivenCount( $row->gift_id );

						$wgOut->addHTML( wfMessage(
							'ga-user-got-awards',
							$row2->stats_user_name,
							$row->gift_name
						)->escaped() . '<br />' );
						$x++;
					}
				}
			}
		}

		$wgOut->addHTML( wfMessage( 'ga-awards-given-out' )->numParams( $x )->parse() );
	}

	/**
	 * Checks if the given user has then given award (system gift) via their ID
	 * numbers.
	 *
	 * @param int $user_id User ID number
	 * @param int $gift_id Award (system gift) ID number
	 * @return bool|int False if the user doesn't have the specified
	 * gift, else the gift's ID number
	 */
	public function doesUserHaveGift( $user_id, $gift_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_system_gift',
			[ 'sg_gift_id' ],
			[ 'sg_gift_id' => $gift_id, 'sg_user_id' => $user_id ],
			__METHOD__
		);
		if ( $s === false ) {
			return false;
		} else {
			return $s->sg_gift_id;
		}
	}

	/**
	 * Adds a new system gift to the database.
	 *
	 * @param mixed $name Gift name
	 * @param mixed $description Gift description
	 * @param int $category See the $categories class member variable
	 * @param int $threshold Threshold number (i.e. 50 or 100 or whatever)
	 * @return int The inserted gift's ID number
	 */
	public function addGift( $name, $description, $category, $threshold ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'system_gift',
			[
				'gift_name' => $name,
				'gift_description' => $description,
				'gift_category' => $category,
				'gift_threshold' => $threshold,
				'gift_createdate' => date( 'Y-m-d H:i:s' ),
			],
			__METHOD__
		);
		return $dbw->insertId();
	}

	/**
	 * Updates the data for a system gift.
	 *
	 * @param int $id System gift unique ID number
	 * @param mixed $name Gift name
	 * @param mixed $description Gift description
	 * @param $category
	 * @param $threshold
	 */
	public function updateGift( $id, $name, $description, $category, $threshold ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'system_gift',
			/* SET */[
				'gift_name' => $name,
				'gift_description' => $description,
				'gift_category' => $category,
				'gift_threshold' => $threshold,
			],
			/* WHERE */[ 'gift_id' => $id ],
			__METHOD__
		);
	}

	public function doesGiftExistForThreshold( $category, $threshold ) {
		$dbr = wfGetDB( DB_REPLICA );

		$awardCategory = 0;
		if ( isset( $this->categories[$category] ) ) {
			$awardCategory = $this->categories[$category];
		}

		$s = $dbr->selectRow(
			'system_gift',
			[ 'gift_id' ],
			[
				'gift_category' => $awardCategory,
				'gift_threshold' => $threshold
			],
			__METHOD__
		);

		if ( $s === false ) {
			return false;
		} else {
			return $s->gift_id;
		}
	}

	/**
	 * Fetches the system gift with the ID $id from the database
	 *
	 * @param int $id ID number of the system gift to be fetched
	 * @return array Array of gift information, including, but not limited to,
	 * the gift ID, its name, description, category, threshold
	 */
	static function getGift( $id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'system_gift',
			[
				'gift_id', 'gift_name', 'gift_description', 'gift_category',
				'gift_threshold', 'gift_given_count'
			],
			[ 'gift_id' => $id ],
			__METHOD__,
			[ 'LIMIT' => 1 ]
		);
		$row = $dbr->fetchObject( $res );
		if ( $row ) {
			$gift['gift_id'] = $row->gift_id;
			$gift['gift_name'] = $row->gift_name;
			$gift['gift_description'] = $row->gift_description;
			$gift['gift_category'] = $row->gift_category;
			$gift['gift_threshold'] = $row->gift_threshold;
			$gift['gift_given_count'] = $row->gift_given_count;
		}
		return $gift;
	}

	/**
	 * Gets the amount of available system gifts from the database.
	 *
	 * @return int The amount of all system gifts on the database
	 */
	static function getGiftCount() {
		$dbr = wfGetDB( DB_REPLICA );
		$gift_count = 0;
		$s = $dbr->selectRow(
			'system_gift',
			[ 'COUNT(*) AS count' ],
			[],
			__METHOD__
		);
		if ( $s !== false ) {
			$gift_count = $s->count;
		}
		return $gift_count;
	}
}
