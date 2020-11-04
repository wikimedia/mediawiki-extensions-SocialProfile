<?php

class RemoveMasterGift extends UnlistedSpecialPage {

	/**
	 * @var int ID of the gift we are removing
	 */
	public $gift_id;

	public function __construct() {
		parent::__construct( 'RemoveMasterGift', 'giftadmin' );
	}

	/**
	 * Group this special page under the correct header in Special:SpecialPages.
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'wiki';
	}

	/**
	 * Deletes a gift image from $wgUploadDirectory/awards/
	 *
	 * @param int $id Internal ID number of the gift whose image we want to delete
	 * @param string $size size of the image to delete
	 * - s for small
	 * - m for medium
	 * - ml for medium-large
	 * - l for large
	 */
	function deleteImage( $id, $size ) {
		global $wgUploadDirectory;
		$files = glob( $wgUploadDirectory . '/awards/' . $id . "_{$size}*" );
		if ( $files && $files[0] ) {
			$img = basename( $files[0] );
			unlink( $wgUploadDirectory . '/awards/' . $img );
		}
	}

	/**
	 * Checks if a user is allowed to remove gifts.
	 *
	 * @return bool False by default or true if
	 * - has'delete' permission or..
	 * - has the 'giftadmin' permission
	 */
	private function canUserManage() {
		$user = $this->getUser();

		if (
			$user->isAllowed( 'delete' ) ||
			$user->isAllowed( 'giftadmin' )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// user needs to be logged in to access
		$this->requireLogin();

		// Check for permissions
		if ( !$this->canUserManage() ) {
			throw new ErrorPageError( 'error', 'badaccess' );
		}

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.usergifts.css' );

		$this->gift_id = $request->getInt( 'gift_id' );

		if ( !$this->gift_id || !is_numeric( $this->gift_id ) ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( htmlspecialchars( $this->msg( 'g-error-message-invalid-link' )->plain() ) );
			return;
		}

		if (
			$request->wasPosted() &&
			$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) &&
			$_SESSION['alreadysubmitted'] == false
		) {
			$_SESSION['alreadysubmitted'] = true;

			$dbw = wfGetDB( DB_MASTER );
			$gift = Gifts::getGift( $this->gift_id );

			$dbw->delete(
				'gift',
				[ 'gift_id' => $this->gift_id ],
				__METHOD__
			);
			$dbw->delete(
				'user_gift',
				[ 'ug_gift_id' => $this->gift_id ],
				__METHOD__
			);

			$this->deleteImage( $this->gift_id, 's' );
			$this->deleteImage( $this->gift_id, 'm' );
			$this->deleteImage( $this->gift_id, 'l' );
			$this->deleteImage( $this->gift_id, 'ml' );

			$out->setPageTitle( $this->msg( 'g-remove-success-title', $gift['gift_name'] )->parse() );

			$output = '<div class="back-links">
				<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'GiftManager' )->getFullURL() ) . '">' .
					htmlspecialchars( $this->msg( 'g-viewgiftlist' )->plain() ) . '</a>
			</div>
			<div class="g-container">' .
				$this->msg( 'g-remove-success-message', $gift['gift_name'] )->parse() .
				'<div class="visualClear"></div>
			</div>';

			$out->addHTML( $output );
		} else {
			$_SESSION['alreadysubmitted'] = false;
			$out->addHTML( $this->displayForm() );
		}
	}

	/**
	 * Displays the main form for removing a gift permanently
	 *
	 * @return string HTML
	 */
	function displayForm() {
		$gift = Gifts::getGift( $this->gift_id );

		$userGiftIcon = new UserGiftIcon( $this->gift_id, 'l' );
		$icon = $userGiftIcon->getIconHTML();

		$this->getOutput()->setPageTitle( $this->msg( 'g-remove-title', $gift['gift_name'] )->parse() );

		$output = '<div class="back-links">
			<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'GiftManager' )->getFullURL() ) . '">' .
				htmlspecialchars( $this->msg( 'g-viewgiftlist' )->plain() ) . '</a>
		</div>
		<form action="" method="post" enctype="multipart/form-data" name="form1">
			<div class="g-remove-message">' .
				$this->msg( 'g-delete-message', $gift['gift_name'] )->parse() .
			'</div>
			<div class="g-container">' .
				$icon .
				'<div class="g-name">' . htmlspecialchars( $gift['gift_name'] ) . '</div>
			</div>
			<div class="visualClear"></div>
			<div class="g-buttons">
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'g-remove' )->plain() ) . '" size="20" onclick="document.form1.submit()" />
				<input type="button" class="site-button" value="' . htmlspecialchars( $this->msg( 'cancel' )->plain() ) . '" size="20" onclick="history.go(-1)" />
			</div>
			<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
		</form>';

		return $output;
	}
}
