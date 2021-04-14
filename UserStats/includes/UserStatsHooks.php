<?php

class UserStatsHooks {

	/**
	 * Set up the <randomfeatureduser> tag
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'randomfeatureduser', [ 'RandomFeaturedUser', 'getRandomUser' ] );
	}

	/**
	 * For the Echo extension.
	 *
	 * @param array[] &$notifications Echo notifications
	 * @param array[] &$notificationCategories Echo notification categories
	 * @param array[] &$icons Icon details
	 */
	public static function onBeforeCreateEchoEvent( array &$notifications, array &$notificationCategories, array &$icons ) {
		$notificationCategories['social-level-up'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-social-level-up',
		];

		$notifications['social-level-up'] = [
			'category' => 'social-level-up',
			'group' => 'interactive',
			'presentation-model' => 'EchoUserLevelAdvancePresentationModel',
			EchoAttributeManager::ATTR_LOCATORS => [
				'EchoUserLocator::locateEventAgent'
			],
			'canNotifyAgent' => true,

			'icon' => 'social-level-up',

			'bundle' => [ 'web' => true, 'email' => true ]
		];

		$icons['social-level-up'] = [
			'path' => 'SocialProfile/images/notifications-level-up.svg'
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
			case 'social-level-up':
				$bundleString = 'social-level-up';
				break;
		}
	}
}
