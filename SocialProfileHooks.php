<?php
/**
 * Hooked functions used by SocialProfile.
 *
 * All class methods are public and static.
 *
 * @file
 */
class SocialProfileHooks {

	/**
	 * Load some responsive CSS on all pages.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, $skin ) {
		$out->addModuleStyles( 'ext.socialprofile.responsive' );
	}

	/**
	 * Register the canonical names for our custom namespaces and their talkspaces.
	 *
	 * @param string[] &$list Array of namespace numbers
	 * with corresponding canonical names
	 */
	public static function onCanonicalNamespaces( &$list ) {
		$list[NS_USER_WIKI] = 'UserWiki';
		$list[NS_USER_WIKI_TALK] = 'UserWiki_talk';
		$list[NS_USER_PROFILE] = 'User_profile';
		$list[NS_USER_PROFILE_TALK] = 'User_profile_talk';
	}

	/**
	 * Creates SocialProfile's new database tables when the user runs
	 * /maintenance/update.php, the MediaWiki core updater script.
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = __DIR__;
		$dbExt = '';
		$db = $updater->getDB();

		if ( $db->getType() == 'postgres' ) {
			$dbExt = '.postgres';
		}

		$updater->addExtensionTable( 'user_board', "$dir/UserBoard/sql/user_board$dbExt.sql" );
		$updater->addExtensionTable( 'user_fields_privacy', "$dir/UserProfile/sql/user_fields_privacy$dbExt.sql" );
		$updater->addExtensionTable( 'user_profile', "$dir/UserProfile/sql/user_profile$dbExt.sql" );
		$updater->addExtensionTable( 'user_stats', "$dir/UserStats/sql/user_stats$dbExt.sql" );
		$updater->addExtensionTable( 'user_relationship', "$dir/UserRelationship/sql/user_relationship$dbExt.sql" );
		$updater->addExtensionTable( 'user_relationship_request', "$dir/UserRelationship/sql/user_relationship_request$dbExt.sql" );
		$updater->addExtensionTable( 'user_system_gift', "$dir/SystemGifts/sql/user_system_gift$dbExt.sql" );
		$updater->addExtensionTable( 'system_gift', "$dir/SystemGifts/sql/system_gift$dbExt.sql" );
		$updater->addExtensionTable( 'user_gift', "$dir/UserGifts/sql/user_gift$dbExt.sql" );
		$updater->addExtensionTable( 'gift', "$dir/UserGifts/sql/gift$dbExt.sql" );
		$updater->addExtensionTable( 'user_system_messages', "$dir/UserStats/sql/user_system_messages$dbExt.sql" );
		$updater->addExtensionTable( 'user_points_weekly', "$dir/UserStats/sql/user_points_weekly$dbExt.sql" );
		$updater->addExtensionTable( 'user_points_monthly', "$dir/UserStats/sql/user_points_monthly$dbExt.sql" );
		$updater->addExtensionTable( 'user_points_archive', "$dir/UserStats/sql/user_points_archive$dbExt.sql" );

		$updater->dropExtensionField( 'user_stats', 'stats_year_id', "$dir/UserStats/sql/patches/patch-drop-column-stats_year_id.sql" );
		$updater->dropExtensionField( 'user_profile', 'up_last_seen', "$dir/UserProfile/sql/patches/patch-drop-column-up_last_seen.sql" );

		// Actor support

		# SystemGifts
		if ( !$db->fieldExists( 'user_system_gift', 'sg_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_system_gift', 'sg_actor', "$dir/SystemGifts/sql/patches/actor/add-sg_actor$dbExt.sql" );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'user_system_gift', 'sg_actor', "$dir/SystemGifts/sql/patches/actor/add-sg_actor_index.sql" );
			// 3) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldSystemGiftsUserColumnsToActor',
				"$dir/SystemGifts/maintenance/migrateOldSystemGiftsUserColumnsToActor.php"
			] );
			// 4) drop old columns & indexes
			$updater->dropExtensionField( 'user_system_gift', 'sg_user_name', "$dir/SystemGifts/sql/patches/actor/drop-sg_user_name.sql" );
			$updater->dropExtensionField( 'user_system_gift', 'sg_user_id', "$dir/SystemGifts/sql/patches/actor/drop-sg_user_id.sql" );
			$updater->dropExtensionIndex( 'user_system_gift', 'sg_user_id', "$dir/SystemGifts/sql/patches/actor/drop-sg_user_id_index.sql" );
		}

		# UserBoard
		if ( !$db->fieldExists( 'user_board', 'ub_actor', __METHOD__ ) ) {
			// 1) add new actor columns
			$updater->addExtensionField( 'user_board', 'ub_actor_from', "$dir/UserBoard/sql/patches/actor/add-ub_actor_from$dbExt.sql" );
			$updater->addExtensionField( 'user_board', 'ub_actor', "$dir/UserBoard/sql/patches/actor/add-ub_actor$dbExt.sql" );
			// 2) add the corresponding indexes
			$updater->addExtensionIndex( 'user_board', 'ub_actor_from', "$dir/UserBoard/sql/patches/actor/add-ub_actor_from_index.sql" );
			$updater->addExtensionIndex( 'user_board', 'ub_actor', "$dir/UserBoard/sql/patches/actor/add-ub_actor_index.sql" );
			// 3) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserBoardUserColumnsToActor',
				"$dir/UserBoard/maintenance/migrateOldUserBoardUserColumnsToActor.php"
			] );
			// 4) drop old columns & indexes
			$updater->dropExtensionField( 'user_board', 'ub_user_id', "$dir/UserBoard/sql/patches/actor/drop-ub_user_id.sql" );
			$updater->dropExtensionField( 'user_board', 'ub_user_name', "$dir/UserBoard/sql/patches/actor/drop-ub_user_name.sql" );
			$updater->dropExtensionField( 'user_board', 'ub_user_id_from', "$dir/UserBoard/sql/patches/actor/drop-ub_user_id_from.sql" );
			$updater->dropExtensionField( 'user_board', 'ub_user_name_from', "$dir/UserBoard/sql/patches/actor/drop-ub_user_name_from.sql" );
			$updater->dropExtensionIndex( 'user_board', 'ub_user_id', "$dir/UserBoard/sql/patches/actor/drop-ub_user_id_index.sql" );
			$updater->dropExtensionIndex( 'user_board', 'ub_user_id', "$dir/UserBoard/sql/patches/actor/drop-ub_user_id_from_index.sql" );
		}

		# UserGifts -- both tables, gift and user_gift, are affected
		if ( !$db->fieldExists( 'gift', 'gift_creator_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'gift', 'gift_creator_actor', "$dir/UserGifts/sql/patches/actor/add-gift_creator_actor$dbExt.sql" );
			// 2) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldGiftsUserColumnsToActor',
				"$dir/UserGifts/maintenance/migrateOldGiftsUserColumnsToActor.php"
			] );
			// 3) drop old columns
			$updater->dropExtensionField( 'gift', 'gift_creator_user_id', "$dir/UserGifts/sql/patches/actor/drop-gift_creator_user_id.sql" );
			$updater->dropExtensionField( 'gift', 'gift_creator_user_name', "$dir/UserGifts/sql/patches/actor/drop-gift_creator_user_name.sql" );
		}

		if ( !$db->fieldExists( 'user_gift', 'ug_actor_to', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_gift', 'ug_actor_to', "$dir/UserGifts/sql/patches/actor/add-ug_actor_to$dbExt.sql" );
			$updater->addExtensionField( 'user_gift', 'ug_actor_from', "$dir/UserGifts/sql/patches/actor/add-ug_actor_from$dbExt.sql" );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'user_gift', 'ug_actor_from', "$dir/UserGifts/sql/patches/actor/add-ug_actor_from_index.sql" );
			$updater->addExtensionIndex( 'user_gift', 'ug_actor_to', "$dir/UserGifts/sql/patches/actor/add-ug_actor_to_index.sql" );
			// 3) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserGiftsUserColumnsToActor',
				"$dir/UserGifts/maintenance/migrateOldUserGiftsUserColumnsToActor.php"
			] );
			// 4) drop old columns & indexes
			$updater->dropExtensionField( 'user_gift', 'ug_user_id_to', "$dir/UserGifts/sql/patches/actor/drop-ug_user_id_to.sql" );
			$updater->dropExtensionField( 'user_gift', 'ug_user_name_to', "$dir/UserGifts/sql/patches/actor/drop-ug_user_name_to.sql" );
			$updater->dropExtensionField( 'user_gift', 'ug_user_id_from', "$dir/UserGifts/sql/patches/actor/drop-ug_user_id_from.sql" );
			$updater->dropExtensionField( 'user_gift', 'ug_user_name_from', "$dir/UserGifts/sql/patches/actor/drop-ug_user_name_from.sql" );
			$updater->dropExtensionIndex( 'user_gift', 'ug_user_id_from', "$dir/UserGifts/sql/patches/actor/drop-ug_user_id_from_index.sql" );
			$updater->dropExtensionIndex( 'user_gift', 'ug_user_id_to', "$dir/UserGifts/sql/patches/actor/drop-ug_user_id_to_index.sql" );
		}

		# UserProfile -- two affected tables, user_profile and user_fields_privacy
		if ( !$db->fieldExists( 'user_profile', 'up_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_profile', 'up_actor', "$dir/UserProfile/sql/patches/actor/add-up_actor$dbExt.sql" );
			// 2) populate the new column with data and make some other magic happen, too,
			// like the PRIMARY KEY switchover
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserProfileUserColumnToActor',
				"$dir/UserProfile/maintenance/migrateOldUserProfileUserColumnToActor.php"
			] );
			// 3) drop the old user ID column
			$updater->dropExtensionField( 'user_profile', 'up_user_id', "$dir/UserProfile/sql/patches/actor/drop-up_user_id.sql" );
		}

		// This was a bad idea and I should feel bad. Luckily it existed only for
		// like less than half a year in 2020.
		if ( $db->fieldExists( 'user_profile', 'up_id', __METHOD__ ) ) {
			$updater->dropExtensionField( 'user_profile', 'up_id', "$dir/UserProfile/sql/patches/patch-drop-column-up_id.sql" );
		}

		if ( !$db->fieldExists( 'user_fields_privacy', 'ufp_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_fields_privacy', 'ufp_actor', "$dir/UserProfile/sql/patches/actor/add-ufp_actor$dbExt.sql" );
			// 2) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserFieldPrivacyUserColumnToActor',
				"$dir/UserProfile/maintenance/migrateOldUserFieldPrivacyUserColumnToActor.php"
			] );
			// 3) drop old column
			$updater->dropExtensionField( 'user_profile', 'ufp_user_id', "$dir/UserProfile/sql/patches/actor/drop-ufp_user_id.sql" );
		}

		# UserRelationship -- two affected tables, user_relationship & user_relationship_request
		if ( !$db->fieldExists( 'user_relationship', 'r_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_relationship', 'r_actor', "$dir/UserRelationship/sql/patches/actor/add-r_actor$dbExt.sql" );
			$updater->addExtensionField( 'user_relationship', 'r_actor_relation', "$dir/UserRelationship/sql/patches/actor/add-r_actor_relation$dbExt.sql" );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'user_relationship', 'r_actor', "$dir/UserRelationship/sql/patches/actor/add-r_actor_index.sql" );
			$updater->addExtensionIndex( 'user_relationship', 'r_actor_relation', "$dir/UserRelationship/sql/patches/actor/add-r_actor_relation_index.sql" );
			// 3) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserRelationshipUserColumnsToActor',
				"$dir/UserRelationship/maintenance/migrateOldUserRelationshipUserColumnsToActor.php"
			] );
			// 4) drop old columns & indexes
			$updater->dropExtensionField( 'user_relationship', 'r_user_id', "$dir/UserRelationship/sql/patches/actor/drop-r_user_id.sql" );
			$updater->dropExtensionField( 'user_relationship', 'r_user_name', "$dir/UserRelationship/sql/patches/actor/drop-r_user_name.sql" );
			$updater->dropExtensionField( 'user_relationship', 'r_user_id_relation', "$dir/UserRelationship/sql/patches/actor/drop-r_user_id_relation.sql" );
			$updater->dropExtensionField( 'user_relationship', 'r_user_name_relation', "$dir/UserRelationship/sql/patches/actor/drop-r_user_name_relation.sql" );
			$updater->dropExtensionIndex( 'user_relationship', 'r_user_id', "$dir/UserRelationship/sql/patches/actor/drop-r_user_id_index.sql" );
			$updater->dropExtensionIndex( 'user_relationship', 'r_user_id_relation', "$dir/UserRelationship/sql/patches/actor/drop-r_user_id_relation_index.sql" );
		}

		if ( !$db->fieldExists( 'user_relationship_request', 'ur_actor_from', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_relationship_request', 'ur_actor_from', "$dir/UserRelationship/sql/patches/actor/add-ur_actor_from$dbExt.sql" );
			$updater->addExtensionField( 'user_relationship_request', 'ur_actor_to', "$dir/UserRelationship/sql/patches/actor/add-ur_actor_to$dbExt.sql" );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'user_relationship_request', 'ur_actor_from', "$dir/UserRelationship/sql/patches/actor/add-ur_actor_from_index.sql" );
			$updater->addExtensionIndex( 'user_relationship_request', 'ur_actor_to', "$dir/UserRelationship/sql/patches/actor/add-ur_actor_to_index.sql" );
			// 3) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserRelationshipRequestUserColumnsToActor',
				"$dir/UserRelationship/maintenance/migrateOldUserRelationshipRequestUserColumnsToActor.php"
			] );
			// 4) drop old columns & indexes
			$updater->dropExtensionField( 'user_relationship_request', 'ur_user_id_from', "$dir/UserRelationship/sql/patches/actor/drop-ur_user_id_from.sql" );
			$updater->dropExtensionField( 'user_relationship_request', 'ur_user_name_from', "$dir/UserRelationship/sql/patches/actor/drop-ur_user_name_from.sql" );
			$updater->dropExtensionField( 'user_relationship_request', 'ur_user_id_to', "$dir/UserRelationship/sql/patches/actor/drop-ur_user_id_to.sql" );
			$updater->dropExtensionField( 'user_relationship_request', 'ur_user_name_to', "$dir/UserRelationship/sql/patches/actor/drop-ur_user_name_to.sql" );
			$updater->dropExtensionIndex( 'user_relationship_request', 'ur_user_id_from', "$dir/UserRelationship/sql/patches/actor/drop-ur_user_id_from_index.sql" );
			$updater->dropExtensionIndex( 'user_relationship_request', 'ur_user_id_to', "$dir/UserRelationship/sql/patches/actor/drop-ur_user_id_to_index.sql" );
		}

		# UserStats -- 5 tables: user_points_{archive,monthly,weekly}, user_stats, user_system_messages
		if ( !$db->fieldExists( 'user_stats', 'stats_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_stats', 'stats_actor', "$dir/UserStats/sql/patches/actor/add-stats_actor.sql" );
			// the stats_id column adding is done by the script, it can't be done here
			// due to the need to switch over PKs...annoying.
			// 2) populate the new column with data and do other magic like the PRIMARY KEY switchover
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserStatsUserColumnsToActor',
				"$dir/UserStats/maintenance/migrateOldUserStatsUserColumnsToActor.php"
			] );
			// 3) drop old columns
			$updater->dropExtensionField( 'user_stats', 'stats_user_name', "$dir/UserStats/sql/patches/actor/drop-stats_user_name.sql" );
			$updater->dropExtensionField( 'user_stats', 'stats_user_id', "$dir/UserStats/sql/patches/actor/drop-stats_user_id.sql" );
		}

		if ( !$db->fieldExists( 'user_system_messages', 'um_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_system_messages', 'um_actor', "$dir/UserStats/sql/patches/actor/add-um_actor$dbExt.sql" );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'user_system_messages', 'um_actor', "$dir/UserStats/sql/patches/actor/add-um_actor_index.sql" );
			// 3) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserSystemMessagesUserColumnToActor',
				"$dir/UserStats/maintenance/migrateOldUserSystemMessagesUserColumnToActor.php"
			] );
			// 4) drop old columns & indexes
			$updater->dropExtensionField( 'user_system_messages', 'um_user_name', "$dir/UserStats/sql/patches/actor/drop-um_user_name.sql" );
			$updater->dropExtensionField( 'user_system_messages', 'um_user_id', "$dir/UserStats/sql/patches/actor/drop-um_user_id.sql" );
			// [sic]!
			$updater->dropExtensionIndex( 'user_system_messages', 'up_user_id', "$dir/UserStats/sql/patches/actor/drop-index-up_user_id-on-user_system_messages.sql" );
		}

		if ( !$db->fieldExists( 'user_points_archive', 'up_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_points_archive', 'up_actor', "$dir/UserStats/sql/patches/actor/add-up_actor-on-user_points_archive$dbExt.sql" );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'user_points_archive', 'upa_actor', "$dir/UserStats/sql/patches/actor/add-upa_actor_index-on-user_points_archive.sql" );
			// 3) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserPointsArchiveUserColumnToActor',
				"$dir/UserStats/maintenance/migrateOldUserPointsArchiveUserColumnToActor.php"
			] );
			// 4) drop old columns & indexes
			$updater->dropExtensionField( 'user_points_archive', 'up_user_name', "$dir/UserStats/sql/patches/actor/drop-up_user_name-on-user_points_archive.sql" );
			$updater->dropExtensionField( 'user_points_archive', 'up_user_id', "$dir/UserStats/sql/patches/actor/drop-up_user_id-on-user_points_archive.sql" );
			$updater->dropExtensionIndex( 'user_points_archive', 'upa_up_user_id', "$dir/UserStats/sql/patches/actor/drop-index-upa_up_user_id-on-user_points_archive.sql" );
		}

		if ( !$db->fieldExists( 'user_points_monthly', 'up_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_points_monthly', 'up_actor', "$dir/UserStats/sql/patches/actor/add-up_actor-on-user_points_monthly$dbExt.sql" );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'user_points_monthly', 'upm_actor', "$dir/UserStats/sql/patches/actor/add-upm_actor_index-on-user_points_monthly.sql" );
			// 3) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserPointsMonthlyUserColumnToActor',
				"$dir/UserStats/maintenance/migrateOldUserPointsMonthlyUserColumnToActor.php"
			] );
			// 4) drop old columns & indexes
			$updater->dropExtensionField( 'user_points_monthly', 'up_user_name', "$dir/UserStats/sql/patches/actor/drop-up_user_name-on-user_points_monthly.sql" );
			$updater->dropExtensionField( 'user_points_monthly', 'up_user_id', "$dir/UserStats/sql/patches/actor/drop-up_user_id-on-user_points_monthly.sql" );
			$updater->dropExtensionIndex( 'user_points_monthly', 'upm_up_user_id', "$dir/UserStats/sql/patches/actor/drop-index-upm_up_user_id-on-user_points_monthly.sql" );
		}

		if ( !$db->fieldExists( 'user_points_weekly', 'up_actor', __METHOD__ ) ) {
			// 1) add new actor column
			$updater->addExtensionField( 'user_points_weekly', 'up_actor', "$dir/UserStats/sql/patches/actor/add-up_actor-on-user_points_weekly$dbExt.sql" );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'user_points_weekly', 'upw_actor', "$dir/UserStats/sql/patches/actor/add-upw_actor_index-on-user_points_weekly.sql" );
			// 3) populate the new column with data
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldUserPointsWeeklyUserColumnToActor',
				"$dir/UserStats/maintenance/migrateOldUserPointsWeeklyUserColumnToActor.php"
			] );
			// 4) drop old columns & indexes
			$updater->dropExtensionField( 'user_points_weekly', 'up_user_name', "$dir/UserStats/sql/patches/actor/drop-up_user_name-on-user_points_weekly.sql" );
			$updater->dropExtensionField( 'user_points_weekly', 'up_user_id', "$dir/UserStats/sql/patches/actor/drop-up_user_id-on-user_points_weekly.sql" );
			$updater->dropExtensionIndex( 'user_points_weekly', 'upw_up_user_id', "$dir/UserStats/sql/patches/actor/drop-index-upw_up_user_id-on-user_points_weekly.sql" );
		}
	}

}
