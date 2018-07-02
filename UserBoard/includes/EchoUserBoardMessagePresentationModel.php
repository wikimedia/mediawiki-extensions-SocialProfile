<?php

/**
 * Formatter for user board message notifications ('social-msg-send')
 */
class EchoUserBoardMessagePresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		return 'emailuser'; // per discussion with Cody on 27 March 2016
	}

	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			return $this->msg(
				'notification-social-msg-send-bundle',
				$this->getBundleCount()
			);
		} else {
			return $this->msg(
				'notification-social-msg-send',
				$this->event->getAgent()->getName(),
				$this->language->truncateForVisual( $this->event->getExtraParam( 'message' ), 100 )
			);
		}
	}

	public function getBodyMessage() {
		return false;
	}

	public function getPrimaryLink() {
		return [
			'url' => SpecialPage::getTitleFor( 'UserBoard' )->getLocalURL(),
			'label' => $this->msg( 'echo-learn-more' )->text()
		];
	}

}
