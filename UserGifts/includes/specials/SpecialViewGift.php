<?php

class ViewGift extends UnlistedSpecialPage {

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
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( [
			'ext.socialprofile.usergifts.css',
			'ext.socialprofile.special.viewgift.css'
		] );

		$giftId = $this->getRequest()->getInt( 'gift_id' );
		if ( !$giftId || !is_numeric( $giftId ) ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->escaped() );
			return;
		}

		$gift = UserGifts::getUserGift( $giftId );

		if ( $gift ) {
			if ( $gift['status'] == 1 ) {
				if ( $gift['actor_to'] == $user->getActorId() ) {
					$g = new UserGifts( $user );
					$g->clearUserGiftStatus( $gift['id'] );
				}
			}

			// DB stuff
			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				[ 'user_gift', 'actor' ],
				[ 'DISTINCT actor_name', 'ug_actor_to', 'ug_date' ],
				[
					'ug_gift_id' => $gift['gift_id'],
					'ug_actor_to <> ' . $dbr->addQuotes( $gift['actor_to'] )
				],
				__METHOD__,
				[
					'GROUP BY' => 'actor_name',
					'ORDER BY' => 'ug_date DESC',
					'LIMIT' => 6
				],
				// @todo CHECKME!
				[ 'actor' => [ 'JOIN', 'actor_id = ug_actor_to' ] ]
			);

			$giftRecipientUser = User::newFromActorId( $gift['actor_to'] );
			$out->setPageTitle( $this->msg(
				'g-description-title',
				$giftRecipientUser->getName(),
				$gift['name']
			)->parse() );

			$output = '<div class="back-links">
				<a href="' . htmlspecialchars( $giftRecipientUser->getUserPage()->getFullURL() ) . '">'
				. $this->msg( 'g-back-link', $giftRecipientUser->getName() )->parse() . '</a>
			</div>';

			$sender = User::newFromActorId( $gift['actor_from'] );
			$removeGiftLink = SpecialPage::getTitleFor( 'RemoveGift' );
			$giveGiftLink = SpecialPage::getTitleFor( 'GiveGift' );

			$userGiftIcon = new UserGiftIcon( $gift['gift_id'], 'l' );
			$icon = $userGiftIcon->getIconHTML();
			$lang = $this->getLanguage();
			// because "23:25, 18 July 2020" is more readable than "2020-07-18 23:25:37" (thanks legoktm!)
			$humanFriendlyTimestamp = $lang->userTimeAndDate( $gift['timestamp'], $user );

			$output .= '<div class="g-description-container">';
			$output .= '<div class="g-description">' .
					$icon .
					'<div class="g-name">' . htmlspecialchars( $gift['name'] ) . '</div>
					<div class="g-timestamp">(' . htmlspecialchars( $humanFriendlyTimestamp, ENT_QUOTES ) . ')</div>
					<div class="g-from">' . $this->msg(
						'g-from',
						$sender->getName()
					)->parse() . '</div>';
			if ( isset( $gift['message'] ) && $gift['message'] ) {
				$message = $out->parseAsContent( trim( $gift['message'] ), false );
				$output .= '<div class="g-user-message">' . $message . '</div>';
			}
			$output .= '<div class="visualClear"></div>
					<div class="g-describe">' . htmlspecialchars( $gift['description'] ) . '</div>
					<div class="g-actions">
						<a href="' . htmlspecialchars( $giveGiftLink->getFullURL( 'gift_id=' . $gift['gift_id'] ) ) . '">' .
							$this->msg( 'g-to-another' )->escaped() . '</a>';
			if ( $gift['actor_to'] == $user->getActorId() ) {
				$output .= $this->msg( 'pipe-separator' )->escaped();
				$output .= '<a href="' . htmlspecialchars( $removeGiftLink->getFullURL( 'gift_id=' . $gift['id'] ) ) . '">' .
					$this->msg( 'g-remove-gift' )->escaped() . '</a>';
			}
			$output .= '</div>
				</div>';

			$output .= '<div class="g-recent">
					<div class="g-recent-title">' .
						$this->msg( 'g-recent-recipients' )->escaped() .
					'</div>
					<div class="g-gift-count">' .
						$this->msg( 'g-given', $gift['gift_count'] )->parse() .
					'</div>';

			foreach ( $res as $row ) {
				$userTo = User::newFromActorId( $row->ug_actor_to );
				$avatar = new wAvatar( $userTo->getId(), 'ml' );

				$output .= '<a href="' . htmlspecialchars( $userTo->getUserPage()->getFullURL() ) . "\">
					{$avatar->getAvatarURL()}
				</a>";
			}

			$output .= '<div class="visualClear"></div>
				</div>
			</div>';

			$out->addHTML( $output );
		} else {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->escaped() );
		}
	}
}
