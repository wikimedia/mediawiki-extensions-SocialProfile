<?php

/**
 * Formatter for user gift notifications ('social-gift-send')
 */
class EchoUserGiftPresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		return 'social-gift-send';
	}

	public function getHeaderMessage() {
		$g = Gifts::getGift( $this->event->getExtraParam( 'mastergiftid' ) );
		$giftName = '';
		if ( isset( $g['gift_name'] ) ) {
			// It damn well *should* be set, but Gifts::getGift() can theoretically
			// return an empty array
			$giftName = $g['gift_name'];
		}
		if ( $this->isBundled() ) {
			return $this->msg(
				'notification-social-gift-send-bundle',
				$this->getBundleCount()
			);
		} else {
			if ( !empty( $this->event->getExtraParam( 'message' ) ) ) {
				return $this->msg(
					'notification-social-gift-send-with-message',
					$this->event->getAgent()->getName(),
					$giftName,
					$this->event->getExtraParam( 'message' )
				);
			} else {
				return $this->msg(
					'notification-social-gift-send-no-message',
					$this->event->getAgent()->getName(),
					$giftName
				);
			}
		}
	}

	public function getBodyMessage() {
		return false;
	}

	public function getPrimaryLink() {
		return [
			'url' => $this->getGiftLink(),
			'label' => $this->msg( 'echo-learn-more' )->text()
		];
	}

	public function getSecondaryLinks() {
		$g = Gifts::getGift( $this->event->getExtraParam( 'mastergiftid' ) );
		$label = '';
		if ( isset( $g['gift_name'] ) ) {
			// It damn well *should* be set, but Gifts::getGift() can theoretically
			// return an empty array
			$label = $g['gift_name'];
		}
		return [
			$this->getMyProfileLink(),
			[
				'url' => $this->getGiftLink(),
				'label' => $label
			]
		];
	}

	private function getMyProfileLink() {
		return [
			'label' => $this->msg( 'g-your-profile' )->text(),
			'url' => Title::makeTitle( NS_USER, $this->getViewingUserForGender() )->getFullURL(),
			'description' => '',
			'icon' => 'userAvatar',
			'prioritized' => true,
		];
	}

	private function getGiftLink() {
		return SpecialPage::getTitleFor( 'ViewGift' )->getLocalURL( [
			'gift_id' => $this->event->getExtraParam( 'giftid' )
		] );
	}

}
