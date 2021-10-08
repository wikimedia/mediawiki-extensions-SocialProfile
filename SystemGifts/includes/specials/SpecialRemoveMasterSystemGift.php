<?php
/**
 * A special page for removing system gifts permanently.
 *
 * @file
 * @ingroup Extensions
 */
class RemoveMasterSystemGift extends UnlistedSpecialPage {

	/**
	 * @var int Internal ID of the system gift we want to delete
	 */
	public $gift_id;

	public function __construct() {
		parent::__construct( 'RemoveMasterSystemGift', 'awardsmanage' );
	}

	/**
	 * Deletes a gift image from $wgUploadDirectory/awards/
	 *
	 * @param int $id Internal ID number of the gift whose image we want to delete
	 * @param string $size Size of the image to delete,
	 * - s for small
	 * - m for medium
	 * - ml for medium-large
	 * - l for large
	 */
	function deleteImage( $id, $size ) {
		global $wgUploadDirectory;
		$files = glob( $wgUploadDirectory . '/awards/sg_' . $id . "_{$size}*" );
		if ( $files && $files[0] ) {
			$img = basename( $files[0] );
			// $img already contains the sg_ prefix
			unlink( $wgUploadDirectory . '/awards/' . $img );
		}
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 * @return string HTML
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// make sure user has the correct permissions
		$this->checkPermissions();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the robot policies, etc.
		$out->setArticleRelated( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		// Add CSS
		$out->addModuleStyles( [
			'ext.socialprofile.systemgifts.css',
			'ext.socialprofile.special.removemastersystemgift.css'
		] );

		$this->gift_id = $request->getInt( 'gift_id', $par );

		if ( !$this->gift_id || !is_numeric( $this->gift_id ) ) {
			$out->setPageTitle( $this->msg( 'ga-error-title' ) );
			$out->addHTML( $this->msg( 'ga-error-message-invalid-link' )->escaped() );
			return false;
		}

		if (
			$request->wasPosted() &&
			$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) &&
			$_SESSION['alreadysubmitted'] == false
		) {
			$_SESSION['alreadysubmitted'] = true;

			$dbw = wfGetDB( DB_MASTER );
			$gift = SystemGifts::getGift( $this->gift_id );

			$dbw->delete(
				'system_gift',
				[ 'gift_id' => $this->gift_id ],
				__METHOD__
			);
			$dbw->delete(
				'user_system_gift',
				[ 'sg_gift_id' => $this->gift_id ],
				__METHOD__
			);

			$this->deleteImage( $this->gift_id, 's' );
			$this->deleteImage( $this->gift_id, 'm' );
			$this->deleteImage( $this->gift_id, 'l' );
			$this->deleteImage( $this->gift_id, 'ml' );

			$out->setPageTitle( $this->msg( 'ga-remove-success-title', $gift['gift_name'] ) );

			$output = '<div class="back-links">
				<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'SystemGiftManager' )->getFullURL() ) . '">' .
					$this->msg( 'ga-viewlist' )->escaped() . '</a>
			</div>
			<div class="ga-container">' .
				$this->msg( 'ga-remove-success-message', $gift['gift_name'] )->escaped() .
				'<div class="visualClear"></div>
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
	 * @return string HTML
	 */
	function displayForm() {
		$gift = SystemGifts::getGift( $this->gift_id );

		$systemGiftIcon = new SystemGiftIcon( $this->gift_id, 'l' );
		$icon = $systemGiftIcon->getIconHTML();

		$this->getOutput()->setPageTitle( $this->msg( 'ga-remove-title', $gift['gift_name'] ) );

		$output = '<div class="back-links">
			<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'SystemGiftManager' )->getFullURL() ) . '">' .
				$this->msg( 'ga-viewlist' )->escaped() . '</a>
		</div>
		<form action="" method="post" enctype="multipart/form-data" name="form1">
			<div class="ga-remove-message">' .
				$this->msg( 'ga-delete-message', $gift['gift_name'] )->escaped() .
			'</div>
			<div class="ga-container">' .
				$icon .
				'<div class="ga-name">' . htmlspecialchars( $gift['gift_name'], ENT_QUOTES ) . '</div>
			</div>
			<div class="visualClear"></div>
			<div class="ga-buttons">
				<input type="button" class="site-button" value="' . $this->msg( 'ga-remove' )->escaped() . '" size="20" onclick="document.form1.submit()" />
				<input type="button" class="site-button" value="' . $this->msg( 'cancel' )->escaped() . '" size="20" onclick="history.go(-1)" />
			</div>
			<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
		</form>';

		return $output;
	}
}
