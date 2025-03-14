<?php
/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This is not a valid entry point.\n" );
}

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

/**
 * For the UserLevels (points) functionality to work, you will need to
 * define $wgUserLevels and require_once() this file in your wiki's
 * LocalSettings.php file.
 */
$wgHooks['RevisionFromEditComplete'][] = 'incEditCount';
$wgHooks['ArticleDelete'][] = 'removeDeletedEdits';
$wgHooks['ArticleUndelete'][] = 'restoreDeletedEdits';

/**
 * Updates user's points after they've made an edit in a namespace that is
 * listed in the $wgNamespacesForEditPoints array.
 *
 * @param WikiPage $wikiPage
 * @param MediaWiki\Revision\RevisionRecord $revision
 * @param int $baseRevId Revision ID if the edit restores or repeats an
 *   earlier revision (such as a rollback or a null revision), otherwise bool false
 * @param MediaWiki\User\UserIdentity $user The user who performed the edit in question
 */
function incEditCount( WikiPage $wikiPage, $revision, $baseRevId, $user ) {
	global $wgNamespacesForEditPoints;

	// only keep tally for allowable namespaces
	if (
		!is_array( $wgNamespacesForEditPoints ) ||
		in_array( $wikiPage->getTitle()->getNamespace(), $wgNamespacesForEditPoints )
	) {
		if ( method_exists( $user, 'getActorId' ) ) {
			// MediaWiki 1.35
			// @phan-suppress-next-line PhanUndeclaredMethod Removed in MW 1.41
			$actorId = $user->getActorId();
		} else {
			// MediaWiki 1.36+
			$userObj = User::newFromName( $user->getName() );
			$actorId = $userObj->getActorId();
		}
		$stats = new UserStatsTrack( $actorId );
		$stats->incStatField( 'edit' );
	}
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
function removeDeletedEdits( WikiPage $article, $user, $reason ) {
	global $wgNamespacesForEditPoints;

	// only keep tally for allowable namespaces
	if (
		!is_array( $wgNamespacesForEditPoints ) ||
		in_array( $article->getTitle()->getNamespace(), $wgNamespacesForEditPoints )
	) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );

		$MW139orEarlier = version_compare( MW_VERSION, '1.39', '<' );
		if ( $MW139orEarlier ) {
			$res = $dbr->select(
				[ 'revision_actor_temp', 'revision', 'actor' ],
				[ 'COUNT(*) AS the_count', 'revactor_actor' ],
				[
					'revactor_page' => $article->getID(),
					'actor_user IS NOT NULL'
				],
				__FUNCTION__,
				[ 'GROUP BY' => 'actor_name' ],
				[
					'actor' => [ 'JOIN', 'actor_id = revactor_actor' ],
					'revision_actor_temp' => [ 'JOIN', 'revactor_rev = rev_id' ]
				]
			);
		} else {
			$res = $dbr->select(
				[ 'revision', 'actor' ],
				[ 'COUNT(*) AS the_count', 'rev_actor' ],
				[
					'rev_page' => $article->getID(),
					'actor_user IS NOT NULL'
				],
				__FUNCTION__,
				[ 'GROUP BY' => 'actor_name' ],
				[
					'actor' => [ 'JOIN', 'actor_id = rev_actor' ]
				]
			);
		}

		foreach ( $res as $row ) {
			$columnName = $MW139orEarlier ? 'revactor_actor' : 'rev_actor';
			$stats = new UserStatsTrack( $row->$columnName );
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
function restoreDeletedEdits( Title $title, $new ) {
	global $wgNamespacesForEditPoints;

	// only keep tally for allowable namespaces
	if (
		!is_array( $wgNamespacesForEditPoints ) ||
		in_array( $title->getNamespace(), $wgNamespacesForEditPoints )
	) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );

		$MW139orEarlier = version_compare( MW_VERSION, '1.39', '<' );
		if ( $MW139orEarlier ) {
			$res = $dbr->select(
				[ 'revision_actor_temp', 'revision', 'actor' ],
				[ 'COUNT(*) AS the_count', 'revactor_actor' ],
				[
					'revactor_page' => $title->getArticleID(),
					'actor_user IS NOT NULL'
				],
				__FUNCTION__,
				[ 'GROUP BY' => 'actor_name' ],
				[
					'actor' => [ 'JOIN', 'actor_id = revactor_actor' ],
					'revision_actor_temp' => [ 'JOIN', 'revactor_rev = rev_id' ]
				]
			);
		} else {
			$res = $dbr->select(
				[ 'revision', 'actor' ],
				[ 'COUNT(*) AS the_count', 'rev_actor' ],
				[
					'rev_page' => $title->getArticleID(),
					'actor_user IS NOT NULL'
				],
				__FUNCTION__,
				[ 'GROUP BY' => 'actor_name' ],
				[
					'actor' => [ 'JOIN', 'actor_id = rev_actor' ]
				]
			);
		}

		foreach ( $res as $row ) {
			$columnName = $MW139orEarlier ? 'revactor_actor' : 'rev_actor';
			$stats = new UserStatsTrack( $row->$columnName );
			$stats->incStatField( 'edit', $row->the_count );
		}
	}

	return true;
}
