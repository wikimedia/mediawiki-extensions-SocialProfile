<?php
/**
 * A special page for removing system gifts permanently.
 *
 * @file
 * @ingroup Extensions
 */
class RemoveMasterSystemGift extends UnlistedSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'RemoveMasterSystemGift' );
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
		$files = glob( $wgUploadDirectory . '/awards/sg_' . $id . "_{$size}*" );
		if ( $files && $files[0] ) {
			$img = basename( $files[0] );
			// $img already contains the sg_ prefix
			unlink( $wgUploadDirectory . '/awards/' .  $img );
		}
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// If the user doesn't have the required 'awardsmanage' permission, display an error
		if ( !$user->isAllowed( 'awardsmanage' ) ) {
			$out->permissionRequired( 'awardsmanage' );
			return;
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$out->readOnlyPage();
			return;
		}

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			$out->blockedPage();
			return;
		}

		// Set the robot policies, etc.
		$out->setArticleRelated( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.systemgifts.css' );

		$this->gift_id = $request->getInt( 'gift_id', $par );

		if ( !$this->gift_id || !is_numeric( $this->gift_id ) ) {
			$out->setPageTitle( $this->msg( 'ga-error-title' )->plain() );
			$out->addHTML( $this->msg( 'ga-error-message-invalid-link' )->plain() );
			return false;
		}

		if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
			$_SESSION['alreadysubmitted'] = true;

			$dbw = wfGetDB( DB_MASTER );
			$gift = SystemGifts::getGift( $this->gift_id );

			$dbw->delete(
				'system_gift',
				array( 'gift_id' => $this->gift_id ),
				__METHOD__
			);
			$dbw->delete(
				'user_system_gift',
				array( 'sg_gift_id' => $this->gift_id ),
				__METHOD__
			);

			$this->deleteImage( $this->gift_id, 's' );
			$this->deleteImage( $this->gift_id, 'm' );
			$this->deleteImage( $this->gift_id, 'l' );
			$this->deleteImage( $this->gift_id, 'ml' );

			$out->setPageTitle( $this->msg( 'ga-remove-success-title', $gift['gift_name'] )->plain() );

			$output = '<div class="back-links">
				<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'SystemGiftManager' )->getFullURL() ) . '">' .
					$this->msg( 'ga-viewlist' )->plain() . '</a>
			</div>
			<div class="ga-container">' .
				$this->msg( 'ga-remove-success-message', $gift['gift_name'] )->plain() .
				'<div class="cleared"></div>
			</div>';

			$out->addHTML( $output );
		} else {
			$_SESSION['alreadysubmitted'] = false;
			$out->addHTML( $this->displayForm() );
		}
	}

	/**
	 * Displays the main form for removing a system gift permanently.
	 *
	 * @return String: HTML output
	 */
	function displayForm() {
		global $wgUploadPath;

		$gift = SystemGifts::getGift( $this->gift_id );

		$giftImage = '<img src="' . $wgUploadPath . '/awards/' .
			SystemGifts::getGiftImage( $this->gift_id, 'l' ) .
			'" border="0" alt="gift" />';

		$this->getOutput()->setPageTitle( $this->msg( 'ga-remove-title', $gift['gift_name'] )->plain() );

		$output = '<div class="back-links">
			<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'SystemGiftManager' )->getFullURL() ) . '">' .
				$this->msg( 'ga-viewlist' )->plain() . '</a>
		</div>
		<form action="" method="post" enctype="multipart/form-data" name="form1">
			<div class="ga-remove-message">' .
				$this->msg( 'ga-delete-message', $gift['gift_name'] ) .
			'</div>
			<div class="ga-container">' .
				$giftImage .
				'<div class="ga-name">' . $gift['gift_name'] . '</div>
			</div>
			<div class="cleared"></div>
			<div class="ga-buttons">
				<input type="button" class="site-button" value="' . $this->msg( 'ga-remove' )->plain() . '" size="20" onclick="document.form1.submit()" />
				<input type="button" class="site-button" value="' . $this->msg( 'ga-cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
			</div>
		</form>';

		return $output;
	}
}
