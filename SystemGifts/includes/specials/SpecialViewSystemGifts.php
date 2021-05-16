<?php
/**
 * A special page to view the list of system gifts (awards) a user has.
 *
 * @file
 * @ingroup Extensions
 */

class ViewSystemGifts extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ViewSystemGifts' );
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
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$linkRenderer = $this->getLinkRenderer();

		$out = $this->getOutput();
		$request = $this->getRequest();
		$currentUser = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( [
			'ext.socialprofile.systemgifts.css',
			'ext.socialprofile.special.viewsystemgifts.css'
		] );

		$output = '';
		$user_name = $request->getVal( 'user' );
		$page = $request->getInt( 'page', 1 );

		/**
		 * Redirect Non-logged in users to Login Page
		 * It will automatically return them to the ViewSystemGifts page
		 */
		if ( !$currentUser->isRegistered() && $user_name == '' ) {
			$out->setPageTitle( $this->msg( 'ga-error-title' ) );
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			$out->redirect( htmlspecialchars( $login->getFullURL( 'returnto=Special:ViewSystemGifts' ) ) );
			return;
		}

		/**
		 * If no user is set in the URL, we assume it's the current user
		 */
		if ( !$user_name ) {
			$user_name = $currentUser->getName();
		}
		$targetUser = User::newFromName( $user_name );

		/**
		 * Error message for username that does not exist (from URL)
		 */
		if ( $targetUser->getId() == 0 ) {
			$out->setPageTitle( $this->msg( 'ga-error-title' ) );
			$out->addHTML( $this->msg( 'ga-error-message-no-user' )->escaped() );
			return;
		}

		/**
		 * Config for the page
		 */
		$per_page = 10;
		$per_row = 2;

		/**
		 * Get all Gifts for this user into the array
		 */
		$listLookup = new SystemGiftListLookup( $per_page, $page );
		$rel = new UserSystemGifts( $targetUser );

		$gifts = $listLookup->getUserGiftList( $targetUser );
		$total = $rel->getGiftCountByUsername( $targetUser );

		/**
		 * Show gift count for user
		 */
		$out->setPageTitle( $this->msg( 'ga-title', $rel->user_name ) );

		$output .= '<div class="back-links">' .
			$this->msg(
				'ga-back-link',
				// could also be using $targetUser->getName() here but doesn't really matter
				// since both of the vars should have the same value here
				$rel->user_name
			)->parse() . '</div>';

		$output .= '<div class="ga-count">' .
			$this->msg( 'ga-count', $rel->user_name, $total )->parse() .
		'</div>';

		// Safelinks
		$view_system_gift_link = SpecialPage::getTitleFor( 'ViewSystemGift' );

		if ( $gifts ) {
			$x = 1;

			foreach ( $gifts as $gift ) {
				$systemGiftIcon = new SystemGiftIcon( $gift['gift_id'], 'ml' );
				$icon = $systemGiftIcon->getIconHTML();

				$output .= "<div class=\"ga-item\">
					{$icon}
					<a href=\"" .
						htmlspecialchars( $view_system_gift_link->getFullURL( 'gift_id=' . $gift['id'] ) ) .
						'">' . htmlspecialchars( $gift['gift_name'], ENT_QUOTES ) . '</a>';

				if ( $gift['status'] == 1 ) {
					if ( $user_name == $currentUser->getName() ) {
						$rel->clearUserGiftStatus( $gift['id'] );
					}
					$output .= '<span class="ga-new">' .
						$this->msg( 'ga-new' )->escaped() . '</span>';
				}

				$output .= '<div class="visualClear"></div>
				</div>';
				if ( $x == count( $gifts ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}

				$x++;
			}
		}

		/**
		 * Build next/prev nav
		 */
		$numofpages = $total / $per_page;

		$page_link = $this->getPageTitle();

		if ( $numofpages > 1 ) {
			$output .= '<div class="page-nav">';

			if ( $page > 1 ) {
				$output .= $linkRenderer->makeLink(
					$page_link,
					$this->msg( 'last' )->text(),
					[],
					[
						'user' => $user_name,
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
						$page_link,
						$i,
						[],
						[
							'user' => $user_name,
							'page' => $i
						]
					) . $this->msg( 'word-separator' )->escaped();
				}
			}

			if ( ( $total - ( $per_page * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->escaped() .
					$linkRenderer->makeLink(
						$page_link,
						$this->msg( 'next' )->text(),
						[],
						[
							'user' => $user_name,
							'page' => ( $page + 1 )
						]
					);
			}

			$output .= '</div>';
		}

		/**
		 * Output everything
		 */
		$out->addHTML( $output );
	}
}
