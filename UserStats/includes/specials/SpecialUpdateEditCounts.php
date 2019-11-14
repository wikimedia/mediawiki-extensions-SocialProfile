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
		global $wgActorTableSchemaMigrationStage, $wgNamespacesForEditPoints;

		$out = $this->getOutput();
		$revQuery = MediaWiki\MediaWikiServices::getInstance()->getRevisionStore()->getQueryInfo();
		$pageField = ( $wgActorTableSchemaMigrationStage & SCHEMA_COMPAT_READ_NEW )
			? 'revactor_page' : 'rev_page';
		$userNameField = $revQuery['fields']['rev_user_text'];

		$whereConds = [];
		$whereConds[] = ActorMigration::newMigration()->isNotAnon( $revQuery['fields']['rev_user'] );
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
			array_merge( $revQuery['tables'], [ 'page' ] ),
			array_merge( $revQuery['fields'], [ 'COUNT(*) AS the_count' ] ),
			$whereConds,
			__METHOD__,
			[ 'GROUP BY' => $userNameField ],
			array_merge( $revQuery['joins'], [ 'page' => [ 'INNER JOIN', "page_id = $pageField" ] ] )
		);

		foreach ( $res as $row ) {
			if ( $wgActorTableSchemaMigrationStage & SCHEMA_COMPAT_READ_NEW ) {
				$user = User::newFromActorId( $row->rev_actor );
				$user->loadFromId();
				$uid = $user->getId();
				$userName = $user->getName();
			} else {
				$user = User::newFromId( $row->rev_user );
				$user->loadFromId();
				$uid = $row->rev_user;
				$userName = $row->rev_user_text;
			}

			// Ehh yeah, we don't care about anons here...
			if ( $uid === 0 ) {
				continue;
			}

			if ( !$user->isBot() ) {
				$editCount = $row->the_count;
			} else {
				$editCount = 0;
			}

			$s = $dbw->selectRow(
				'user_stats',
				[ 'stats_user_id' ],
				[ 'stats_user_id' => $uid ],
				__METHOD__
			);
			if ( $s === false || !$s->stats_user_id ) {
				$dbw->insert(
					'user_stats',
					[
						'stats_user_id' => $uid,
						'stats_user_name' => $userName,
						'stats_total_points' => 1000
					],
					__METHOD__
				);
			}

			$out->addWikiMsg( 'updateeditcounts-updating', $userName, $editCount );

			$dbw->update(
				'user_stats',
				[ 'stats_edit_count = ' . $editCount ],
				[ 'stats_user_id' => $uid ],
				__METHOD__
			);

			global $wgMemc;
			// clear stats cache for current user
			$key = $wgMemc->makeKey( 'user', 'stats', $uid );
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
