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
				[ 'EchoUserLocator::locateFromEventExtra', [ 'target' ] ],
			],
			'icon' => 'gratitude',
		];

		$notifications['social-rel-accept'] = [
			'category' => 'social-rel',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserRelationshipPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => [
				[ 'EchoUserLocator::locateFromEventExtra', [ 'target' ] ],
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

}
