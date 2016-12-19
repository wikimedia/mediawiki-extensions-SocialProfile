<?php

class UserRelationshipHooks {
	/**
	 * For the Echo extension.
	 *
	 * @param array $notifications Echo notifications
	 * @param array $notificationCategories Echo notification categories
	 * @param array $icons Icon details
	 * @return bool
	 */
	public static function onBeforeCreateEchoEvent( &$notifications, &$notificationCategories, &$icons ) {
		$notificationCategories['social-rel'] = array(
			'priority' => 2,
			'tooltip' => 'echo-pref-tooltip-social-rel',
		);

		$notifications['social-rel-add'] = array(
			'primary-link' => array( 'message' => 'notification-link-text-view-edit', 'destination' => 'diff' ),
			'category' => 'social-rel',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserRelationshipPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => array(
				'EchoUserLocator::locateEventAgent'
			),
			'payload' => array( 'relationship-add' ),
			'email-subject-message' => 'notification-social-rel-add-email-subject',
			'email-subject-params' => array( 'user' ),
			'email-body-batch-message' => 'notification-social-rel-add-email-batch-body',
			'email-body-batch-params' => array( 'user', 'relationship' ),
			'icon' => 'gratitude',
		);

		$notifications['social-rel-accept'] = array(
			'primary-link' => array( 'message' => 'notification-link-text-view-edit', 'destination' => 'diff' ),
			'category' => 'social-rel',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserRelationshipPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => array(
				'EchoUserLocator::locateEventAgent'
			),
			'email-subject-message' => 'notification-social-rel-accept-email-subject',
			'email-subject-params' => array( 'user' ),
			'email-body-batch-message' => 'notification-social-rel-accept-email-batch-body',
			'email-body-batch-params' => array( 'user' ),
			'icon' => 'gratitude',
		);

		$icons['social-added'] = array(
			'path' => 'SocialProfile/images/notifications-added.svg'
		);
		$icons['gratitude'] = array(
			'path' => 'SocialProfile/images/gratitude.png'
		);

		return true;
	}

	/**
	 * Add user to be notified on Echo event
	 *
	 * @param EchoEvent $event
	 * @param array $users
	 * @return bool
	 */
	public static function onEchoGetDefaultNotifiedUsers( $event, &$users ) {
		switch ( $event->getType() ) {
			case 'social-rel-add':
			case 'social-rel-accept':
				$extra = $event->getExtra();
				$targetId = $extra['target'];
				$users[] = User::newFromId( $targetId );
				break;
		}
		return true;
	}
}
