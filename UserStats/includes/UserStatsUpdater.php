<?php

use MediaWiki\MediaWikiServices;

/**
 * Logic to update or populate the user_stats table
 * Called from the UpdateEditCounts special page and a maintenance script
 *
 * @see https://phabricator.wikimedia.org/T341098
 * @since July 2023
 */

class UserStatsUpdater {

	/**
	 * Perform the queries necessary to update the social point counts and
	 * purge memcached entries.
	 *
	 * @param callable|null $progressCallback Callback to print progress for
	 *                      each user. Params: $userName, $editCount
	 */
	public function updateMainEditsCount( callable $progressCallback = null ) {
		global $wgNamespacesForEditPoints;

		$additionalConds = [];
		// If points are given out for editing non-main namespaces, take that
		// into account, too.
		if (
			isset( $wgNamespacesForEditPoints ) &&
			is_array( $wgNamespacesForEditPoints )
		) {
			$additionalConds['page_namespace'] = $wgNamespacesForEditPoints;
		}

		$MW139orEarlier = version_compare( MW_VERSION, '1.39', '<' );

		$dbr = wfGetDB( DB_REPLICA );
		$dbw = wfGetDB( DB_PRIMARY );

		// Traverse the user list. This means anons will be skipped
		$resUsers = $dbr->select(
			[ 'user', 'actor' ],
			[ 'user_id', 'actor_id', 'user_editcount' ],
			[],
			__METHOD__,
			[],
			[
				'actor' => [ 'INNER JOIN', 'actor_user = user_id' ]
			]

		);

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		foreach ( $resUsers as $rowUser ) {
			$actorId = $rowUser->actor_id;
			$user = User::newFromId( $rowUser->user_id );
			$user->load();

			// Shortcut to skip a query to the revision table if the user
			// table already tells us the user has no edits
			// Exclude bots too, since they usually clutter the top user lists
			if ( (int)$rowUser->user_editcount === 0 || $user->isBot() ) {
				$editCount = 0;
			} else {
				if ( $MW139orEarlier ) {
					$whereConds = [
						'revactor_actor' => $actorId,
					];
					$editCount = (int)$dbr->selectField(
						[ 'revision_actor_temp', 'revision', 'page' ],
						'COUNT(*) AS the_count',
						array_merge( $whereConds, $additionalConds ),
						__METHOD__,
						[],
						[
							'revision_actor_temp' => [ 'INNER JOIN', 'revactor_rev = rev_id' ],
							'page' => [ 'INNER JOIN', 'page_id = revactor_page' ]
						]
					);
				} else {
					$whereConds = [ 'rev_actor' => $actorId ];
					$editCount = (int)$dbr->selectField(
						[ 'revision', 'page' ],
						'COUNT(*) AS the_count',
						array_merge( $whereConds, $additionalConds ),
						__METHOD__,
						[],
						[
							'page' => [ 'INNER JOIN', 'page_id = rev_page' ]
						]
					);
				}

				$s = $dbw->selectRow(
					'user_stats',
					[ 'stats_actor' ],
					[ 'stats_actor' => $actorId ],
					__METHOD__
				);
				if ( $s === false || !$s->stats_actor ) {
					$dbw->insert(
						'user_stats',
						[
							'stats_actor' => $actorId,
							'stats_total_points' => 1000
						],
						__METHOD__
					);
				}

				$userName = $user->getName();
				if ( $progressCallback ) {
					call_user_func( $progressCallback, $userName, $editCount );
				}

				$dbw->update(
					'user_stats',
					[ 'stats_edit_count = ' . $editCount ],
					[ 'stats_actor' => $actorId ],
					__METHOD__
				);

				// clear stats cache for current user
				$key = $cache->makeKey( 'user', 'stats', 'actor_id', $actorId );
				$cache->delete( $key );
			}
		}
	}

	/**
	 * Update users' total points in the user_stats DB table.
	 *
	 * @return int Amount of users whose records were updated
	 */
	public function updateTotalPoints() {
		$dbw = wfGetDB( DB_PRIMARY );
		$res = $dbw->select(
			[ 'user_stats', 'actor' ],
			[ 'stats_actor', 'stats_total_points' ],
			[],
			__METHOD__,
			[ 'ORDER BY' => 'stats_actor' ],
			[ 'actor' => [ 'JOIN', 'stats_actor = actor_id' ] ]
		);

		$x = 0;
		foreach ( $res as $row ) {
			$x++;
			$stats = new UserStatsTrack( $row->stats_actor );
			$stats->updateTotalPoints();
		}

		return $x;
	}
}
