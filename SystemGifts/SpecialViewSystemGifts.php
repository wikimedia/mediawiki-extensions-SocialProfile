<?php
/**
 * A special page to view the list of system gifts (awards) a user has.
 *
 * @file
 * @ingroup Extensions
 */

class ViewSystemGifts extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ViewSystemGifts' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgUploadPath;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.systemgifts.css' );

		$output = '';
		$user_name = $request->getVal( 'user' );
		$page = $request->getInt( 'page', 1 );

		/**
		 * Redirect Non-logged in users to Login Page
		 * It will automatically return them to the ViewSystemGifts page
		 */
		if ( $user->getID() == 0 && $user_name == '' ) {
			$out->setPageTitle( $this->msg( 'ga-error-title' )->plain() );
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			$out->redirect( htmlspecialchars( $login->getFullURL( 'returnto=Special:ViewSystemGifts' ) ) );
			return false;
		}

		/**
		 * If no user is set in the URL, we assume it's the current user
		 */
		if ( !$user_name ) {
			$user_name = $user->getName();
		}
		$user_id = User::idFromName( $user_name );

		/**
		 * Error message for username that does not exist (from URL)
		 */
		if ( $user_id == 0 ) {
			$out->setPageTitle( $this->msg( 'ga-error-title' )->plain() );
			$out->addHTML( $this->msg( 'ga-error-message-no-user' )->plain() );
			return false;
		}

		/**
		* Config for the page
		*/
		$per_page = 10;
		$per_row = 2;

		/**
		 * Get all Gifts for this user into the array
		 */
		$rel = new UserSystemGifts( $user_name );

		$gifts = $rel->getUserGiftList( 0, $per_page, $page );
		$total = $rel->getGiftCountByUsername( $user_name );

		/**
		 * Show gift count for user
		 */
		$out->setPageTitle( $this->msg( 'ga-title', $rel->user_name )->parse() );

		$output .= '<div class="back-links">' .
			$this->msg(
				'ga-back-link',
				htmlspecialchars( $user->getUserPage()->getFullURL() ),
				$rel->user_name
			)->text() . '</div>';

		$output .= '<div class="ga-count">' .
			$this->msg( 'ga-count', $rel->user_name, $total )->parse() .
		'</div>';

		// Safelinks
		$view_system_gift_link = SpecialPage::getTitleFor( 'ViewSystemGift' );

		if ( $gifts ) {
			$x = 1;
			foreach ( $gifts as $gift ) {
				$gift_image = "<img src=\"{$wgUploadPath}/awards/" .
					SystemGifts::getGiftImage( $gift['gift_id'], 'ml' ) .
					'" border="0" alt="" />';

				$output .= "<div class=\"ga-item\">
					{$gift_image}
					<a href=\"" .
						htmlspecialchars( $view_system_gift_link->getFullURL( 'gift_id=' . $gift['id'] ) ) .
						"\">{$gift['gift_name']}</a>";

				if ( $gift['status'] == 1 ) {
					if ( $user_name == $user->getName() ) {
						$rel->clearUserGiftStatus( $gift['id'] );
						$rel->decNewSystemGiftCount( $user->getID() );
					}
					$output .= '<span class="ga-new">' .
						$this->msg( 'ga-new' )->plain() . '</span>';
				}

				$output .= '<div class="cleared"></div>
				</div>';
				if ( $x == count( $gifts ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="cleared"></div>';
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
				$output .= Linker::link(
					$page_link,
					$this->msg( 'ga-previous' )->plain(),
					array(),
					array(
						'user' => $user_name,
						'page' => ( $page - 1 )
					)
				) . $this->msg( 'word-separator' )->plain();
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
					$output .= Linker::link(
						$page_link,
						$i,
						array(),
						array(
							'user' => $user_name,
							'page' => $i
						)
					) . $this->msg( 'word-separator' )->plain();
				}
			}

			if ( ( $total - ( $per_page * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->plain() .
					Linker::link(
						$page_link,
						$this->msg( 'ga-next' )->plain(),
						array(),
						array(
							'user' => $user_name,
							'page' => ( $page + 1 )
						)
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
