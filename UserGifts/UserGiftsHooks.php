<?php

class UserGiftsHooks {
	/**
	 * For the Echo extension.
	 *
	 * @param array $notifications Echo notifications
	 * @param array $notificationCategories Echo notification categories
	 * @param array $icons Icon details
	 * @return bool
	 */
	public static function onBeforeCreateEchoEvent( &$notifications, &$notificationCategories, &$icons ) {
		$notificationCategories['social-gift'] = array(
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-social-gift',
		);

		$notifications['social-gift-send'] = array(
			'category' => 'social-gift',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserGiftPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => array(
				'EchoUserLocator::locateEventAgent'
			),

			'payload' => array( 'send-message' ), // @todo FIXME

			'icon' => 'social-gift-send',

			'bundle' => array( 'web' => true, 'email' => true ),
			'bundle-message' => 'notification-social-gift-send-bundle'
		);

		// You just were *sent* a gift, thus you *received* it, ergo you should
		// be seeing the *received* icon
		$icons['social-gift-send'] = array(
			'path' => 'SocialProfile/images/notifications-gift-received.svg'
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
			case 'social-gift-send':
				$extra = $event->getExtra();
				$targetId = $extra['target'];
				$users[] = User::newFromId( $targetId );
				break;
		}
		return true;
	}

	/**
	 * Set bundle for message
	 *
	 * @param EchoEvent $event
	 * @param string $bundleString
	 * @return bool
	 */
	public static function onEchoGetBundleRules( $event, &$bundleString ) {
		switch ( $event->getType() ) {
			case 'social-gift-send':
				$bundleString = 'social-gift-send';
				break;
		}
		return true;
	}
}