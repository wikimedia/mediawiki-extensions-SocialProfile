<?php
/**
 * @file
 * @ingroup Maintenance
 */
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Run automatically with update.php
 *
 * @since January 2020
 */
class MigrateOldUserStatsUserColumnsToActor extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrates data from old _user_name/_user_id columns in the user_stats table to the new actor column.' );
	}

	/**
	 * Get the update key name to go in the update log table
	 *
	 * @return string
	 */
	protected function getUpdateKey() {
		return __CLASS__;
	}

	/**
	 * Message to show that the update was done already and was just skipped
	 *
	 * @return string
	 */
	protected function updateSkippedMessage() {
		return 'user_stats has already been migrated to use the actor column.';
	}

	/**
	 * Do the actual work.
	 *
	 * @return bool True to log the update as done
	 */
	protected function doDBUpdates() {
		$dbw = $this->getDB( DB_MASTER );

		if ( !$dbw->fieldExists( 'user_stats', 'stats_id', __METHOD__ ) ) {
			$dbw->sourceFile( __DIR__ . '/../sql/patches/actor/drop-primary-key.sql' );
			$dbw->sourceFile( __DIR__ . '/../sql/patches/actor/add-stats_id.sql' );
		}

		if ( $dbw->fieldExists( 'user_stats', 'stats_user_id', __METHOD__ ) ) {
			$dbw->query(
				"UPDATE {$dbw->tableName( 'user_stats' )} SET stats_actor=(SELECT actor_id FROM {$dbw->tableName( 'actor' )} WHERE actor_user=stats_user_id AND actor_name=stats_user_name)",
				__METHOD__
			);
		}

		return true;
	}
}

$maintClass = MigrateOldUserStatsUserColumnsToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
