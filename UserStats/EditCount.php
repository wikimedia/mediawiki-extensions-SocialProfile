<?php
/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This is not a valid entry point.\n" );
}

/**
 * For the UserLevels (points) functionality to work, you will need to
 * define $wgUserLevels and require_once() this file in your wiki's
 * LocalSettings.php file.
 */
$wgHooks['NewRevisionFromEditComplete'][] = 'incEditCount';
$wgHooks['ArticleDelete'][] = 'removeDeletedEdits';
$wgHooks['ArticleUndelete'][] = 'restoreDeletedEdits';

/**
 * Updates user's points after they've made an edit in a namespace that is
 * listed in the $wgNamespacesForEditPoints array.
 *
 * @param WikiPage $wikiPage
 * @param Revision $revision
 * @param int $baseRevId
 * @return bool true
 */
function incEditCount( WikiPage $wikiPage, $revision, $baseRevId ) {
	global $wgUser, $wgNamespacesForEditPoints;

	// only keep tally for allowable namespaces
	if (
		!is_array( $wgNamespacesForEditPoints ) ||
		in_array( $wikiPage->getTitle()->getNamespace(), $wgNamespacesForEditPoints )
	) {
		$stats = new UserStatsTrack( $wgUser->getId(), $wgUser->getName() );
		$stats->incStatField( 'edit' );
	}

	return true;
}

/**
 * Updates user's points after a page in a namespace that is listed in the
 * $wgNamespacesForEditPoints array that they've edited has been deleted.
 *
 * @param WikiPage $article
 * @param User $user
 * @param string $reason
 * @return bool true
 */
function removeDeletedEdits( &$article, &$user, &$reason ) {
	global $wgActorTableSchemaMigrationStage, $wgNamespacesForEditPoints;

	// only keep tally for allowable namespaces
	if (
		!is_array( $wgNamespacesForEditPoints ) ||
		in_array( $article->getTitle()->getNamespace(), $wgNamespacesForEditPoints )
	) {
		$dbr = wfGetDB( DB_MASTER );
		$revQuery = MediaWiki\MediaWikiServices::getInstance()->getRevisionStore()->getQueryInfo();
		$pageField = ( $wgActorTableSchemaMigrationStage & SCHEMA_COMPAT_READ_NEW )
			? 'revactor_page' : 'rev_page';
		$userNameField = $revQuery['fields']['rev_user_text'];
		$res = $dbr->select(
			$revQuery['tables'],
			array_merge( $revQuery['fields'], [ 'COUNT(*) AS the_count' ] ),
			[
				$pageField => $article->getID(),
				ActorMigration::newMigration()->isNotAnon( $revQuery['fields']['rev_user'] )
			],
			__METHOD__,
			[ 'GROUP BY' => $userNameField ],
			$revQuery['joins']
		);
		foreach ( $res as $row ) {
			if ( $wgActorTableSchemaMigrationStage & SCHEMA_COMPAT_READ_NEW ) {
				$u = User::newFromActorId( $row->rev_actor );
				$uid = $u->getId();
				$userName = $u->getName();
			} else {
				$uid = $row->rev_user;
				$userName = $row->rev_user_text;
			}
			$stats = new UserStatsTrack( $uid, $userName );
			$stats->decStatField( 'edit', $row->the_count );
		}
	}

	return true;
}

/**
 * Updates user's points after a page in a namespace that is listed in the
 * $wgNamespacesForEditPoints array that they've edited has been restored after
 * it was originally deleted.
 *
 * @param Title $title
 * @param bool $new
 * @return bool true
 */
function restoreDeletedEdits( &$title, $new ) {
	global $wgActorTableSchemaMigrationStage, $wgNamespacesForEditPoints;

	// only keep tally for allowable namespaces
	if (
		!is_array( $wgNamespacesForEditPoints ) ||
		in_array( $title->getNamespace(), $wgNamespacesForEditPoints )
	) {
		$dbr = wfGetDB( DB_MASTER );
		$revQuery = MediaWiki\MediaWikiServices::getInstance()->getRevisionStore()->getQueryInfo();
		$pageField = ( $wgActorTableSchemaMigrationStage & SCHEMA_COMPAT_READ_NEW )
			? 'revactor_page' : 'rev_page';
		$userNameField = $revQuery['fields']['rev_user_text'];
		$res = $dbr->select(
			$revQuery['tables'],
			array_merge( $revQuery['fields'], [ 'COUNT(*) AS the_count' ] ),
			[
				$pageField => $title->getArticleID(),
				ActorMigration::newMigration()->isNotAnon( $revQuery['fields']['rev_user'] )
			],
			__METHOD__,
			[ 'GROUP BY' => $userNameField ],
			$revQuery['joins']
		);
		foreach ( $res as $row ) {
			if ( $wgActorTableSchemaMigrationStage & SCHEMA_COMPAT_READ_NEW ) {
				$u = User::newFromActorId( $row->rev_actor );
				$uid = $u->getId();
				$userName = $u->getName();
			} else {
				$uid = $row->rev_user;
				$userName = $row->rev_user_text;
			}
			$stats = new UserStatsTrack( $uid, $userName );
			$stats->incStatField( 'edit', $row->the_count );
		}
	}

	return true;
}
