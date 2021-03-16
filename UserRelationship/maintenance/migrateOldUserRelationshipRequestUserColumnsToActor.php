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
class MigrateOldUserRelationshipRequestUserColumnsToActor extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrates data from old _user_name/_user_id columns in the user_relationship_request table to the new actor columns.' );
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
		return 'user_relationship_request has already been migrated to use the actor columns.';
	}

	/**
	 * Do the actual work.
	 *
	 * @return bool True to log the update as done
	 */
	protected function doDBUpdates() {
		$dbw = $this->getDB( DB_MASTER );

		if ( $dbw->fieldExists( 'user_relationship_request', 'ur_user_id_from', __METHOD__ ) ) {
			$res = $dbw->select(
				'user_relationship_request',
				[
					'ur_user_id_from'
				],
				'',
				__METHOD__,
				[ 'DISTINCT' ]
			);
			foreach ( $res as $row ) {
				$user = User::newFromId( $row->ur_user_id_from );
				if ( interface_exists( '\MediaWiki\User\ActorNormalization' ) ) {
					// MW 1.36+
					$actorId = MediaWikiServices::getInstance()->getActorNormalization()->acquireActorId( $user, $dbw );
				} else {
					$actorId = $user->getActorId( $dbw );
				}
				$dbw->update(
					'user_relationship_request',
					[
						'ur_actor_from' => $actorId
					],
					[
						'ur_user_id_from' => $row->ur_user_id_from
					]
				);
			}
		}

		if ( $dbw->fieldExists( 'user_relationship_request', 'ur_user_id_to', __METHOD__ ) ) {
			$res = $dbw->select(
				'user_relationship_request',
				[
					'ur_user_id_to'
				],
				'',
				__METHOD__,
				[ 'DISTINCT' ]
			);
			foreach ( $res as $row ) {
				$user = User::newFromId( $row->ur_user_id_to );
				if ( interface_exists( '\MediaWiki\User\ActorNormalization' ) ) {
					// MW 1.36+
					$actorId = MediaWikiServices::getInstance()->getActorNormalization()->acquireActorId( $user, $dbw );
				} else {
					$actorId = $user->getActorId( $dbw );
				}
				$dbw->update(
					'user_relationship_request',
					[
						'ur_actor_to' => $actorId
					],
					[
						'ur_user_id_to' => $row->ur_user_id_to
					]
				);
			}
		}

		return true;
	}
}

$maintClass = MigrateOldUserRelationshipRequestUserColumnsToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
