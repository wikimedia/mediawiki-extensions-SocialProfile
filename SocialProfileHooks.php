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
		$updater->addExtensionUpdate( array( 'addTable', 'user_relationship',	"$dir/UserRelationship/user_relationship$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_relationship_request', "$dir/UserRelationship/user_relationship$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_system_gift', "$dir/SystemGifts/systemgifts$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'system_gift', "$dir/SystemGifts/systemgifts$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_gift', "$dir/UserGifts/usergifts$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'gift', "$dir/UserGifts/usergifts$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_system_messages', "$dir/UserSystemMessages/user_system_messages$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_status', "$dir/UserStatus/userstatus$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_status_history', "$dir/UserStatus/userstatus$dbExt.sql", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'user_status_likes', "$dir/UserStatus/userstatus$dbExt.sql", true ) );

		return true;
	}

	/**
	 * For integration with the Renameuser extension.
	 * The hook registration has been commented out for years because, as the
	 * FIXME below notes, RenameuserSQL doesn't like updating the same table
	 * twice and I figured it'd be better to let capable sysadmins update data
	 * manually than to leave the database in a terribly messy condition.
	 *
	 * @param $renameUserSQL RenameuserSQL
	 * @return Boolean
	 */
	public static function onRenameUserSQL( $renameUserSQL ) {
		$renameUserSQL->tables['user_system_gift'] = array( 'sg_user_name', 'sg_user_id' );
		$renameUserSQL->tables['user_board'] = array( 'ub_user_name_from', 'ub_user_id_from' );
		$renameUserSQL->tables['user_gift'] = array( 'ug_user_name_to', 'ug_user_id_to' );
		$renameUserSQL->tables['gift'] = array( 'gift_creator_user_name', 'gift_creator_user_id' );
		// <fixme> This sucks and only updates half of the rows...wtf?
		$renameUserSQL->tables['user_relationship'] = array( 'r_user_name_relation', 'r_user_id_relation' );
		$renameUserSQL->tables['user_relationship'] = array( 'r_user_name', 'r_user_id' );
		// </fixme>
		$renameUserSQL->tables['user_relationship_request'] = array( 'ur_user_name_from', 'ur_user_id_from' );
		$renameUserSQL->tables['user_stats'] = array( 'stats_user_name', 'stats_user_id' );
		$renameUserSQL->tables['user_system_messages'] = array( 'um_user_name', 'um_user_id' );
		return true;
	}

}