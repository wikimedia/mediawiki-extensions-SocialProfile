<?php

/**
 * Formatter for user's level up notifications ('social-level-up')
 */
class EchoUserLevelAdvancePresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		return 'social-level-up';
	}

	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			return $this->msg(
				'notification-social-level-up-bundle',
				$this->getBundleCount(),
				$this->event->getExtraParam( 'new-level' )
			);
		} else {
			return $this->msg( 'notification-social-level-up', $this->event->getExtraParam( 'new-level' ) );
		}
	}

	public function getBodyMessage() {
		return false;
	}

	public function getPrimaryLink() {
		return [
			'url' => Title::makeTitle( NS_USER, $this->event->getAgent()->getName() )->getLocalURL(),
			'label' => $this->msg( 'echo-learn-more' )->text()
		];
	}

}
