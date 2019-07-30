<?php
/**
 * A special page to view an individual system gift (award).
 *
 * @file
 * @ingroup Extensions
 */

class ViewSystemGift extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'ViewSystemGift' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( [
			'ext.socialprofile.systemgifts.css',
			'ext.socialprofile.special.viewsystemgift.css'
		] );

		$output = ''; // Prevent E_NOTICE

		// If gift ID wasn't passed in the URL parameters or if it's not
		// numeric, display an error message
		$giftId = $this->getRequest()->getInt( 'gift_id' );
		if ( !$giftId || !is_numeric( $giftId ) ) {
			$out->setPageTitle( $this->msg( 'ga-error-title' )->plain() );
			$out->addHTML( $this->msg( 'ga-error-message-invalid-link' )->plain() );
			return false;
		}

		$gift = UserSystemGifts::getUserGift( $giftId );

		if ( $gift ) {
			if ( $gift['status'] == 1 ) {
				if ( $gift['user_name'] == $user->getName() ) {
					$g = new UserSystemGifts( $gift['user_name'] );
					$g->clearUserGiftStatus( $gift['id'] );
				}
			}
			// DB stuff
			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				'user_system_gift',
				[
					'DISTINCT sg_user_name', 'sg_user_id', 'sg_gift_id',
					'sg_date'
				],
				[
					"sg_gift_id = {$gift['gift_id']}",
					'sg_user_name <> ' . $dbr->addQuotes( $gift['user_name'] )
				],
				__METHOD__,
				[
					'GROUP BY' => 'sg_user_name',
					'ORDER BY' => 'sg_date DESC',
					'OFFSET' => 0,
					'LIMIT' => 6
				]
			);

			$out->setPageTitle( $this->msg( 'ga-gift-title', $gift['user_name'], $gift['name'] )->parse() );

			$profileURL = htmlspecialchars( Title::makeTitle( NS_USER, $gift['user_name'] )->getFullURL() );
			$output .= '<div class="back-links">' .
				$this->msg( 'ga-back-link', $profileURL, $gift['user_name'] )->text() .
			'</div>';

			$message = str_replace( [ '<p>', '</p>' ], '', $out->parseAsContent( trim( $gift['description'] ), false ) );
			$output .= '<div class="ga-description-container">';

			$systemGiftIcon = new SystemGiftIcon( $gift['gift_id'], 'l' );
			$icon = $systemGiftIcon->getIconHTML();

			$output .= "<div class=\"ga-description\">
					{$icon}
					<div class=\"ga-name\">{$gift['name']}</div>
					<div class=\"ga-timestamp\">({$gift['timestamp']})</div>
					<div class=\"ga-description-message\">\"{$message}\"</div>";
			$output .= '<div class="visualClear"></div>
				</div>';

			// If someone else in addition to the current user has gotten this
			// award, then and only then show the "Other recipients of this
			// award" header and the list of avatars
			if ( $gift['gift_count'] > 1 ) {
				$output .= '<div class="ga-recent">
					<div class="ga-recent-title">' .
						$this->msg( 'ga-recent-recipients-award' )->plain() .
					'</div>
					<div class="ga-gift-count">' .
						$this->msg(
							'ga-gift-given-count'
						)->numParams(
							$gift['gift_count']
						)->parse() .
					'</div>';

				foreach ( $res as $row ) {
					$userToId = $row->sg_user_id;
					$avatar = new wAvatar( $userToId, 'ml' );
					$userNameLink = Title::makeTitle( NS_USER, $row->sg_user_name );

					$output .= '<a href="' . htmlspecialchars( $userNameLink->getFullURL() ) . "\">
					{$avatar->getAvatarURL()}
				</a>";
				}

				$output .= '<div class="visualClear"></div>
				</div>'; // .ga-recent
			}

			$output .= '</div>';

			$out->addHTML( $output );
		} else {
			$out->setPageTitle( $this->msg( 'ga-error-title' )->plain() );
			$out->addHTML( $this->msg( 'ga-error-message-invalid-link' )->plain() );
		}
	}
}
