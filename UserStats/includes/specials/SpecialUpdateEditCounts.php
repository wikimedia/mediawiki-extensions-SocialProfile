<?php

use MediaWiki\Html\Html;

/**
 * A special page for updating users' point counts.
 *
 * @file
 * @ingroup Extensions
 */

class UpdateEditCounts extends UnlistedSpecialPage {

	// MW 1.46+ compatibility, remove comparison with 1.47 LTS release
	public function __construct() {
		if ( version_compare( MW_VERSION, '1.46', '>=' ) ) {
			parent::__construct( 'UpdateEditCounts' );
		} else {
			parent::__construct( 'UpdateEditCounts', 'updatepoints' );
		}
	}

	public function getRestriction(): string {
		return 'updatepoints';
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();

		// Check permissions -- we must be allowed to access this special page
		// before we can run any database queries
		$this->checkPermissions();

		// And obviously the database needs to be writable before we start
		// running INSERT/UPDATE queries against it...
		$this->checkReadOnly();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		if ( $request->wasPosted() && $this->getUser()->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			$updater = new UserStatsUpdater();
			$updater->updateMainEditsCount( [ $this, 'reportProgress' ] );
			$count = $updater->updateTotalPoints();
			$out->addWikiMsg( 'updateeditcounts-updated', $count );
		} else {
			$out->addHTML( $this->displayForm() );
		}
	}

	/**
	 * Prints each user that gets their edit count updated
	 *
	 * @param string $userName User name
	 * @param int $editCount Updated edit count
	 */
	public function reportProgress( $userName, $editCount ) {
		$out = $this->getOutput();
		$out->addWikiMsg( 'updateeditcounts-updating', $userName, $editCount );
	}

	/**
	 * Render the confirmation form
	 *
	 * @return string HTML
	 */
	private function displayForm() {
		$form = '<form method="post" name="update-edit-counts" action="">';
		$form .= $this->msg( 'updateeditcounts-confirm' )->escaped();
		$form .= '<br />';
		$form .= Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() );
		$form .= Html::submitButton( $this->msg( 'htmlform-submit' )->text(), [ 'name' => 'wpSubmit' ] );
		$form .= '</form>';
		return $form;
	}

}
