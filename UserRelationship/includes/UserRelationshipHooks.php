<?php

class UserRelationshipHooks {
	/**
	 * For the Echo extension.
	 *
	 * @param array[] &$notifications Echo notifications
	 * @param array[] &$notificationCategories Echo notification categories
	 * @param array[] &$icons Icon details
	 */
	public static function onBeforeCreateEchoEvent( array &$notifications, array &$notificationCategories, array &$icons ) {
		$notificationCategories['social-rel'] = [
			'priority' => 2,
			'tooltip' => 'echo-pref-tooltip-social-rel',
		];

		$notifications['social-rel-add'] = [
			'primary-link' => [ 'message' => 'notification-link-text-view-edit', 'destination' => 'diff' ],
			'category' => 'social-rel',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserRelationshipPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => [
				'EchoUserLocator::locateEventAgent'
			],
			'payload' => [ 'relationship-add' ],
			'email-subject-message' => 'notification-social-rel-add-email-subject',
			'email-subject-params' => [ 'user' ],
			'email-body-batch-message' => 'notification-social-rel-add-email-batch-body',
			'email-body-batch-params' => [ 'user', 'relationship' ],
			'icon' => 'gratitude',
		];

		$notifications['social-rel-accept'] = [
			'primary-link' => [ 'message' => 'notification-link-text-view-edit', 'destination' => 'diff' ],
			'category' => 'social-rel',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserRelationshipPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => [
				'EchoUserLocator::locateEventAgent'
			],
			'email-subject-message' => 'notification-social-rel-accept-email-subject',
			'email-subject-params' => [ 'user' ],
			'email-body-batch-message' => 'notification-social-rel-accept-email-batch-body',
			'email-body-batch-params' => [ 'user' ],
			'icon' => 'gratitude',
		];

		$icons['social-added'] = [
			'path' => 'SocialProfile/images/notifications-added.svg'
		];
		$icons['gratitude'] = [
			'path' => 'SocialProfile/images/gratitude.png'
		];
	}

	/**
	 * Add user to be notified on Echo event
	 *
	 * @param EchoEvent $event
	 * @param User[] &$users
	 */
	public static function onEchoGetDefaultNotifiedUsers( EchoEvent $event, array &$users ) {
		switch ( $event->getType() ) {
			case 'social-rel-add':
			case 'social-rel-accept':
				$extra = $event->getExtra();
				$targetId = $extra['target'];
				$users[] = User::newFromId( $targetId );
				break;
		}
	}
}
