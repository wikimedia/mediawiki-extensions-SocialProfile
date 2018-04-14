<?php

/**
 * Formatter for award notifications ('social-award')
 */
class EchoUserSystemGiftPresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		return 'social-award';
	}

	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			return $this->msg( 'notification-social-award-rec-bundle', $this->getBundleCount() );
		} else {
			return $this->msg( 'notification-social-award-rec', $this->event->getExtraParam( 'giftname' ) );
		}
	}

	public function getBodyMessage() {
		return false;
	}

	public function getPrimaryLink() {
		return [
			'url' => SpecialPage::getTitleFor( 'ViewSystemGift' )->getLocalURL( [ 'gift_id' => $this->event->getExtraParam( 'giftid' ) ] ),
			'label' => $this->msg( 'echo-learn-more' )->text()
		];
	}

}
