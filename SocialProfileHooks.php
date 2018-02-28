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
	public static function onBeforePageDisplay( OutputPage &$out, &$skin ) {
		$out->addModuleStyles( 'ext.socialprofile.responsive' );
	}

	/**
	 * Register the canonical names for our custom namespaces and their talkspaces.
	 *
	 * @param array $list Array of namespace numbers
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

		if ( $updater->getDB()->getType() == 'postgres' ) {
			$dbExt = '.postgres';
		}

		$updater->addExtensionTable( 'user_board', "$dir/UserBoard/sql/user_board$dbExt.sql" );
		$updater->addExtensionTable( 'user_fields_privacy', "$dir/UserProfile/sql/user_fields_privacy$dbExt.sql" );
		$updater->addExtensionTable( 'user_profile', "$dir/UserProfile/sql/user_profile$dbExt.sql" );
		$updater->addExtensionTable( 'user_stats', "$dir/UserStats/sql/user_stats$dbExt.sql" );
		$updater->addExtensionTable( 'user_relationship', "$dir/UserRelationship/sql/user_relationship$dbExt.sql" );
		$updater->addExtensionTable( 'user_relationship_request', "$dir/UserRelationship/sql/user_relationship$dbExt.sql" );
		$updater->addExtensionTable( 'user_system_gift', "$dir/SystemGifts/sql/systemgifts$dbExt.sql" );
		$updater->addExtensionTable( 'system_gift', "$dir/SystemGifts/sql/systemgifts$dbExt.sql" );
		$updater->addExtensionTable( 'user_gift', "$dir/UserGifts/sql/usergifts$dbExt.sql" );
		$updater->addExtensionTable( 'gift', "$dir/UserGifts/sql/usergifts$dbExt.sql" );
		$updater->addExtensionTable( 'user_system_messages', "$dir/UserStats/sql/user_system_messages$dbExt.sql" );
		$updater->addExtensionTable( 'user_points_weekly', "$dir/UserStats/sql/user_points_weekly$dbExt.sql" );
		$updater->addExtensionTable( 'user_points_monthly', "$dir/UserStats/sql/user_points_monthly$dbExt.sql" );
		$updater->addExtensionTable( 'user_points_archive', "$dir/UserStats/sql/user_points_archive$dbExt.sql" );
		$updater->dropExtensionField( 'user_stats', 'stats_year_id', "$dir/UserStats/sql/patch-drop-column-stats_year_id.sql" );
	}

	/**
	 * For integration with the Renameuser extension.
	 *
	 * @param int $uid User ID
	 * @param string $oldName Old user name
	 * @param string $newName New user name
	 */
	public static function onRenameUserComplete( $uid, $oldName, $newName ) {
		$dbw = wfGetDB( DB_MASTER );

		$tables = array(
			'user_system_gift' => array( 'sg_user_name', 'sg_user_id' ),
			'user_board' => array( 'ub_user_name_from', 'ub_user_id_from' ),
			'user_gift' => array( 'ug_user_name_to', 'ug_user_id_to' ),
			'gift' => array( 'gift_creator_user_name', 'gift_creator_user_id' ),
			'user_relationship' => array( 'r_user_name_relation', 'r_user_id_relation' ),
			'user_relationship' => array( 'r_user_name', 'r_user_id' ),
			'user_relationship_request' => array( 'ur_user_name_from', 'ur_user_id_from' ),
			'user_stats' => array( 'stats_user_name', 'stats_user_id' ),
			'user_system_messages' => array( 'um_user_name', 'um_user_id' ),
		);

		foreach ( $tables as $table => $data ) {
			$dbw->update(
				$table,
				array( $data[0] => $newName ),
				array( $data[1] => $uid ),
				__METHOD__
			);
		}
	}
}