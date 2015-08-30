<?php
/**
 * Special:ViewGifts -- a special page for viewing the list of user-to-user
 * gifts a given user has received
 *
 * @file
 * @ingroup Extensions
 */

class ViewGifts extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ViewGifts' );
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
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgUploadPath;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$currentUser = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.usergifts.css' );

		$user_name = $request->getVal( 'user' );
		$page = $request->getInt( 'page', 1 );

		/**
		 * Redirect Non-logged in users to Login Page
		 * It will automatically return them to the ViewGifts page
		 */
		if ( $currentUser->getID() == 0 && $user_name == '' ) {
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			$out->redirect( htmlspecialchars( $login->getFullURL( 'returnto=Special:ViewGifts' ) ) );
			return false;
		}

		/**
		 * If no user is set in the URL, we assume it's the current user
		 */
		if ( !$user_name ) {
			$user_name = $currentUser->getName();
		}
		$user_id = User::idFromName( $user_name );
		$user = Title::makeTitle( NS_USER, $user_name );

		/**
		 * Error message for username that does not exist (from URL)
		 */
		if ( $user_id == 0 ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-no-user' )->plain() );
			return false;
		}

		/**
		 * Config for the page
		 */
		$per_page = 10;
		$per_row = 2;

		/**
		 * Get all gifts for this user into the array
		 */
		$rel = new UserGifts( $user_name );

		$gifts = $rel->getUserGiftList( 0, $per_page, $page );
		$total = $rel->getGiftCountByUsername( $user_name );

		/**
		 * Show gift count for user
		 */
		$out->setPageTitle( $this->msg( 'g-list-title', $rel->user_name )->parse() );

		$output = '<div class="back-links">
			<a href="' . $user->getFullURL() . '">' .
				$this->msg( 'g-back-link', $rel->user_name )->parse() .
			'</a>
		</div>
		<div class="g-count">' .
			$this->msg( 'g-count', $rel->user_name, $total )->parse() .
		'</div>';

		if ( $gifts ) {
			$x = 1;

			// Safe links
			$viewGiftLink = SpecialPage::getTitleFor( 'ViewGift' );
			$giveGiftLink = SpecialPage::getTitleFor( 'GiveGift' );
			$removeGiftLink = SpecialPage::getTitleFor( 'RemoveGift' );

			foreach ( $gifts as $gift ) {
				$giftname_length = strlen( $gift['gift_name'] );
				$giftname_space = stripos( $gift['gift_name'], ' ' );

				if ( ( $giftname_space == false || $giftname_space >= "30" ) && $giftname_length > 30 ) {
					$gift_name_display = substr( $gift['gift_name'], 0, 30 ) .
						' ' . substr( $gift['gift_name'], 30, 50 );
				} else {
					$gift_name_display = $gift['gift_name'];
				}

				$user_from = Title::makeTitle( NS_USER, $gift['user_name_from'] );
				$gift_image = "<img src=\"{$wgUploadPath}/awards/" .
					Gifts::getGiftImage( $gift['gift_id'], 'l' ) .
					'" border="0" alt="" />';

				$output .= '<div class="g-item">
					<a href="' . htmlspecialchars( $viewGiftLink->getFullURL( 'gift_id=' . $gift['id'] ) ) . '">' .
						$gift_image .
					'</a>
					<div class="g-title">
						<a href="' . htmlspecialchars( $viewGiftLink->getFullURL( 'gift_id=' . $gift['id'] ) ) . '">' .
							$gift_name_display .
						'</a>';
				if ( $gift['status'] == 1 ) {
					if ( $user_name == $currentUser->getName() ) {
						$rel->clearUserGiftStatus( $gift['id'] );
						$rel->decNewGiftCount( $currentUser->getID() );
					}
					$output .= '<span class="g-new">' .
						$this->msg( 'g-new' )->plain() .
					'</span>';
				}
				$output .= '</div>';

				$output .= '<div class="g-from">' .
					$this->msg( 'g-from', htmlspecialchars( $user_from->getFullURL() ), $gift['user_name_from'] )->text() .
				'</div>
					<div class="g-actions">
						<a href="' . htmlspecialchars( $giveGiftLink->getFullURL( 'gift_id=' . $gift['gift_id'] ) ) . '">' .
							$this->msg( 'g-to-another' )->plain() .
						'</a>';
				if ( $rel->user_name == $currentUser->getName() ) {
					$output .= '&#160;';
					$output .= $this->msg( 'pipe-separator' )->escaped();
					$output .= '&#160;';
					$output .= '<a href="' . htmlspecialchars( $removeGiftLink->getFullURL( 'gift_id=' . $gift['id'] ) ) . '">' .
						$this->msg( 'g-remove-gift' )->plain() . '</a>';
				}
				$output .= '</div>
					<div class="visualClear"></div>';
				$output .= '</div>';
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

		$pageLink = $this->getPageTitle();

		if ( $numofpages > 1 ) {
			$output .= '<div class="page-nav">';
			if ( $page > 1 ) {
				$output .= Linker::link(
					$pageLink,
					$this->msg( 'g-previous' )->plain(),
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
						$pageLink,
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
						$pageLink,
						$this->msg( 'g-next' )->plain(),
						array(),
						array(
							'user' => $user_name,
							'page' => ( $page + 1 )
						)
					);
			}
			$output .= '</div>';
		}

		$out->addHTML( $output );
	}
}
