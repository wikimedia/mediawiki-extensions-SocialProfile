<?php

class UserProfileHooks {
	/**
	 * Called by ArticleFromTitle hook
	 * Calls UserProfilePage instead of standard article
	 *
	 * @param Title &$title
	 * @param WikiPage|Article &$article
	 * @return bool
	 */
	public static function onArticleFromTitle( &$title, &$article ) {
		global $wgRequest, $wgOut, $wgHooks, $wgUserPageChoice;

		if (
			!$title->isSubpage() &&
			$title->inNamespaces( [ NS_USER, NS_USER_PROFILE ] )
		) {
			$show_user_page = false;
			if ( $wgUserPageChoice ) {
				$profile = new UserProfile( $title->getText() );
				$profile_data = $profile->getProfile();

				// If they want regular page, ignore this hook
				if ( isset( $profile_data['user_id'] ) && $profile_data['user_id'] && $profile_data['user_page_type'] == 0 ) {
					$show_user_page = true;
				}
			}

			if ( !$show_user_page ) {
				// Prevents editing of userpage
				if ( $wgRequest->getVal( 'action' ) == 'edit' ) {
					$wgOut->redirect( $title->getFullURL() );
				}
			} else {
				$wgOut->enableClientCache( false );
				$wgHooks['ParserLimitReport'][] = 'UserProfileHooks::markPageUncacheable';
			}

			$wgOut->addModuleStyles( [
				'ext.socialprofile.clearfix',
				'ext.socialprofile.userprofile.css'
			] );

			$article = new UserProfilePage( $title );
		}

		return true;
	}

	/**
	 * Mark page as uncacheable
	 *
	 * @param Parser $parser
	 * @param string &$limitReport unused
	 * @return bool
	 */
	public static function markPageUncacheable( $parser, &$limitReport ) {
		$parser->disableCache();
		return true;
	}

	/**
	 * Load the necessary CSS for avatars in diffs if that feature is enabled.
	 *
	 * @param DifferenceEngine $differenceEngine
	 * @return bool
	 */
	public static function onDifferenceEngineShowDiff( $differenceEngine ) {
		global $wgUserProfileAvatarsInDiffs;
		if ( $wgUserProfileAvatarsInDiffs ) {
			$differenceEngine->getOutput()->addModuleStyles( 'ext.socialprofile.userprofile.diff' );
		}
		return true;
	}

	/**
	 * Load the necessary CSS for avatars in diffs if that feature is enabled.
	 *
	 * @param OutputPage $out
	 * @return bool
	 */
	public static function onDifferenceEngineShowDiffPage( $out ) {
		global $wgUserProfileAvatarsInDiffs;
		if ( $wgUserProfileAvatarsInDiffs ) {
			$out->addModuleStyles( 'ext.socialprofile.userprofile.diff' );
		}
		return true;
	}

	/**
	 * Displays user avatars in diffs.
	 *
	 * This is largely based on wikiHow's /extensions/wikihow/hooks/DiffHooks.php
	 * (as of 2016-07-08) with some tweaks for SocialProfile.
	 *
	 * @author Scott Cushman@wikiHow -- original code
	 * @author Jack Phoenix, Samantha Nguyen -- modifications
	 */
	public static function onDifferenceEngineOldHeader( $differenceEngine, &$oldHeader, $prevLink, $oldMinor, $diffOnly, $ldel, $unhide ) {
		global $wgUserProfileAvatarsInDiffs;

		if ( !$wgUserProfileAvatarsInDiffs ) {
			return true;
		}

		$oldRevisionHeader = $differenceEngine->getRevisionHeader( $differenceEngine->mOldRev, 'complete', 'old' );

		$username = $differenceEngine->mOldRev->getUserText();
		$avatar = new wAvatar( $differenceEngine->mOldRev->getUser(), 'l' );
		$avatarElement = $avatar->getAvatarURL( [
			'alt' => $username,
			'title' => $username,
			'class' => 'diff-avatar'
		] );

		$oldHeader = '<div id="mw-diff-otitle1"><h4>' . $oldRevisionHeader . '</h4></div>' .
			'<div id="mw-diff-otitle2">' . $avatarElement . '<div id="mw-diff-oinfo">' .
			Linker::revUserTools( $differenceEngine->mOldRev, !$unhide ) .
			//'<br /><div id="mw-diff-odaysago">' . $differenceEngine->mOldRev->getTimestamp() . '</div>' .
			Linker::revComment( $differenceEngine->mOldRev, !$diffOnly, !$unhide ) .
			'</div></div>' .
			'<div id="mw-diff-otitle3" class="rccomment">' . $oldMinor . $ldel . '</div>' .
			'<div id="mw-diff-otitle4">' . $prevLink . '</div>';

		return true;
	}

	/**
	 * Displays user avatars in diffs.
	 *
	 * This is largely based on wikiHow's /extensions/wikihow/hooks/DiffHooks.php
	 * (as of 2016-07-08) with some tweaks for SocialProfile.
	 *
	 * @author Scott Cushman@wikiHow -- original code
	 * @author Jack Phoenix, Samantha Nguyen -- modifications
	 */
	public static function onDifferenceEngineNewHeader( $differenceEngine, &$newHeader, $formattedRevisionTools, $nextLink, $rollback, $newMinor, $diffOnly, $rdel, $unhide ) {
		global $wgUserProfileAvatarsInDiffs;

		if ( !$wgUserProfileAvatarsInDiffs ) {
			return true;
		}

		$newRevisionHeader =
			$differenceEngine->getRevisionHeader( $differenceEngine->mNewRev, 'complete', 'new' ) .
			' ' . implode( ' ', $formattedRevisionTools );

		$username = $differenceEngine->mNewRev->getUserText();
		$avatar = new wAvatar( $differenceEngine->mNewRev->getUser(), 'l' );
		$avatarElement = $avatar->getAvatarURL( [
			'alt' => $username,
			'title' => $username,
			'class' => 'diff-avatar'
		] );

		$newHeader = '<div id="mw-diff-ntitle1"><h4>' . $newRevisionHeader . '</h4></div>' .
			'<div id="mw-diff-ntitle2">' . $avatarElement . '<div id="mw-diff-oinfo">'
			. Linker::revUserTools( $differenceEngine->mNewRev, !$unhide ) .
			" $rollback " .
			//'<br /><div id="mw-diff-ndaysago">' . $differenceEngine->mNewRev->getTimestamp() . '</div>' .
			Linker::revComment( $differenceEngine->mNewRev, !$diffOnly, !$unhide ) .
			'</div></div>' .
			'<div id="mw-diff-ntitle3" class="rccomment">' . $newMinor . $rdel . '</div>' .
			'<div id="mw-diff-ntitle4">' . $nextLink . $differenceEngine->markPatrolledLink() . '</div>';

		return true;
	}


}
