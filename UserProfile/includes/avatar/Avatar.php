<?php

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;

/**
 * wAvatar class - used to display avatars
 *
 * Example usage:
 * @code
 *	$context = RequestContext::getMain();
 *	$avatar = new wAvatar( $context->getUser()->getId(), 'l' );
 *	$context->getOutput()->addHTML( $avatar->getAvatarURL() );
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

	/** @var string Avatar size (abbreviation): s/m/ml/l */
	public $avatar_size;

	/**
	 * @param int $userId User's internal ID number
	 * @param string $size Avatar image size
	 * - 's' for small (16x16px)
	 * - 'm' for medium (30x30px)
	 * - 'ml' for medium-large (50x50px)
	 * - 'l' for large (75x75px)
	 */
	public function __construct( $userId, $size ) {
		$this->user_id = $userId;
		$this->avatar_size = $size;
	}

	/**
	 * Check if there is a default avatar image with the supplied $size.
	 *
	 * @param string $size Avatar image size
	 * @return bool|null Returns null on failure
	 */
	private function defaultAvatarExists( $size ) {
		$backend = new SocialProfileFileBackend( 'avatars' );
		return $backend->getFileBackend()->fileExists( [
			'src' => $backend->getContainerStoragePath() . '/default_' . $size . '.gif',
		] );
	}

	/**
	 * Upload a default avatar image in the supplied $size.
	 *
	 * @param string $size Avatar image size
	 * @return StatusValue
	 */
	private function uploadDefaultAvatars( $size ) {
		$backend = new SocialProfileFileBackend( 'avatars' );
		return $backend->getFileBackend()->quickStore( [
			'src' => __DIR__ . '/../../../avatars/default_' . $size . '.gif',
			'dst' => $backend->getContainerStoragePath() . '/default_' . $size . '.gif',
		] );
	}

	/**
	 * Fetches the avatar image's name from the file backend
	 *
	 * @return string Avatar image's file name i.e. default_l.gif or wikidb_3_l.jpg;
	 * - First part for non-default images is the database name
	 * - Second part is the user's ID number
	 * - Third part is the letter for image size (s, m, ml or l)
	 */
	public function getAvatarImage() {
		global $wgAvatarKey;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$key = $cache->makeKey( 'user', 'profile', 'avatar', $this->user_id, $this->avatar_size );
		$data = $cache->get( $key );

		// Load from memcached if possible
		if ( $data ) {
			$avatar_filename = $data;
		} else {
			// @todo FIXME: This seems unnecessarily intensive since this really
			// should be done at install time and never again afterwards.
			// Move this to SocialProfileHooks#onLoadExtensionSchemaUpdates or something?
			if ( !$this->defaultAvatarExists( 'l' ) ) {
				$this->uploadDefaultAvatars( 'l' );
			}

			if ( !$this->defaultAvatarExists( 'm' ) ) {
				$this->uploadDefaultAvatars( 'm' );
			}

			if ( !$this->defaultAvatarExists( 'ml' ) ) {
				$this->uploadDefaultAvatars( 'ml' );
			}

			if ( !$this->defaultAvatarExists( 's' ) ) {
				$this->uploadDefaultAvatars( 's' );
			}

			$avatar_filename = 'default_' . $this->avatar_size . '.gif';

			$backend = new SocialProfileFileBackend( 'avatars' );
			$extensions = [ 'png', 'gif', 'jpg', 'jpeg' ];
			foreach ( $extensions as $ext ) {
				if ( $backend->fileExists( $wgAvatarKey . '_', $this->user_id, $this->avatar_size, $ext ) ) {
					$avatar_filename = $backend->getFileName(
						$wgAvatarKey . '_', $this->user_id, $this->avatar_size, $ext
					);

					// @phan-suppress-next-line PhanTypeArraySuspiciousNullable Not sure why phan is unhappy
					$avatar_filename .= '?r=' . $backend->getFileBackend()->getFileStat( [
						'src' => $backend->getContainerStoragePath() . '/' . $avatar_filename
					] )['mtime'];

					// We only really care about the first one being found, so exit once it finds one
					break;
				}
			}

			$cache->set( $key, $avatar_filename, 60 * 60 * 24 ); // cache for 24 hours
		}

		return $avatar_filename;
	}

	/**
	 * @param array $extraParams Array of extra parameters to give to the image;
	 *  if [ 'raw' => true ], returns the raw avatar URL *without* the surrounding <img> tag
	 * @return string Either the <img> HTML tag with full path to the avatar image
	 *  or the raw avatar URL only if requested
	 */
	public function getAvatarURL( $extraParams = [] ) {
		global $wgUserProfileDisplay, $wgNativeImageLazyLoading;

		$backend = new SocialProfileFileBackend( 'avatars' );

		$url = $backend->getFileHttpUrlFromName( $this->getAvatarImage() );
		if ( isset( $extraParams['raw'] ) && $extraParams['raw'] === true ) {
			return $url;
		}

		$defaultParams = [
			'src' => $url,
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
	public function isDefault() {
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
