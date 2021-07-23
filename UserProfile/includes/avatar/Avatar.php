<?php

use MediaWiki\MediaWikiServices;

/**
 * wAvatar class - used to display avatars
 * Example usage:
 * @code
 *	$avatar = new wAvatar( $wgUser->getId(), 'l' );
 *	$wgOut->addHTML( $avatar->getAvatarURL() );
 * @endcode
 * This would display the current user's largest avatar on the page.
 *
 * @file
 * @ingroup Extensions
 */

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class wAvatar {
	/** @var string|null */
	public $user_name = null;
	/** @var int */
	public $user_id;
	/** @var int */
	public $avatar_type = 0;
	/** @var int */
	public $avatar_size;

	/**
	 * @param int $userId User's internal ID number
	 * @param string $size
	 * - 's' for small
	 * - 'm' for medium
	 * - 'ml' for medium-large
	 * - 'l' for large
	 */
	function __construct( $userId, $size ) {
		$this->user_id = $userId;
		$this->avatar_size = $size;
	}

	/**
	 * Fetches the avatar image's name from the filesystem
	 *
	 * @return string Avatar image's file name i.e. default_l.gif or wikidb_3_l.jpg;
	 * - First part for non-default images is the database name
	 * - Second part is the user's ID number
	 * - Third part is the letter for image size (s, m, ml or l)
	 */
	function getAvatarImage() {
		global $wgAvatarKey, $wgUploadDirectory;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$key = $cache->makeKey( 'user', 'profile', 'avatar', $this->user_id, $this->avatar_size );
		$data = $cache->get( $key );

		// Load from memcached if possible
		if ( $data ) {
			$avatar_filename = $data;
		} else {
			$files = glob( $wgUploadDirectory . '/avatars/' . $wgAvatarKey . '_' . $this->user_id . '_' . $this->avatar_size . "*" );
			if ( !isset( $files[0] ) || !$files[0] ) {
				$avatar_filename = 'default_' . $this->avatar_size . '.gif';
			} else {
				$avatar_filename = basename( $files[0] ) . '?r=' . filemtime( $files[0] );
			}
			$cache->set( $key, $avatar_filename, 60 * 60 * 24 ); // cache for 24 hours
		}
		return $avatar_filename;
	}

	/**
	 * Get the web-accessible url for the avatar.
	 *
	 * @return string
	 */
	public function getAvatarUrlPath(): string {
		global $wgUploadBaseUrl, $wgUploadPath;

		$uploadPath = $wgUploadBaseUrl ? $wgUploadBaseUrl . $wgUploadPath : $wgUploadPath;

		return "{$uploadPath}/avatars/{$this->getAvatarImage()}";
	}

	/**
	 * @param array $extraParams Array of extra parameters to give to the image
	 * @return string <img> HTML tag with full path to the avatar image
	 */
	function getAvatarURL( $extraParams = [] ) {
		global $wgUserProfileDisplay, $wgNativeImageLazyLoading;

		$defaultParams = [
			'src' => $this->getAvatarUrlPath(),
			'border' => '0',
			'class' => 'mw-socialprofile-avatar'
		];

		if ( $wgNativeImageLazyLoading ) {
			$defaultParams['loading'] = 'lazy';
		}

		// Allow callers to add a different alt attribute and only add this
		// default one if no alt attribute was provided in $extraParams
		if ( empty( $extraParams['alt'] ) ) {
			$defaultParams['alt'] = 'avatar';
		}

		// If a caller (such as the Refreshed skin) wants to specify custom classes,
		// allow that but keep the default class intact nevertheless.
		if ( isset( $extraParams['class'] ) && $extraParams['class'] ) {
			$defaultParams['class'] = $defaultParams['class'] . ' ' . $extraParams['class'];
		}

		if ( $wgUserProfileDisplay['avatar'] === false ) {
			$defaultParams['src'] = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'; // Replace by a white pixel
			$defaultParams['style'] = 'border-width:0;display:none;';
		}

		$params = array_merge( $extraParams, $defaultParams );

		return Html::element( 'img', $params, '' );
	}

	/**
	 * Is the user's avatar a default one?
	 *
	 * @return bool True if they have a default avatar, false if they've uploaded their own
	 */
	function isDefault() {
		return strpos( $this->getAvatarImage(), 'default_' ) !== false;
	}

	/**
	 * Return a string representation of this avatar object
	 *
	 * @return string Representation of this avatar object
	 */
	public function __toString() {
		return $this->getAvatarURL();
	}
}
