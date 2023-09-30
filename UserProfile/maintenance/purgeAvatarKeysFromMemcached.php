<?php
/**
 * Purges cache keys related to SocialProfile's avatars from memcached.
 *
 * @file
 * @ingroup Maintenance
 * @see https://phabricator.wikimedia.org/T161975
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class PurgeAvatarKeysFromMemcached extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Purges cache keys related to SocialProfile's avatars from memcached." );
		$this->addOption( 'all', 'Delete cache data for ALL users who have a custom avatar instead of a specific user?', false, false );
		$this->addOption( 'uid', 'Numeric user ID of the user whose cache data we want to delete; if neither --username nor --all are specified, this should be.', false, true );
		$this->addOption( 'username', 'User name of the user whose cache keys to delete; if neither --uid nor --all are specified, this should be.', false, false );
	}

	public function execute() {
		$services = MediaWikiServices::getInstance();
		$cache = $services->getMainWANObjectCache();
		$userFactory = $services->getUserFactory();

		if ( !$this->hasOption( 'all' ) ) {
			// Figure out the user ID, either by using the supplied ID as-is or
			// then by constructing a User object from the given user name to
			// get the ID
			$uid = null;
			if ( $this->hasOption( 'uid' ) ) {
				$uid = (int)$this->getOption( 'uid' );
			} elseif ( $this->hasOption( 'username' ) ) {
				if ( method_exists( MediaWikiServices::class, 'getUserIdentityLookup' ) ) {
					// MW 1.36+
					$userIdentity = $services->getUserIdentityLookup()
						->getUserIdentityByName( $this->getOption( 'username' ) );
					$uid = $userIdentity ? $userIdentity->getId() : 0;
				} else {
					// @phan-suppress-next-line PhanUndeclaredStaticMethod Removed in MW 1.41+
					$uid = User::idFromName( $this->getOption( 'username' ) );
				}
			}
			if ( !$uid ) {
				$this->fatalError(
					'Must specify either a user ID or a user name when not running ' .
					"this script with the --all option!\n",
					1
				);
			}

			$sizes = [ 's', 'm', 'ml', 'l' ];
			foreach ( $sizes as $size ) {
				$key = $cache->makeKey( 'user', 'profile', 'avatar', $uid, $size );
				$cache->delete( $key );
			}
		} else {
			// Deleting ALL cache keys for ALL users? Oh my...
			// user_stats.stats_user_image_count is technically speaking incremented
			// when the user uploads a new avatar (see
			// /extensions/SocialProfile/UserProfile/SpecialUploadAvatar.php, function performUpload),
			// but in practise this doesn't always appear to be the case, or at
			// least the value of user_stats.stats_user_image_count is 0 for me
			// on Brickipedia despite that I've uploaded a custom avatar in the
			// past, but that might be because Brickipedia is a special case.
			// In any case, I can't think of a better way to get a list of users
			// who *may* have a custom avatar than to pull _all_ the user_stats
			// entries and then see for each entry (user) whether they have an
			// avatar or not. This is still a lot faster than anything else because
			// especially on a wiki farm
			// (total amount of users) != (users who have edited the wiki and thus have a user_stats entry)
			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				'user_stats',
				'*',
				[],
				__METHOD__
			);

			foreach ( $res as $row ) {
				$actorId = $row->stats_actor;
				$user = $userFactory->newFromActorId( $actorId );
				$uid = $user->getId();
				$avatar = new wAvatar( $uid, 's' /* this doesn't even matter but it's a non-optional param */ );

				// User has a custom avatar? Oh goody!
				if ( !$avatar->isDefault() ) {
					$sizes = [ 's', 'm', 'ml', 'l' ];
					foreach ( $sizes as $size ) {
						$key = $cache->makeKey( 'user', 'profile', 'avatar', $uid, $size );
						$cache->delete( $key );
					}
					$this->output( "Deleted cache keys for UID (#{$uid}), username: {$row->stats_user_name}\n" );
				}

				// Trying to keep the memory usage under control
				unset( $avatar );
			}
		}

		$this->output( "All done!\n" );
	}
}

$maintClass = PurgeAvatarKeysFromMemcached::class;
require_once RUN_MAINTENANCE_IF_MAIN;
