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
class MigrateOldUserSystemMessagesUserColumnToActor extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrates data from old _user_name/_user_id columns in the user_system_messages table to the new actor column.' );
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
		return 'user_system_messages has already been migrated to use the actor column.';
	}

	/**
	 * Do the actual work.
	 *
	 * @return bool True to log the update as done
	 */
	protected function doDBUpdates() {
		$dbw = $this->getDB( DB_MASTER );

		if ( !$dbw->fieldExists( 'user_system_messages', 'um_user_id', __METHOD__ ) ) {
			return true;
		}

		$res = $dbw->select(
			'user_system_messages',
			[
				'um_user_id'
			],
			'',
			__METHOD__,
			[ 'DISTINCT' ]
		);
		foreach ( $res as $row ) {
			$user = User::newFromId( $row->um_user_id );
			if ( interface_exists( '\MediaWiki\User\ActorNormalization' ) ) {
				// MW 1.36+
				$actorId = MediaWikiServices::getInstance()->getActorNormalization()->acquireActorId( $user, $dbw );
			} else {
				$actorId = $user->getActorId( $dbw );
			}
			$dbw->update(
				'user_system_messages',
				[
					'um_actor' => $actorId
				],
				[
					'um_user_id' => $row->um_user_id
				]
			);
		}

		return true;
	}
}

$maintClass = MigrateOldUserSystemMessagesUserColumnToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
