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
class MigrateOldGiftsUserColumnsToActor extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrates data from old _user_name/_user_id columns in the gift table to the new actor column.' );
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
	public function updateSkippedMessage() {
		return 'gift has already been migrated to use the actor column.';
	}

	/**
	 * Do the actual work.
	 *
	 * @return bool True to log the update as done
	 */
	protected function doDBUpdates() {
		$dbw = $this->getDB( DB_PRIMARY );

		if ( !$dbw->fieldExists( 'gift', 'gift_creator_user_id', __METHOD__ ) ) {
			// Old field's been dropped already so nothing to do here...
			return true;
		}

		$res = $dbw->select(
			'gift',
			[
				'gift_creator_user_id'
			],
			'',
			__METHOD__,
			[ 'DISTINCT' ]
		);
		foreach ( $res as $row ) {
			$user = User::newFromId( $row->gift_creator_user_id );
			$actorId = MediaWikiServices::getInstance()->getActorNormalization()->acquireActorId( $user, $dbw );
			$dbw->update(
				'gift',
				[
					'gift_creator_actor' => $actorId
				],
				[
					'gift_creator_user_id' => $row->gift_creator_user_id
				],
				__METHOD__
			);
		}

		return true;
	}
}

$maintClass = MigrateOldGiftsUserColumnsToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
