<?php
/**
 * A special page for viewing all relationships by type
 *
 * Example URL: index.php?title=Special:ViewRelationships/Pean/friends (viewing friends)
 * Example URL: index.php?title=Special:ViewRelationships/Pean/foes (viewing foes)
 *
 * Example old URL: index.php?title=Special:ViewRelationships&user=Pean&rel_type=1 (viewing friends)
 * Example old URL: index.php?title=Special:ViewRelationships&user=Pean&rel_type=2 (viewing foes)
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license GPL-2.0-or-later
 */

class SpecialViewRelationships extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ViewRelationships' );
	}

	/**
	 * Group this special page under the correct header in Special:SpecialPages.
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'users';
	}

	/**
	 * Show this special page on Special:SpecialPages only for registered users
	 *
	 * @return bool
	 */
	function isListed() {
		return (bool)$this->getUser()->isRegistered();
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $params Forward slash separated parameters (user/type), e.g. "Alice/friends"
	 *  to view Alice's friend list
	 */
	public function execute( $params ) {
		$lang = $this->getLanguage();
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();
		$linkRenderer = $this->getLinkRenderer();

		/**
		 * Redirect Non-logged in users to Login Page
		 * It will automatically return them to the ViewRelationships page
		 */
		$this->requireLogin();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.userrelationship.css' );

		$output = '';

		/**
		 * Get query string variables
		 */
		$user_name = $request->getVal( 'user' );
		$rel_type = $request->getInt( 'rel_type' );
		$page = $request->getInt( 'page' );

		// Support for friendly-by-default URLs (T191157)
		$params = explode( '/', $params );
		if ( count( $params ) === 2 ) {
			$user_name = $params[0];
			$rel_type = ( $params[1] === 'foes' ? 2 : 1 );
		}

		/**
		 * Set up config for page / default values
		 */
		if ( !$page || !is_numeric( $page ) ) {
			$page = 1;
		}
		if ( !$rel_type || !is_numeric( $rel_type ) ) {
			$rel_type = 1;
		}
		$per_page = 50;
		$per_row = 2;

		/**
		 * If no user is set in the URL, we assume its the current user
		 */
		$targetUser = false;
		if ( $user_name ) {
			$targetUser = User::newFromName( $user_name );
		}
		if ( !$targetUser || $targetUser->isAnon() ) {
			/**
			 * Error message for username that does not exist (from URL)
			 */
			if ( $user_name ) {
				$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );
				$output = '<div class="relationship-error-message">' .
					$this->msg( 'ur-error-message-no-user' )->escaped() .
				'</div>
				<div class="relationship-request-buttons">
					<input type="button" class="site-button" value="' . $this->msg( 'mainpage' )->escaped() . '" onclick=\'window.location="index.php?title=' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
				if ( $user->isRegistered() ) {
					$output .= '<input type="button" class="site-button" value="' . $this->msg( 'ur-your-profile' )->escaped() . '" onclick=\'window.location="' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '"\' />';
				}
				$output .= '</div>';
				$out->addHTML( $output );
				return;
			}

			$targetUser = $user;
		}
		$userPage = $targetUser->getUserPage();

		/**
		 * Get all relationships
		 */
		$rel = new UserRelationship( $targetUser );
		$listLookup = new RelationshipListLookup( $targetUser, $per_page );
		$relationships = $listLookup->getRelationshipList( $rel_type, $page );

		$stats = new UserStats( $targetUser );
		$stats_data = $stats->getUserStats();
		$friend_count = $stats_data['friend_count'];
		$foe_count = $stats_data['foe_count'];

		if ( $rel_type == 1 ) {
			$out->setPageTitle( $this->msg( 'ur-title-friend', $targetUser->getName() )->parse() );

			$total = $friend_count;

			$rem = $this->msg( 'ur-remove-relationship-friend' )->plain();
			$output .= '<div class="back-links">
			<a href="' . htmlspecialchars( $targetUser->getUserPage()->getFullURL() ) . '">' .
				$this->msg( 'ur-backlink', $targetUser->getName() )->parse() .
			'</a>
		</div>
		<div class="relationship-count">' .
			$this->msg(
				'ur-relationship-count-friends',
				$targetUser->getName(),
				$total
			)->escaped() . '</div>';
		} else {
			$out->setPageTitle( $this->msg( 'ur-title-foe', $targetUser->getName() )->parse() );

			$total = $foe_count;

			$rem = $this->msg( 'ur-remove-relationship-foe' )->plain();

			$output .= '<div class="back-links">
			<a href="' . htmlspecialchars( $targetUser->getUserPage()->getFullURL() ) . '">' .
				$this->msg( 'ur-backlink', $targetUser->getName() )->parse() .
			'</a>
		</div>
		<div class="relationship-count">'
			. $this->msg(
				'ur-relationship-count-foes',
				$targetUser->getName(),
				$total
			)->escaped() . '</div>';
		}

		if ( $relationships ) {
			$x = 1;

			foreach ( $relationships as $relationship ) {
				$actor = User::newFromActorId( $relationship['actor'] );
				if ( !$actor ) {
					continue;
				}

				$indivRelationship = UserRelationship::getUserRelationshipByID( $actor, $user );

				// Safe titles
				$addRelationshipLink = SpecialPage::getTitleFor( 'AddRelationship' );
				$removeRelationshipLink = SpecialPage::getTitleFor( 'RemoveRelationship' );
				$giveGiftLink = SpecialPage::getTitleFor( 'GiveGift' );

				$userPageURL = htmlspecialchars( $actor->getUserPage()->getFullURL() );
				$avatar = new wAvatar( $actor->getId(), 'ml' );

				$avatar_img = $avatar->getAvatarURL();

				$username_length = strlen( $actor->getName() );
				$username_space = stripos( $actor->getName(), ' ' );

				// Insert a space at position 30 if the first word of the name
				// has more than 30 characters, truncate at 50 characters
				// @todo Or just use $this->getLanguage()->truncateForVisual( $actor->getName(), 50 )?
				if ( ( $username_space == false || $username_space >= "30" ) && $username_length > 30 ) {
					$user_name_display = substr( $actor->getName(), 0, 30 ) .
						' ' . substr( $actor->getName(), 30, 50 );
				} else {
					$user_name_display = $actor->getName();
				}
				$user_name_display = htmlspecialchars( $user_name_display );

				$output .= "<div class=\"relationship-item\">
					<a href=\"{$userPageURL}\">{$avatar_img}</a>
					<div class=\"relationship-info\">
						<div class=\"relationship-name\">
							<a href=\"{$userPageURL}\">{$user_name_display}</a>
						</div>
					<div class=\"relationship-actions\">";

				// Provide links to add/remove as foe/friend and give a gift, except for ourselves
				if ( $relationship['actor'] != $user->getActorId() ) {
					if ( $indivRelationship == false ) {
						// No relationship with us, links to add relationship
						$output .= $lang->pipeList( [
							$linkRenderer->makeLink(
								$addRelationshipLink,
								$this->msg( 'ur-add-friend' )->text(),
								[],
								[ 'user' => $actor->getName(), 'rel_type' => 1 ]
							),
							$linkRenderer->makeLink(
								$addRelationshipLink,
								$this->msg( 'ur-add-foe' )->text(),
								[],
								[ 'user' => $actor->getName(), 'rel_type' => 2 ]
							),
							''
						] );
					} elseif ( $targetUser->getActorId() === $user->getActorId() ) {
						// Our relationships page, link to remove
						$output .= $linkRenderer->makeLink(
							$removeRelationshipLink,
							$rem,
							[],
							[ 'user' => $actor->getName() ]
						);
						$output .= $this->msg( 'pipe-separator' )->escaped();
					}

					$output .= $linkRenderer->makeLink(
						$giveGiftLink,
						$this->msg( 'ur-give-gift' )->text(),
						[],
						[ 'user' => $actor->getName() ]
					);
				} else {
					// Add an empty space to account for the lack of links
					$output .= '&nbsp;';
				}

				$output .= '</div>
					<div class="visualClear"></div>
				</div>';

				$output .= '</div>';
				if ( $x == count( $relationships ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}
				$x++;
			}
		}

		/**
		 * Build next/prev nav
		 */
		$total = intval( $total );
		$numofpages = $total / $per_page;

		$pageLink = $this->getPageTitle();

		if ( $numofpages > 1 ) {
			$output .= '<div class="page-nav">';
			if ( $page > 1 ) {
				$output .= $linkRenderer->makeLink(
					$pageLink,
					$this->msg( 'last' )->text(),
					[],
					[
						'user' => $user_name,
						'rel_type' => $rel_type,
						'page' => ( $page - 1 )
					]
				) . $this->msg( 'word-separator' )->escaped();
			}

			if ( ( $total % $per_page ) != 0 ) {
				$numofpages++;
			}
			if ( $numofpages >= 9 && $page < $total ) {
				$numofpages = 9 + $page;
			}
			if ( $numofpages >= ( $total / $per_page ) ) {
				$numofpages = ( $total / $per_page ) + 1;
			}

			for ( $i = 1; $i <= $numofpages; $i++ ) {
				if ( $i == $page ) {
					$output .= ( $i . ' ' );
				} else {
					$output .= $linkRenderer->makeLink(
						$pageLink,
						$i,
						[],
						[
							'user' => $user_name,
							'rel_type' => $rel_type,
							'page' => $i
						]
					) . $this->msg( 'word-separator' )->escaped();
				}
			}

			if ( ( $total - ( $per_page * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->escaped() .
					$linkRenderer->makeLink(
						$pageLink,
						$this->msg( 'next' )->text(),
						[],
						[
							'user' => $user_name,
							'rel_type' => $rel_type,
							'page' => ( $page + 1 )
						]
					);
			}
			$output .= '</div>';
		}

		$out->addHTML( $output );
	}
}
