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
			// Drop the _old_ PRIMARY KEY
			$dbw->sourceFile( __DIR__ . '/../sql/patches/actor/drop-primary-key.sql' );

			$res = $dbw->select(
				'user_profile',
				[
					'up_user_id'
				],
				'',
				__METHOD__,
				[ 'DISTINCT' ]
			);
			foreach ( $res as $row ) {
				$user = User::newFromId( $row->up_user_id );
				if ( interface_exists( '\MediaWiki\User\ActorNormalization' ) ) {
					// MW 1.36+
					$actorId = MediaWikiServices::getInstance()->getActorNormalization()->acquireActorId( $user, $dbw );
				} else {
					$actorId = $user->getActorId( $dbw );
				}
				// Populate our brand new column
				$dbw->update(
					'user_profile',
					[
						'up_actor' => $actorId
					],
					[
						'up_user_id' => $row->up_user_id
					],
					__METHOD__
				);
			}
			// Make our new column the new PK!
			$dbw->sourceFile( __DIR__ . '/../sql/patches/actor/make-up_actor-primary-key.sql' );
		}
		return true;
	}
}

$maintClass = MigrateOldUserProfileUserColumnToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
