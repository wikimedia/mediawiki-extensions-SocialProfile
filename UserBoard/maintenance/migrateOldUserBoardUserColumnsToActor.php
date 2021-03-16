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
class MigrateOldUserBoardUserColumnsToActor extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrates data from old _user_name/_user_id columns in the user_board table to the new actor columns.' );
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
		return 'user_board has already been migrated to use the actor columns.';
	}

	/**
	 * Do the actual work.
	 *
	 * @return bool True to log the update as done
	 */
	protected function doDBUpdates() {
		$dbw = $this->getDB( DB_MASTER );

		if ( !$dbw->fieldExists( 'user_board', 'ub_user_id', __METHOD__ ) ) {
			return true;
		}

		$res = $dbw->select(
			'user_board',
			[
				'ub_user_id'
			],
			'',
			__METHOD__,
			[ 'DISTINCT' ]
		);
		foreach ( $res as $row ) {
			$user = User::newFromId( $row->ub_user_id );
			if ( interface_exists( '\MediaWiki\User\ActorNormalization' ) ) {
				// MW 1.36+
				$actorId = MediaWikiServices::getInstance()->getActorNormalization()->acquireActorId( $user, $dbw );
			} else {
				$actorId = $user->getActorId( $dbw );
			}
			$dbw->update(
				'user_board',
				[
					'ub_actor' => $actorId

				],
				[
					'ub_user_id' => $row->ub_user_id
				]
			);
		}

		$res = $dbw->select(
			'user_board',
			[
				'ub_user_id_from'
			],
			'',
			__METHOD__,
			[ 'DISTINCT' ]
		);
		foreach ( $res as $row ) {
			$user = User::newFromId( $row->ub_user_id_from );
			if ( interface_exists( '\MediaWiki\User\ActorNormalization' ) ) {
				// MW 1.36+
				$actorId = MediaWikiServices::getInstance()->getActorNormalization()->acquireActorId( $user, $dbw );
			} else {
				$actorId = $user->getActorId( $dbw );
			}
			$dbw->update(
				'user_board',
				[
					'ub_actor_from' => $actorId
				],
				[
					'ub_user_id_from' => $row->ub_user_id_from
				]
			);
		}

		return true;
	}
}

$maintClass = MigrateOldUserBoardUserColumnsToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
