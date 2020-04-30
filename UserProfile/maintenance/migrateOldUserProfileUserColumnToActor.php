<?php
/**
 * @file
 * @ingroup Maintenance
 */
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Run automatically with update.php
 *
 * @since January 2020
 */
class MigrateOldUserProfileUserColumnToActor extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrates data from old up_user_id column in the user_profile table to the new actor column.' );
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
		return 'user_profile has already been migrated to use the actor column.';
	}

	/**
	 * Do the actual work.
	 *
	 * @return bool True to log the update as done
	 */
	protected function doDBUpdates() {
		$dbw = $this->getDB( DB_MASTER );
		if ( $dbw->fieldExists( 'user_profile', 'up_user_id', __METHOD__ ) ) {
			$dbw->sourceFile( __DIR__ . '/../sql/patches/actor/drop-primary-key.sql' );
			if ( !$dbw->fieldExists( 'user_profile', 'up_id', __METHOD__ ) ) {
				$dbw->sourceFile( __DIR__ . '/../sql/patches/actor/add-up_id.sql' );
			}
			$dbw->query(
				"UPDATE {$dbw->tableName( 'user_profile' )} SET up_actor=(SELECT actor_id FROM {$dbw->tableName( 'actor' )} WHERE actor_user=up_user_id)",
				__METHOD__
			);
		}
		return true;
	}
}

$maintClass = MigrateOldUserProfileUserColumnToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
