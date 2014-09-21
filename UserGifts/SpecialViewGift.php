<?php

class ViewGift extends UnlistedSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'ViewGift' );
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
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.usergifts.css' );

		$giftId = $this->getRequest()->getInt( 'gift_id' );
		if ( !$giftId || !is_numeric( $giftId ) ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->plain() );
			return false;
		}

		$gift = UserGifts::getUserGift( $giftId );

		if ( $gift ) {
			if ( $gift['status'] == 1 ) {
				if ( $gift['user_name_to'] == $user->getName() ) {
					$g = new UserGifts( $gift['user_name_to'] );
					$g->clearUserGiftStatus( $gift['id'] );
					$g->decNewGiftCount( $user->getID() );
				}
			}

			// DB stuff
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'user_gift',
				array( 'DISTINCT ug_user_name_to', 'ug_user_id_to', 'ug_date' ),
				array(
					'ug_gift_id' => $gift['gift_id'],
					'ug_user_name_to <> ' . $dbr->addQuotes( $gift['user_name_to'] )
				),
				__METHOD__,
				array(
					'GROUP BY' => 'ug_user_name_to',
					'ORDER BY' => 'ug_date DESC',
					'LIMIT' => 6
				)
			);

			$out->setPageTitle( $this->msg(
				'g-description-title',
				$gift['user_name_to'],
				$gift['name']
			)->parse() );

			$output = '<div class="back-links">
				<a href="' . htmlspecialchars( Title::makeTitle( NS_USER, $gift['user_name_to'] )->getFullURL() ) . '">'
				. $this->msg( 'g-back-link', $gift['user_name_to'] )->parse() . '</a>
			</div>';

			$sender = Title::makeTitle( NS_USER, $gift['user_name_from'] );
			$removeGiftLink = SpecialPage::getTitleFor( 'RemoveGift' );
			$giveGiftLink = SpecialPage::getTitleFor( 'GiveGift' );

			$giftImage = '<img src="' . $wgUploadPath . '/awards/' .
				Gifts::getGiftImage( $gift['gift_id'], 'l' ) .
				'" border="0" alt="" />';

			$message = $out->parse( trim( $gift['message'] ), false );

			$output .= '<div class="g-description-container">';
			$output .= '<div class="g-description">' .
					$giftImage .
					'<div class="g-name">' . $gift['name'] . '</div>
					<div class="g-timestamp">(' . $gift['timestamp'] . ')</div>
					<div class="g-from">' . $this->msg(
						'g-from',
						htmlspecialchars( $sender->getFullURL() ),
						$gift['user_name_from']
					)->text() . '</div>';
			if ( $message ) {
				$output .= '<div class="g-user-message">' . $message . '</div>';
			}
			$output .= '<div class="cleared"></div>
					<div class="g-describe">' . $gift['description'] . '</div>
					<div class="g-actions">
						<a href="' . htmlspecialchars( $giveGiftLink->getFullURL( 'gift_id=' . $gift['gift_id'] ) ) . '">' .
							$this->msg( 'g-to-another' )->plain() . '</a>';
			if ( $gift['user_name_to'] == $user->getName() ) {
				$output .= $this->msg( 'pipe-separator' )->escaped();
				$output .= '<a href="' . htmlspecialchars( $removeGiftLink->getFullURL( 'gift_id=' . $gift['id'] ) ) . '">' .
					$this->msg( 'g-remove-gift' )->plain() . '</a>';
			}
			$output .= '</div>
				</div>';

			$output .= '<div class="g-recent">
					<div class="g-recent-title">' .
						$this->msg( 'g-recent-recipients' )->plain() .
					'</div>
					<div class="g-gift-count">' .
						$this->msg( 'g-given', $gift['gift_count'] )->parse() .
					'</div>';

			foreach ( $res as $row ) {
				$userToId = $row->ug_user_id_to;
				$avatar = new wAvatar( $userToId, 'ml' );
				$userNameLink = Title::makeTitle( NS_USER, $row->ug_user_name_to );

				$output .= '<a href="' . htmlspecialchars( $userNameLink->getFullURL() ) . "\">
					{$avatar->getAvatarURL()}
				</a>";
			}

			$output .= '<div class="cleared"></div>
				</div>
			</div>';

			$out->addHTML( $output );
		} else {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->plain() );
		}
	}
}
