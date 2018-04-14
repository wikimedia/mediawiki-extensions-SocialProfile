<?php

class UserSystemGiftsHooks {
	/**
	 * For the Echo extension.
	 *
	 * @param array $notifications Echo notifications
	 * @param array $notificationCategories Echo notification categories
	 * @param array $icons Icon details
	 */
	public static function onBeforeCreateEchoEvent( &$notifications, &$notificationCategories, &$icons ) {
		$notificationCategories['social-award'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-social-award',
		];

		$notifications['social-award-rec'] = [
			'category' => 'social-award',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserSystemGiftPresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => [
				'EchoUserLocator::locateEventAgent'
			],

			'payload' => [ 'award-rec' ],

			'icon' => 'social-award',

			'bundle' => [ 'web' => true, 'email' => true ],
			'bundle-message' => 'notification-social-award-rec-bundle',
			'bundle-params' => [ 'bundle-user-count', 'bundle-noti-count' ] // @todo FIXME: 100% incorrect & bad copypasta
		];

		$icons['social-award'] = [
			'path' => 'SocialProfile/images/notifications-award.svg'
		];
	}

	/**
	 * Add user to be notified on Echo event
	 *
	 * @param EchoEvent $event
	 * @param array $users
	 */
	public static function onEchoGetDefaultNotifiedUsers( $event, &$users ) {
		switch ( $event->getType() ) {
			case 'social-award-rec':
				$extra = $event->getExtra();
				$targetId = $extra['target'];
				$users[] = User::newFromId( $targetId );
				break;
		}
	}

	/**
	 * Set bundle for message
	 *
	 * @param EchoEvent $event
	 * @param string $bundleString
	 */
	public static function onEchoGetBundleRules( $event, &$bundleString ) {
		switch ( $event->getType() ) {
			case 'social-award-rec':
				$bundleString = 'social-award-rec';
				break;
		}
	}
}