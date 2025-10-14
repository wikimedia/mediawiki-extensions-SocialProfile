<?php

use MediaWiki\MediaWikiServices;

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
	 * Deletes a gift image from mwstore://<file_backend>
	 *
	 * @param int $id Internal ID number of the gift whose image we want to delete
	 * @param string $size Size of the image to delete,
	 * - s for small
	 * - m for medium
	 * - ml for medium-large
	 * - l for large
	 */
	function deleteImage( $id, $size ) {
		$backend = new SocialProfileFileBackend( 'awards' );

		$extensions = [ 'png', 'gif', 'jpg', 'jpeg' ];
		foreach ( $extensions as $ext ) {
			if ( $backend->fileExists( 'sg_', $id, $size, '.' . $ext ) ) {
				$backend->getFileBackend()->quickDelete( [
					'src' => $backend->getPath( 'sg_', $id, $size, $ext )
				] );
			}
		}
	}

	/**
	 * Show the special page
	 *
	 * @param int|null $par Gift ID, if any; needed to be able to use this special page properly
	 * @return void
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$session = $request->getSession();
		$user = $this->getUser();

		// make sure user has the correct permissions
		$this->checkPermissions();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		$block = $user->getBlock();
		if ( $block ) {
			throw new UserBlockedError( $block );
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

		if ( !$this->gift_id ) {
			$out->setPageTitle( $this->msg( 'ga-error-title' )->escaped() );
			$out->addHTML( $this->msg( 'ga-error-message-invalid-link' )->escaped() );
			return;
		}

		if (
			$request->wasPosted() &&
			$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) &&
			$session->get( 'alreadysubmitted' ) == false
		) {
			$session->set( 'alreadysubmitted', true );

			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
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

			$out->setPageTitle( $this->msg( 'ga-remove-success-title', $gift['gift_name'] )->parse() );

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
			$session->set( 'alreadysubmitted', false );
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

		$this->getOutput()->setPageTitle( $this->msg( 'ga-remove-title', $gift['gift_name'] )->parse() );

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
				<input type="submit" class="site-button" value="' . $this->msg( 'ga-remove' )->escaped() . '" size="20" />
				<input type="button" class="site-button" value="' . $this->msg( 'cancel' )->escaped() . '" size="20" onclick="history.go(-1)" />
			</div>
			<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
		</form>';

		return $output;
	}
}
