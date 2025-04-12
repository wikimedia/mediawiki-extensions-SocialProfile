<?php
/**
 * Object to easily build icons shown in a user activity feed.
 */
class UserActivityIcon {
	/**
	 * @var string
	 */
	private $icon;

	public function __construct( $icon ) {
		$this->icon = $icon;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->getIconHTML();
	}

	/**
	 * @return string
	 */
	private function getTypeIcon() {
		return UserActivity::getTypeIcon( $this->icon );
	}

	/**
	 * @return string HTML
	 */
	public function getIconHTML() {
		global $wgExtensionAssetsPath;

		$params = [
			'src' => "{$wgExtensionAssetsPath}/SocialProfile/images/{$this->getTypeIcon()}",
			'alt' => $this->getTypeIcon(),
			'border' => 0,
		];

		return Html::element( 'img', $params, '' );
	}
}
