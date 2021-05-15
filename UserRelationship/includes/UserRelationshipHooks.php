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
			'category' => 'social-rel',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserRelationshipPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => [
				'EchoUserLocator::locateEventAgent'
			],
			'icon' => 'gratitude',
		];

		$notifications['social-rel-accept'] = [
			'category' => 'social-rel',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserRelationshipPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => [
				'EchoUserLocator::locateEventAgent'
			],
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
