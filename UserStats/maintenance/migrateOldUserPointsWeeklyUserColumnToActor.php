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
class MigrateOldUserPointsWeeklyUserColumnToActor extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrates data from old _user_name/_user_id columns in the user_points_weekly table to the new actor column.' );
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
		return 'user_points_weekly has already been migrated to use the actor column.';
	}

	/**
	 * Do the actual work.
	 *
	 * @return bool True to log the update as done
	 */
	protected function doDBUpdates() {
		$dbw = $this->getDB( DB_MASTER );
		if ( !$dbw->fieldExists( 'user_points_weekly', 'up_user_id', __METHOD__ ) ) {
			// Old field's been dropped already so nothing to do here...
			// Why is this loop here? Because Postgres was being weird, that's why.
			return true;
		}
		$dbw->query(
			"UPDATE {$dbw->tableName( 'user_points_weekly' )} SET up_actor=(SELECT actor_id FROM {$dbw->tableName( 'actor' )} WHERE actor_user=up_user_id AND actor_name=up_user_name)",
			__METHOD__
		);
		return true;
	}
}

$maintClass = MigrateOldUserPointsWeeklyUserColumnToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
