<?php

class UserBoardHooks {
	/**
	 * For the Echo extension.
	 *
	 * @param array[] &$notifications Echo notifications
	 * @param array[] &$notificationCategories Echo notification categories
	 * @param array[] &$icons Icon details
	 */
	public static function onBeforeCreateEchoEvent( array &$notifications, array &$notificationCategories, array &$icons ) {
		$notificationCategories['social-msg'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-social-msg',
		];

		$notifications['social-msg-send'] = [
			'category' => 'social-msg',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserBoardMessagePresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => [
				'EchoUserLocator::locateEventAgent'
			],

			'icon' => 'emailuser', // per discussion with Cody on 27 March 2016

			'bundle' => [ 'web' => true, 'email' => true ]
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
			case 'social-msg-send':
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
	 * @param string &$bundleString
	 */
	public static function onEchoGetBundleRules( EchoEvent $event, &$bundleString ) {
		switch ( $event->getType() ) {
			case 'social-msg-send':
				$bundleString = 'social-msg-send';
				break;
		}
	}
}
