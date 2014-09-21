<?php

class RemoveMasterGift extends UnlistedSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'RemoveMasterGift' );
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
	 * @param $id Integer: internal ID number of the gift whose image we want to delete
	 * @param $size String: size of the image to delete (s for small, m for
	 *                      medium, ml for medium-large and l for large)
	 */
	function deleteImage( $id, $size ) {
		global $wgUploadDirectory;
		$files = glob( $wgUploadDirectory . '/awards/' . $id . "_{$size}*" );
		if ( $files && $files[0] ) {
			$img = basename( $files[0] );
			unlink( $wgUploadDirectory . '/awards/' .  $img );
		}
	}

	/**
	 * Checks if a user is allowed to remove gifts.
	 *
	 * @return Boolean: false by default or if the user is blocked, true if
	 *                  user has 'delete' permission or is a member of the
	 *                  giftadmin group
	 */
	function canUserManage() {
		$user = $this->getUser();

		if ( $user->isBlocked() ) {
			return false;
		}

		if ( $user->isAllowed( 'delete' ) || in_array( 'giftadmin', $user->getGroups() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.usergifts.css' );

		// Check for permissions
		if ( $this->getUser()->isAnon() || !$this->canUserManage() ) {
			throw new ErrorPageError( 'error', 'badaccess' );
		}

		$this->gift_id = $request->getInt( 'gift_id' );

		if ( !$this->gift_id || !is_numeric( $this->gift_id ) ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->plain() );
			return false;
		}

		if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
			$_SESSION['alreadysubmitted'] = true;

			$dbw = wfGetDB( DB_MASTER );
			$gift = Gifts::getGift( $this->gift_id );

			$dbw->delete(
				'gift',
				array( 'gift_id' => $this->gift_id ),
				__METHOD__
			);
			$dbw->delete(
				'user_gift',
				array( 'ug_gift_id' => $this->gift_id ),
				__METHOD__
			);

			$this->deleteImage( $this->gift_id, 's' );
			$this->deleteImage( $this->gift_id, 'm' );
			$this->deleteImage( $this->gift_id, 'l' );
			$this->deleteImage( $this->gift_id, 'ml' );

			$out->setPageTitle( $this->msg( 'g-remove-success-title', $gift['gift_name'] )->parse() );

			$output = '<div class="back-links">
				<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'GiftManager' )->getFullURL() ) . '">' .
					$this->msg( 'g-viewgiftlist' )->plain() . '</a>
			</div>
			<div class="g-container">' .
				$this->msg( 'g-remove-success-message', $gift['gift_name'] )->parse() .
				'<div class="cleared"></div>
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
	 * @return String: HTML output
	 */
	function displayForm() {
		global $wgUploadPath;

		$gift = Gifts::getGift( $this->gift_id );

		$gift_image = '<img src="' . $wgUploadPath . '/awards/' .
			Gifts::getGiftImage( $this->gift_id, 'l' ) .
			'" border="0" alt="gift" />';

		$this->getOutput()->setPageTitle( $this->msg( 'g-remove-title', $gift['gift_name'] )->parse() );

		$output = '<div class="back-links">
			<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'GiftManager' )->getFullURL() ) . '">' .
				$this->msg( 'g-viewgiftlist' )->plain() . '</a>
		</div>
		<form action="" method="post" enctype="multipart/form-data" name="form1">
			<div class="g-remove-message">' .
				$this->msg( 'g-delete-message', $gift['gift_name'] )->parse() .
			'</div>
			<div class="g-container">' .
				$gift_image .
				'<div class="g-name">' . $gift['gift_name'] . '</div>
			</div>
			<div class="cleared"></div>
			<div class="g-buttons">
				<input type="button" class="site-button" value="' . $this->msg( 'g-remove' )->plain() . '" size="20" onclick="document.form1.submit()" />
				<input type="button" class="site-button" value="' . $this->msg( 'g-cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
			</div>
		</form>';

		return $output;
	}
}
