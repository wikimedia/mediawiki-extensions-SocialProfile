<?php
/**
 * @file
 * @ingroup Maintenance
 */

use MediaWiki\MediaWikiServices;

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
class MigrateOldSystemGiftsUserColumnsToActor extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrates data from old _user_name/_user_id columns in the user_system_gift table to the new actor column.' );
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
		return 'user_system_gift has already been migrated to use the actor column.';
	}

	/**
	 * Do the actual work.
	 *
	 * @return bool True to log the update as done
	 */
	protected function doDBUpdates() {
		$dbw = $this->getDB( DB_MASTER );
		if ( !$dbw->fieldExists( 'user_system_gift', 'sg_user_id', __METHOD__ ) ) {
			// Old field's been dropped already so nothing to do here...
			// Why is this loop here? Because Postgres was being weird, that's why.
			return true;
		}

		$res = $dbw->select(
			'user_system_gift',
			[
				'sg_user_id'
			],
			'',
			__METHOD__,
			[ 'DISTINCT' ]
		);
		foreach ( $res as $row ) {
			$user = User::newFromId( $row->sg_user_id );
			if ( interface_exists( '\MediaWiki\User\ActorNormalization' ) ) {
				// MW 1.36+
				$actorId = MediaWikiServices::getInstance()->getActorNormalization()->acquireActorId( $user, $dbw );
			} else {
				$actorId = $user->getActorId( $dbw );
			}
			$dbw->update(
				'user_system_gift',
				[
					'sg_actor' => $actorId
				],
				[
					'sg_user_id' => $row->sg_user_id
				]
			);
		}

		return true;
	}
}

$maintClass = MigrateOldSystemGiftsUserColumnsToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
