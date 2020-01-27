<?php
/**
 * A special page for updating users' point counts.
 *
 * @file
 * @ingroup Extensions
 */

class UpdateEditCounts extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'UpdateEditCounts', 'updatepoints' );
	}

	/**
	 * Perform the queries necessary to update the social point counts and
	 * purge memcached entries.
	 */
	function updateMainEditsCount() {
		global $wgNamespacesForEditPoints;

		$out = $this->getOutput();

		$whereConds = [
			'actor_user IS NOT NULL'
		];
		// If points are given out for editing non-main namespaces, take that
		// into account, too.
		if (
			isset( $wgNamespacesForEditPoints ) &&
			is_array( $wgNamespacesForEditPoints )
		) {
			foreach ( $wgNamespacesForEditPoints as $pointNamespace ) {
				$whereConds[] = 'page_namespace = ' . (int)$pointNamespace;
			}
		}

		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			[ 'revision_actor_temp', 'revision', 'actor', 'page' ],
			[ 'COUNT(*) AS the_count', 'revactor_actor' ],
			$whereConds,
			__METHOD__,
			// revactor_actor wasn't here originally but PostgreSQL seems to require it
			// Without it, this error happens:
			// Error: 42803 ERROR: column "revision_actor_temp.revactor_actor" must appear in the GROUP BY clause or be used in an aggregate function
			[ 'GROUP BY' => 'actor_name, revactor_actor' ],
			[
				'actor' => [ 'JOIN', 'actor_id = revactor_actor' ],
				'revision_actor_temp' => [ 'JOIN', 'revactor_rev = rev_id' ],
				'page' => [ 'INNER JOIN', 'page_id = revactor_page' ]
			]
		);

		foreach ( $res as $row ) {
			$user = User::newFromActorId( $row->revactor_actor );
			$user->load();
			$actorId = $user->getActorId();
			$userName = $user->getName();

			// Ehh yeah, we don't care about anons here...
			if ( $user->isAnon() ) {
				continue;
			}

			if ( !$user->isBot() ) {
				$editCount = $row->the_count;
			} else {
				$editCount = 0;
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

			$out->addWikiMsg( 'updateeditcounts-updating', $userName, $editCount );

			$dbw->update(
				'user_stats',
				[ 'stats_edit_count = ' . $editCount ],
				[ 'stats_actor' => $actorId ],
				__METHOD__
			);

			global $wgMemc;
			// clear stats cache for current user
			$key = $wgMemc->makeKey( 'user', 'stats', 'actor_id', $actorId );
			$wgMemc->delete( $key );
		}
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();

		// Check permissions -- we must be allowed to access this special page
		// before we can run any database queries
		$this->checkPermissions();

		// And obviously the database needs to be writable before we start
		// running INSERT/UPDATE queries against it...
		$this->checkReadOnly();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		$dbw = wfGetDB( DB_MASTER );
		$this->updateMainEditsCount();

		global $wgUserLevels;
		$wgUserLevels = '';

		$res = $dbw->select(
			[ 'user_stats', 'actor' ],
			[ 'stats_actor', 'actor_name', 'stats_total_points' ],
			[],
			__METHOD__,
			[ 'ORDER BY' => 'actor_name' ],
			[ 'actor' => [ 'JOIN', 'stats_actor = actor_id' ] ]
		);

		$x = 0;
		foreach ( $res as $row ) {
			$x++;
			$stats = new UserStatsTrack( $row->stats_actor );
			$stats->updateTotalPoints();
		}

		$out->addWikiMsg( 'updateeditcounts-updated', $x );
	}
}
