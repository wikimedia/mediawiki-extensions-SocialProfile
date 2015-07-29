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
	 * @return bool
	 */
	public static function onBeforePageDisplay( OutputPage &$out, &$skin ) {
		$out->addModuleStyles( 'ext.socialprofile.responsive' );
		return true;
	}

	/**
	 * Register the canonical names for our custom namespaces and their talkspaces.
	 *
	 * @param $list Array: array of namespace numbers with corresponding
	 *                     canonical names
	 * @return Boolean: true
	 */
	public static function onCanonicalNamespaces( &$list ) {
		$list[NS_USER_WIKI] = 'UserWiki';
		$list[NS_USER_WIKI_TALK] = 'UserWiki_talk';
		$list[NS_USER_PROFILE] = 'User_profile';
		$list[NS_USER_PROFILE_TALK] = 'User_profile_talk';

		return true;
	}

	/**
	 * Creates SocialProfile's new database tables when the user runs
	 * /maintenance/update.php, the MediaWiki core updater script.
	 *
	 * @param $updater DatabaseUpdater
	 * @return Boolean
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = dirname( __FILE__ );
		$dbExt = '';

		if ( $updater->getDB()->getType() == 'postgres' ) {
			$dbExt = '.postgres';
		}

		$updater->addExtensionUpdate( array( 'addTable', 'user_board', "$dir/UserBoard/user_board$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_profile', "$dir/UserProfile/user_profile$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_stats', "$dir/UserStats/user_stats$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_relationship', "$dir/UserRelationship/user_relationship$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_relationship_request', "$dir/UserRelationship/user_relationship$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_system_gift', "$dir/SystemGifts/systemgifts$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'system_gift', "$dir/SystemGifts/systemgifts$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_gift', "$dir/UserGifts/usergifts$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'gift', "$dir/UserGifts/usergifts$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_system_messages', "$dir/UserSystemMessages/user_system_messages$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_points_weekly', "$dir/UserStats/user_points_weekly$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_points_monthly', "$dir/UserStats/user_points_monthly$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_points_archive', "$dir/UserStats/user_points_archive$dbExt.sql", true ) );

		return true;
	}

	/**
	 * For integration with the Renameuser extension.
	 *
	 * @param int $uid User ID
	 * @param String $oldName old user name
	 * @param String $newName new user name
	 * @return Boolean
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

		return true;
	}
}