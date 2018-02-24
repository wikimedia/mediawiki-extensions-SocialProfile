<?php

/**
 * Formatter for user relationship (friend/foe) notifications ('social-rel-*')
 */
class EchoUserRelationshipPresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		$eventType = $this->event->getType();
		$relType = $this->event->getExtraParam( 'rel_type' );

		if ( $relType == 1 ) { // friend added/pending friend request
			return 'gratitude';
		} elseif ( $eventType == 'social-rel-accept' && $relType != 1 ) { // foe added
			return 'social-added';
		} else { // pending friend or foe request
			// @todo FIXME: better icon
			return 'placeholder';
		}
	}

	public function getHeaderMessage() {
		$eventType = $this->event->getType();
		$message = $this->event->getExtraParam( 'message' );
		$relType = $this->event->getExtraParam( 'rel_type' );

		if ( $eventType == 'social-rel-add' ) { // pending request
			if ( $relType == 1 && $message ) {
				$msg = 'notification-social-rel-add-friend-message';
			} elseif ( $relType == 1 ) {
				$msg = 'notification-social-rel-add-friend-no-message';
			} elseif ( $relType !== 1 && $message ) {
				$msg = 'notification-social-rel-add-foe-message';
			} else {
				$msg = 'notification-social-rel-add-foe-no-message';
			}
			return $this->msg(
				$msg,
				$this->event->getAgent()->getName(),
				$message
			);
		} elseif ( $eventType == 'social-rel-accept' ) { // accepted request
			$msg = ( $relType == 1 ? 'notification-social-rel-accept-friend' : 'notification-social-rel-accept-foe' );
			return $this->msg(
				$msg,
				$this->event->getAgent()->getName()
			);
		}
	}

	public function getBodyMessage() {
		return false;
	}

	public function getPrimaryLink() {
		$eventType = $this->event->getType();
		$relType = $this->event->getExtraParam( 'rel_type' );
		if ( $eventType == 'social-rel-add' ) { // pending request
			$url = SpecialPage::getTitleFor( 'ViewRelationshipRequests' )->getLocalURL();
		} elseif ( $eventType == 'social-rel-accept' ) { // accepted request
			$url = SpecialPage::getTitleFor( 'ViewRelationships' )->getLocalURL();
		}
		return array(
			'url' => $url,
			'label' => $this->msg( 'echo-learn-more' )->text()
		);
	}

	public function getSecondaryLinks() {
		// Apparently these two can't be the other way around 'cause it'll look
		// stupid. Who knew?
		return array( $this->getAgentLink(), $this->getSpecialPageLink() );
	}

	private function getSpecialPageLink() {
		$eventType = $this->event->getType();
		$relType = $this->event->getExtraParam( 'rel_type' );
		if ( $eventType == 'social-rel-add' ) { // pending request
			$label = $this->msg( 'viewrelationshiprequests' )->text();
			$url = SpecialPage::getTitleFor( 'ViewRelationshipRequests' )->getLocalURL();
		} elseif ( $eventType == 'social-rel-accept' ) { // accepted request
			$label = $this->msg( ( $relType == 1 ) ? 'ur-title-friend' : 'ur-title-foe' )->params( $this->getViewingUserForGender() )->text();
			$url = SpecialPage::getTitleFor( 'ViewRelationships' )->getLocalURL();
		}
		return array(
			'label' => $label,
			'url' => $url,
			'description' => '',
			'icon' => false,
			'prioritized' => true,
		);
	}

}