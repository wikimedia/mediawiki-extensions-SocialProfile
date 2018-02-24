<?php

class UserStatsHooks {
	/**
	 * For the Echo extension.
	 *
	 * @param array $notifications Echo notifications
	 * @param array $notificationCategories Echo notification categories
	 * @param array $icons Icon details
	 */
	public static function onBeforeCreateEchoEvent( &$notifications, &$notificationCategories, &$icons ) {
		$notificationCategories['social-level-up'] = array(
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-social-level-up',
		);

		$notifications['social-level-up'] = array(
			'category' => 'social-level-up',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserLevelAdvancePresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => array(
				'EchoUserLocator::locateEventAgent'
			),

			'title-message' => 'notification-social-level-up',
			'title-params' => array( 'new-level' ),
			'payload' => array( 'level-up' ),

			'icon' => 'social-level-up',

			'bundle' => array( 'web' => true, 'email' => true ),
			'bundle-message' => 'notification-social-level-up-bundle'
		);

		$icons['social-level-up'] = array(
			'path' => 'SocialProfile/images/notifications-level-up.svg'
		);
	}

	/**
	 * Set bundle for message
	 *
	 * @param EchoEvent $event
	 * @param string $bundleString
	 */
	public static function onEchoGetBundleRules( $event, &$bundleString ) {
		switch ( $event->getType() ) {
			case 'social-level-up':
				$bundleString = 'social-level-up';
				break;
		}
	}
}