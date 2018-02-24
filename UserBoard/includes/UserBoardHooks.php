<?php

class UserBoardHooks {
	/**
	 * For the Echo extension.
	 *
	 * @param array $notifications Echo notifications
	 * @param array $notificationCategories Echo notification categories
	 * @param array $icons Icon details
	 */
	public static function onBeforeCreateEchoEvent( &$notifications, &$notificationCategories, &$icons ) {
		$notificationCategories['social-msg'] = array(
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-social-msg',
		);

		$notifications['social-msg-send'] = array(
			'category' => 'social-msg',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserBoardMessagePresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => array(
				'EchoUserLocator::locateEventAgent'
			),

			'payload' => array( 'send-message' ),

			'icon' => 'emailuser', // per discussion with Cody on 27 March 2016

			'bundle' => array( 'web' => true, 'email' => true ),
			'bundle-message' => 'notification-social-msg-send-bundle',
			'bundle-params' => array( 'bundle-user-count', 'bundle-noti-count' )
		);
	}

	/**
	 * Add user to be notified on Echo event
	 *
	 * @param EchoEvent $event
	 * @param array $users
	 */
	public static function onEchoGetDefaultNotifiedUsers( $event, &$users ) {
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
	 * @param string $bundleString
	 */
	public static function onEchoGetBundleRules( $event, &$bundleString ) {
		switch ( $event->getType() ) {
			case 'social-msg-send':
				$bundleString = 'social-msg-send';
				break;
		}
	}
}