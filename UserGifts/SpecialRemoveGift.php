<?php

class RemoveGift extends UnlistedSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'RemoveGift' );
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
		global $wgMemc, $wgUploadPath;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.usergifts.css' );

		$this->gift_id = $request->getInt( 'gift_id' );
		$rel = new UserGifts( $user->getName() );

		// Make sure that we have a gift ID, can't do anything without that
		if ( !$this->gift_id || !is_numeric( $this->gift_id ) ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->plain() );
			return false;
		}

		// And also ensure that we're not trying to delete *someone else's* gift(s)...
		if ( $rel->doesUserOwnGift( $user->getID(), $this->gift_id ) == false ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-do-not-own' )->plain() );
			return false;
		}

		$gift = $rel->getUserGift( $this->gift_id );
		if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
			$_SESSION['alreadysubmitted'] = true;

			$user_page_link = Title::makeTitle( NS_USER, $user->getName() );

			if ( $rel->doesUserOwnGift( $user->getID(), $this->gift_id ) == true ) {
				$wgMemc->delete( wfMemcKey( 'user', 'profile', 'gifts', $user->getID() ) );
				$rel->deleteGift( $this->gift_id );
			}

			$gift_image = '<img src="' . $wgUploadPath . '/awards/' .
				Gifts::getGiftImage( $gift['gift_id'], 'l' ) .
				'" border="0" alt="" />';

			$out->setPageTitle( $this->msg( 'g-remove-success-title', $gift['name'] )->parse() );

			$out = '<div class="back-links">
				<a href="' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '">' .
					$this->msg( 'g-back-link', $gift['user_name_to'] )->parse() . '</a>
			</div>
			<div class="g-container">' .
				$gift_image . $this->msg( 'g-remove-success-message', $gift['name'] )->parse() .
				'<div class="cleared"></div>
			</div>
			<div class="g-buttons">
				<input type="button" class="site-button" value="' . $this->msg( 'g-main-page' )->plain() . '" size="20" onclick="window.location=\'index.php?title=' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '\'" />
				<input type="button" class="site-button" value="' . $this->msg( 'g-your-profile' )->plain() . '" size="20" onclick="window.location=\'' . htmlspecialchars( $user_page_link->getFullURL() ) . '\'" />
			</div>';

			$out->addHTML( $out );
		} else {
			$_SESSION['alreadysubmitted'] = false;
			$out->addHTML( $this->displayForm() );
		}
	}

	/**
	 * Displays the main form for removing a gift
	 * @return HTML output
	 */
	function displayForm() {
		global $wgUploadPath;

		$currentUser = $this->getUser();
		$rel = new UserGifts( $currentUser->getName() );
		$gift = $rel->getUserGift( $this->gift_id );
		$user = Title::makeTitle( NS_USER, $gift['user_name_from'] );
		$gift_image = '<img src="' . $wgUploadPath . '/awards/' .
			Gifts::getGiftImage( $gift['gift_id'], 'l' ) .
			'" border="0" alt="gift" />';

		$this->getOutput()->setPageTitle( $this->msg( 'g-remove-title', $gift['name'] )->parse() );

		$output = '<div class="back-links">
			<a href="' . htmlspecialchars( $currentUser->getUserPage()->getFullURL() ) . '">' .
				$this->msg( 'g-back-link', $gift['user_name_to'] )->parse() . '</a>
		</div>
		<form action="" method="post" enctype="multipart/form-data" name="form1">
			<div class="g-remove-message">' .
				$this->msg( 'g-remove-message', $gift['name'] )->parse() .
			'</div>
			<div class="g-container">' .
				$gift_image .
				'<div class="g-name">' . $gift['name'] . '</div>
				<div class="g-from">' .
					$this->msg(
						'g-from',
						htmlspecialchars( $user->getFullURL() ),
						$gift['user_name_from']
					)->parse() . '</div>';
		if ( $gift['message'] ) {
			$output .= '<div class="g-user-message">' .
				$gift['message'] . '</div>';
		}
		$output .= '</div>
			<div class="cleared"></div>
			<div class="g-buttons">' .
				Html::hidden( 'user', $gift['user_name_from'] ) .
				'<input type="button" class="site-button" value="' . $this->msg( 'g-remove' )->plain() . '" size="20" onclick="document.form1.submit()" />
				<input type="button" class="site-button" value="' . $this->msg( 'g-cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
			</div>
		</form>';

		return $output;
	}
}
