<?php

class UserGiftsHooks {
	/**
	 * For the Echo extension.
	 *
	 * @param array[] &$notifications Echo notifications
	 * @param array[] &$notificationCategories Echo notification categories
	 * @param array[] &$icons Icon details
	 */
	public static function onBeforeCreateEchoEvent( array &$notifications, array &$notificationCategories, array &$icons ) {
		$notificationCategories['social-gift'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-social-gift',
		];

		$notifications['social-gift-send'] = [
			'category' => 'social-gift',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserGiftPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => [
				[ 'EchoUserLocator::locateFromEventExtra', [ 'target' ] ],
			],

			'icon' => 'social-gift-send',

			'bundle' => [ 'web' => true, 'email' => true ]
		];

		// You just were *sent* a gift, thus you *received* it, ergo you should
		// be seeing the *received* icon
		$icons['social-gift-send'] = [
			'path' => 'SocialProfile/images/notifications-gift-received.svg'
		];
	}

	/**
	 * Set bundle for message
	 *
	 * @param EchoEvent $event
	 * @param string &$bundleString
	 */
	public static function onEchoGetBundleRules( EchoEvent $event, &$bundleString ) {
		switch ( $event->getType() ) {
			case 'social-gift-send':
				$bundleString = 'social-gift-send';
				break;
		}
	}
}
