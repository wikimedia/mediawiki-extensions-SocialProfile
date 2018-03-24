<?php

class RemoveGift extends UnlistedSpecialPage {

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
	 * @param string|null $par
	 */
	public function execute( $par ) {
		global $wgMemc;

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
		if ( $rel->doesUserOwnGift( $user->getId(), $this->gift_id ) == false ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-do-not-own' )->plain() );
			return false;
		}

		$gift = $rel->getUserGift( $this->gift_id );
		if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
			$_SESSION['alreadysubmitted'] = true;

			$user_page_link = Title::makeTitle( NS_USER, $user->getName() );

			if ( $rel->doesUserOwnGift( $user->getId(), $this->gift_id ) == true ) {
				$wgMemc->delete( $wgMemc->makeKey( 'user', 'profile', 'gifts', $user->getId() ) );
				$rel->deleteGift( $this->gift_id );
			}

			$userGiftIcon = new UserGiftIcon( $gift['gift_id'], 'l' );
			$icon = $userGiftIcon->getIconHTML();

			$out->setPageTitle( $this->msg( 'g-remove-success-title', $gift['name'] )->parse() );

			$html = '<div class="back-links">
				<a href="' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '">' .
					$this->msg( 'g-back-link', $gift['user_name_to'] )->parse() . '</a>
			</div>
			<div class="g-container">' .
				$gift_image . $this->msg( 'g-remove-success-message', $gift['name'] )->parse() .
				'<div class="visualClear"></div>
			</div>
			<div class="g-buttons">
				<input type="button" class="site-button" value="' . $this->msg( 'mainpage' )->plain() . '" size="20" onclick="window.location=\'index.php?title=' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '\'" />
				<input type="button" class="site-button" value="' . $this->msg( 'g-your-profile' )->plain() . '" size="20" onclick="window.location=\'' . htmlspecialchars( $user_page_link->getFullURL() ) . '\'" />
			</div>';

			$out->addHTML( $html );
		} else {
			$_SESSION['alreadysubmitted'] = false;
			$out->addHTML( $this->displayForm() );
		}
	}

	/**
	 * Displays the main form for removing a gift
	 *
	 * @return string HTML
	 */
	function displayForm() {
		global $wgUploadPath;

		$currentUser = $this->getUser();
		$rel = new UserGifts( $currentUser->getName() );
		$gift = $rel->getUserGift( $this->gift_id );
		$user = Title::makeTitle( NS_USER, $gift['user_name_from'] );
		$userGiftIcon = new UserGiftIcon( $gift['gift_id'], 'l' );
		$icon = $userGiftIcon->getIconHTML();

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
				$icon .
				'<div class="g-name">' . $gift['name'] . '</div>
				<div class="g-from">' .
					$this->msg(
						'g-from',
						htmlspecialchars( $user->getFullURL() ),
						$gift['user_name_from']
					)->text() . '</div>';
		if ( $gift['message'] ) {
			$output .= '<div class="g-user-message">' .
				$gift['message'] . '</div>';
		}
		$output .= '</div>
			<div class="visualClear"></div>
			<div class="g-buttons">' .
				Html::hidden( 'user', $gift['user_name_from'] ) .
				'<input type="button" class="site-button" value="' . $this->msg( 'g-remove' )->plain() . '" size="20" onclick="document.form1.submit()" />
				<input type="button" class="site-button" value="' . $this->msg( 'cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
			</div>
		</form>';

		return $output;
	}
}
