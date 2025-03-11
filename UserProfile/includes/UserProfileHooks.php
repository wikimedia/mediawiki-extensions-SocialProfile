<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class UserProfileHooks {

	/**
	 * Registers the following custom tags with the Parser:
	 * - <randomuserswithavatars>
	 * - <newusers>
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'randomuserswithavatars', [ 'RandomUsersWithAvatars', 'getRandomUsersWithAvatars' ] );
		$parser->setHook( 'newusers', [ 'NewUsersList', 'getNewUsers' ] );
	}

	/**
	 * Add a class to the <body> element on user pages to indicate which type
	 * of user page -- social profile or traditional wiki user page -- has been
	 * chosen by the user in question to make CSS styling easier.
	 *
	 * @see https://phabricator.wikimedia.org/T167506
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @param array &$bodyAttrs Pre-existing attributes of the <body> tag
	 */
	public static function onOutputPageBodyAttributes( $out, $skin, array &$bodyAttrs ) {
		global $wgUserPageChoice;

		$title = $out->getTitle();
		$pageTitle = $title->getText();
		$userNameUtils = MediaWikiServices::getInstance()->getUserNameUtils();
		// Only NS_USER is "ambiguous", NS_USER_PROFILE and NS_USER_WIKI are not
		// Also we don't care about subpages here since only the main user page
		// can be something else than wikitext
		// Also ignore anonymous users since they can't have social profiles and
		// passing an IP address to UserProfile's constructor would break things
		// Finally also ensure that the username isn't mojibake or other garbage
		// which would fail MW's username validation and thus cause a user-facing
		// fatal error
		if (
			$title->inNamespace( NS_USER ) &&
			!$title->isSubpage() &&
			$wgUserPageChoice &&
			$userNameUtils->isUsable( $pageTitle )
		) {
			$profile = new UserProfile( $pageTitle );
			$profile_data = $profile->getProfile();

			if ( isset( $profile_data['actor'] ) && $profile_data['actor'] ) {
				if ( $profile_data['user_page_type'] == 0 ) {
					$bodyAttrs['class'] .= ' mw-wiki-user-page';
				} else {
					$bodyAttrs['class'] .= ' mw-social-profile-page';
				}
			}
		}
	}

	/**
	 * Mark social user pages as known so they appear in blue, unless the user
	 * is explicitly using a wiki user page, which may or may not exist.
	 *
	 * The assumption here is that when we have a Title pointing to a non-subpage
	 * page in the user NS (i.e. a user profile page), we _probably_ want to treat
	 * it as a blue link unless we have a good reason not to.
	 *
	 * Pages like Special:TopUsers etc. which use LinkRenderer would be slightly
	 * confusing if they'd show a mixture of red and blue links when in fact,
	 * regardless of the URL params, with SocialProfile installed they behave the
	 * same.
	 *
	 * @param Title $title title to check
	 * @param bool &$isKnown Whether the page should be considered known
	 */
	public static function onTitleIsAlwaysKnown( $title, &$isKnown ) {
		// global $wgUserPageChoice;

		$pageTitle = $title->getText();
		$userNameUtils = MediaWikiServices::getInstance()->getUserNameUtils();
		// @todo FIXME: also filter out nonexistent users (viewing the User: page of an
		// account that does not literally exist in the DB)
		if (
			$title->inNamespace( NS_USER ) &&
			!$title->isSubpage() &&
			$userNameUtils->isUsable( $pageTitle )
		) {
			$isKnown = true;
			/* @todo Do we care? Also, how expensive would this be in the long run?
			if ( $wgUserPageChoice ) {
				$profile = new UserProfile( $title->getText() );
				$profile_data = $profile->getProfile();

				if ( isset( $profile_data['user_id'] ) && $profile_data['user_id'] ) {
					if ( $profile_data['user_page_type'] == 0 ) {
						$isKnown = false;
					}
				}
			}
			*/
		}
	}

	/**
	 * Called by ArticleFromTitle hook
	 * Calls UserProfilePage instead of standard article on registered users'
	 * User: or User_profile: pages which are not subpages
	 *
	 * @param Title $title
	 * @param Article|null &$article
	 * @param IContextSource $context
	 */
	public static function onArticleFromTitle( Title $title, &$article, $context ) {
		global $wgUserPageChoice;

		$services = MediaWikiServices::getInstance();
		$out = $context->getOutput();
		$request = $context->getRequest();
		$pageTitle = $title->getText();
		$userNameUtils = $services->getUserNameUtils();
		$hookContainer = $services->getHookContainer();
		if (
			!$title->isSubpage() &&
			$title->inNamespaces( [ NS_USER, NS_USER_PROFILE ] ) &&
			// Avoid new UserProfile( ... ) call below fataling on shitty mojibake usernames
			// which fail core MW username validation; if we don't, there's gonna be a
			// user-facing fatal, and that's nasty.
			$userNameUtils->isUsable( $pageTitle )
		) {
			$show_user_page = false;
			if ( $wgUserPageChoice ) {
				$profile = new UserProfile( $pageTitle );
				$profile_data = $profile->getProfile();

				// If they want regular page, ignore this hook
				if ( isset( $profile_data['actor'] ) && $profile_data['actor'] && $profile_data['user_page_type'] == 0 ) {
					$show_user_page = true;
				}
			}

			if ( $show_user_page ) {
				if ( method_exists( $out, 'disableClientCache' ) ) {
					// MW 1.38+
					$out->disableClientCache();
				} else {
					// @phan-suppress-next-line PhanParamTooMany The arg is there for pre-1.38 MWs
					$out->enableClientCache( false );
				}

				$hookContainer->register( 'ParserLimitReportPrepare', 'UserProfileHooks::onParserLimitReportPrepare' );
			}

			$out->addModuleStyles( [
				'ext.socialprofile.clearfix',
				'ext.socialprofile.userprofile.css'
			] );

			$article = new UserProfilePage( $title );
		}
	}

	/**
	 * Redirect action=edit attempts on a social profile page to a meaningful
	 * URL, which is either:
	 * -Special:UpdateProfile (if user is attempting to edit their own profile)
	 * -Special:EditProfile (if user is privileged and attempting to edit someone
	 *   else's profile)
	 * -the profile page (if user is attempting to edit someone else's profile
	 *   without being allowed to do that)
	 *
	 * @param UserProfilePage|WikiPage $article
	 * @param User $user
	 * @return bool
	 */
	public static function onCustomEditor( $article, $user ) {
		global $wgUserPageChoice;

		$title = $article->getTitle();
		$pageName = $title->getText();

		// We only care about pages which can be social profile pages here, so
		// ignore all other pages.
		// Also, always ignore subpages, we only care about the user's "main" User: or User_profile: page.
		if ( !$title->inNamespaces( [ NS_USER, NS_USER_PROFILE ] ) || $title->isSubpage() ) {
			return true;
		}

		$userObjectOrThenMaybeNotIfItIsAnIPAddress = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $pageName );
		if ( !$userObjectOrThenMaybeNotIfItIsAnIPAddress ) {
			// For fuck's sake, MediaWiki.
			return true;
		}

		$isAnon = $userObjectOrThenMaybeNotIfItIsAnIPAddress->isAnon();
		// Ignore anonymous users, they can't have a social profile page.
		// This is needed to prevent the "new UserProfile" initialization below from throwing exceptions
		// upon trying to edit an anon user's User: page.
		if ( $isAnon ) {
			return true;
		}

		$show_wikitext_user_page = false;

		if ( $wgUserPageChoice ) {
			$profile = new UserProfile( $pageName );
			$profile_data = $profile->getProfile();

			// If they want regular page, ignore this hook
			if (
				isset( $profile_data['actor'] ) &&
				$profile_data['actor'] &&
				$profile_data['user_page_type'] == 0
			) {
				$show_wikitext_user_page = true;
			}
		}

		if (
			!$show_wikitext_user_page &&
			$article->getContext()->getRequest()->getVal( 'action' ) == 'edit'
		) {
			$out = $article->getContext()->getOutput();

			$userOwnsThisProfile = (
				$title->equals( $user->getUserPage() ) ||
				$title->equals( Title::makeTitle( NS_USER_PROFILE, $user->getName() ) )
			);

			// Prevents direct editing of the user page and redirects the user to the appropriate special page
			// (if applicable)
			if ( $userOwnsThisProfile ) {
				$out->redirect( SpecialPage::getTitleFor( 'UpdateProfile' )->getFullURL() );
				return false;
			} elseif ( $user->isAllowed( 'editothersprofiles' ) ) {
				$out->redirect( SpecialPage::getTitleFor( 'EditProfile', $pageName )->getFullURL() );
				return false;
			}

			$out->redirect( $title->getFullURL() );

			return false;
		}

		return true;
	}

	/**
	 * Potentially prevent editing User: & User_profile: pages via the API (api.php) if the user in question
	 * has opted to use a social user profile page instead.
	 *
	 * @todo This and the above function duplicate _a lot_ of code with each other... :-/
	 *
	 * @param ApiBase $module
	 * @param User $user
	 * @param IApiMessage|Message|string|array &$message
	 * @return bool
	 */
	public static function onApiCheckCanExecute( $module, $user, &$message ) {
		global $wgUserPageChoice;

		$moduleName = $module->getModuleName();

		if ( $moduleName == 'edit' ) {
			$params = $module->extractRequestParams();
			$pageObj = $module->getTitleOrPageId( $params );
			$title = $pageObj->getTitle();
			$pageName = $title->getText();

			// We only care about pages which can be social profile pages here, so
			// ignore all other pages.
			// Also, always ignore subpages, we only care about the user's "main" User: or User_profile: page.
			if ( !$title->inNamespaces( [ NS_USER, NS_USER_PROFILE ] ) || $title->isSubpage() ) {
				return true;
			}

			$userObjectOrThenMaybeNotIfItIsAnIPAddress = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $pageName );
			if ( !$userObjectOrThenMaybeNotIfItIsAnIPAddress ) {
				// For fuck's sake, MediaWiki.
				return true;
			}

			$isAnon = $userObjectOrThenMaybeNotIfItIsAnIPAddress->isAnon();
			// Ignore anonymous users, they can't have a social profile page.
			// This is needed to prevent the "new UserProfile" initialization below from throwing exceptions
			// upon trying to edit an anon user's User: page.
			if ( $isAnon ) {
				return true;
			}

			$show_wikitext_user_page = false;

			if ( $wgUserPageChoice ) {
				$profile = new UserProfile( $pageName );
				$profile_data = $profile->getProfile();

				// If they want regular page, ignore this hook
				if (
					isset( $profile_data['actor'] ) &&
					$profile_data['actor'] &&
					$profile_data['user_page_type'] == 0
				) {
					$show_wikitext_user_page = true;
				}
			}

			if ( !$show_wikitext_user_page ) {
				$message = 'user-profile-error-no-api-edit';
				return false;
			}

			return true;
		}

		return true;
	}

	/**
	 * Mark page as uncacheable
	 *
	 * @param Parser $parser
	 * @param ParserOutput $output
	 */
	public static function onParserLimitReportPrepare( $parser, $output ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
	}

	/**
	 * Load the necessary CSS for avatars in diffs if that feature is enabled.
	 *
	 * @param DifferenceEngine $differenceEngine
	 */
	public static function onDifferenceEngineShowDiff( $differenceEngine ) {
		global $wgUserProfileAvatarsInDiffs;
		if ( $wgUserProfileAvatarsInDiffs ) {
			$differenceEngine->getOutput()->addModuleStyles( 'ext.socialprofile.userprofile.diff' );
		}
	}

	/**
	 * Displays user avatars in diffs.
	 *
	 * This is largely based on wikiHow's /extensions/wikihow/hooks/DiffHooks.php
	 * (as of 2016-07-08) with some tweaks for SocialProfile.
	 *
	 * @author Scott Cushman@wikiHow -- original code
	 * @author Jack Phoenix, Samantha Nguyen -- modifications
	 *
	 * @param DifferenceEngine $differenceEngine
	 * @param string &$oldHeader
	 * @param string $prevLink
	 * @param string $oldMinor
	 * @param bool $diffOnly
	 * @param string $ldel
	 * @param bool $unhide
	 */
	public static function onDifferenceEngineOldHeader( $differenceEngine, &$oldHeader, $prevLink, $oldMinor, $diffOnly, $ldel, $unhide ) {
		global $wgUserProfileAvatarsInDiffs;

		if ( !$wgUserProfileAvatarsInDiffs ) {
			return;
		}

		// Need a RevisionRecord object
		$oldRevision = $differenceEngine->getOldRevision();

		// Core MW DifferenceEngine#getRevisionHeader never had a 3rd parameter.
		// wikiHow introduced it for a custom hook of theirs, which hasn't (yet)
		// been upstreamed. If uncommented, this would cause a PhanParamTooMany
		// issue, but no adverse functionality whatsoever.
		$oldRevisionHeader = $differenceEngine->getRevisionHeader( $oldRevision, 'complete'/*, 'old'*/ );

		$oldRevUser = $oldRevision->getUser();
		if ( $oldRevUser ) {
			$username = $oldRevUser->getName();
			$uid = $oldRevUser->getId();
		} else {
			return;
		}

		$avatar = new wAvatar( $uid, 'l' );
		$avatarElement = $avatar->getAvatarURL( [
			'alt' => $username,
			'title' => $username,
			'class' => 'diff-avatar'
		] );

		$oldHeader = '<div id="mw-diff-otitle1"><strong>' . $oldRevisionHeader . '</strong></div>' .
			'<div id="mw-diff-otitle2">' . $avatarElement . '<div id="mw-diff-oinfo">' .
			Linker::revUserTools( $oldRevision, !$unhide ) .
			// '<br /><div id="mw-diff-odaysago">' . $differenceEngine->mOldRev->getTimestamp() . '</div>' .
			MediaWikiServices::getInstance()->getCommentFormatter()
				->formatRevision( $oldRevision, $differenceEngine->getAuthority(), !$diffOnly, !$unhide ) .
			'</div></div>' .
			'<div id="mw-diff-otitle3" class="rccomment">' . $oldMinor . $ldel . '</div>' .
			'<div id="mw-diff-otitle4">' . $prevLink . '</div>';
	}

	/**
	 * Displays user avatars in diffs.
	 *
	 * This is largely based on wikiHow's /extensions/wikihow/hooks/DiffHooks.php
	 * (as of 2016-07-08) with some tweaks for SocialProfile.
	 *
	 * @author Scott Cushman@wikiHow -- original code
	 * @author Jack Phoenix, Samantha Nguyen -- modifications
	 *
	 * @param DifferenceEngine $differenceEngine
	 * @param string &$newHeader
	 * @param string[] $formattedRevisionTools
	 * @param string $nextLink
	 * @param string $rollback
	 * @param string $newMinor
	 * @param bool $diffOnly
	 * @param string $rdel
	 * @param bool $unhide
	 */
	public static function onDifferenceEngineNewHeader( $differenceEngine, &$newHeader, $formattedRevisionTools, $nextLink, $rollback, $newMinor, $diffOnly, $rdel, $unhide ) {
		global $wgUserProfileAvatarsInDiffs;

		if ( !$wgUserProfileAvatarsInDiffs ) {
			return;
		}

		// Need a RevisionRecord object
		$newRevision = $differenceEngine->getNewRevision();

		// Core MW DifferenceEngine#getRevisionHeader never had a 3rd parameter.
		// wikiHow introduced it for a custom hook of theirs, which hasn't (yet)
		// been upstreamed. If uncommented, this would cause a PhanParamTooMany
		// issue, but no adverse functionality whatsoever.
		$newRevisionHeader =
			$differenceEngine->getRevisionHeader( $newRevision, 'complete'/*, 'new'*/ ) .
			' ' . implode( ' ', $formattedRevisionTools );

		$newRevUser = $newRevision->getUser();
		if ( $newRevUser ) {
			$username = $newRevUser->getName();
			$uid = $newRevUser->getId();
		} else {
			return;
		}

		$avatar = new wAvatar( $uid, 'l' );
		$avatarElement = $avatar->getAvatarURL( [
			'alt' => $username,
			'title' => $username,
			'class' => 'diff-avatar'
		] );

		$newHeader = '<div id="mw-diff-ntitle1"><strong>' . $newRevisionHeader . '</strong></div>' .
			'<div id="mw-diff-ntitle2">' . $avatarElement . '<div id="mw-diff-oinfo">'
			. Linker::revUserTools( $newRevision, !$unhide ) .
			" $rollback " .
			// '<br /><div id="mw-diff-ndaysago">' . $differenceEngine->mNewRev->getTimestamp() . '</div>' .
			MediaWikiServices::getInstance()->getCommentFormatter()
				->formatRevision( $newRevision, $differenceEngine->getAuthority(), !$diffOnly, !$unhide ) .
			'</div></div>' .
			'<div id="mw-diff-ntitle3" class="rccomment">' . $newMinor . $rdel . '</div>' .
			'<div id="mw-diff-ntitle4">' . $nextLink . $differenceEngine->markPatrolledLink() . '</div>';
	}

}
