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

}