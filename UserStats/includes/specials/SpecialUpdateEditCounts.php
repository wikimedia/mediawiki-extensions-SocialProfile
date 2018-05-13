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

		$whereConds = [];
		$whereConds[] = 'rev_user <> 0';
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
			[ 'revision', 'page' ],
			[ 'rev_user_text', 'rev_user', 'COUNT(*) AS the_count' ],
			$whereConds,
			__METHOD__,
			[ 'GROUP BY' => 'rev_user_text' ],
			[ 'page' => [ 'INNER JOIN', 'page_id = rev_page' ] ]
		);

		foreach ( $res as $row ) {
			$user = User::newFromId( $row->rev_user );
			$user->loadFromId();

			if ( !$user->isBot() ) {
				$editCount = $row->the_count;
			} else {
				$editCount = 0;
			}

			$s = $dbw->selectRow(
				'user_stats',
				[ 'stats_user_id' ],
				[ 'stats_user_id' => $row->rev_user ],
				__METHOD__
			);
			if ( !$s->stats_user_id || $s === false ) {
				$dbw->insert(
					'user_stats',
					[
						'stats_user_id' => $row->rev_user,
						'stats_user_name' => $row->rev_user_text,
						'stats_total_points' => 1000
					],
					__METHOD__
				);
			}
			$out->addWikiMsg(
				'updateeditcounts-updating',
				$row->rev_user_text,
				$editCount
			);

			$dbw->update(
				'user_stats',
				[ 'stats_edit_count = ' . $editCount ],
				[ 'stats_user_id' => $row->rev_user ],
				__METHOD__
			);

			global $wgMemc;
			// clear stats cache for current user
			$key = $wgMemc->makeKey( 'user', 'stats', $row->rev_user );
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
			'user_stats',
			[ 'stats_user_id', 'stats_user_name', 'stats_total_points' ],
			[],
			__METHOD__,
			[ 'ORDER BY' => 'stats_user_name' ]
		);

		$x = 0;
		foreach ( $res as $row ) {
			$x++;
			$stats = new UserStatsTrack(
				$row->stats_user_id,
				$row->stats_user_name
			);
			$stats->updateTotalPoints();
		}

		$out->addWikiMsg( 'updateeditcounts-updated', $x );
	}
}
