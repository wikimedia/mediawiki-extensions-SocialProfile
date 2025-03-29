<?php
/**
 * Decides which file backend to use for storing custom images used by
 * SocialProfile which are not treated as normal MediaWiki images.
 * Such images are:
 * -user avatars
 * -system gift (award) images
 * -user-to-user gift images
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\MediaWikiServices;
use MediaWiki\WikiMap\WikiMap;

class SocialProfileFileBackend {

	/** @var string The name of the container to use */
	private $container;

	/**
	 * @param string $container The name of the container to use.
	 *   System gifts (awards) and user-to-user gifts both use 'awards'; user
	 *   avatars use 'avatars'.
	 */
	public function __construct( string $container ) {
		$this->container = $container;
	}

	/**
	 * Get a FileBackend class.
	 *
	 * @return FileBackend
	 */
	public function getFileBackend() {
		$services = MediaWikiServices::getInstance();
		$mainConfig = $services->getMainConfig();

		if ( !empty( $mainConfig->get( 'SocialProfileFileBackend' ) ) ) {
			$backend = $services->getFileBackendGroup()->get(
				$mainConfig->get( 'SocialProfileFileBackend' )
			);
		} else {
			$backend = new FSFileBackend( [
				// We just set the backend name to match the container to
				// avoid having to set another variable of the same value
				'name'           => "{$this->container}-backend",
				'wikiId'         => WikiMap::getCurrentWikiId(),
				'lockManager'    => new NullLockManager( [] ),
				'containerPaths' => [ $this->container => "{$mainConfig->get( 'UploadDirectory' )}/{$this->container}" ],
				'fileMode'       => 0777,
				'obResetFunc'    => 'wfResetOutputBuffers',
				'streamMimeFunc' => [ 'StreamFile', 'contentTypeFromPath' ],
				'statusWrapper'  => [ 'Status', 'wrap' ],
			] );
		}

		if ( !$backend->directoryExists( [ 'dir' => $backend->getContainerStoragePath( $this->container ) ] ) ) {
			$backend->prepare( [ 'dir' => $backend->getContainerStoragePath( $this->container ) ] );
		}

		return $backend;
	}

	/**
	 * Get the path to an image (e.g. avatar, award or gift one).
	 *
	 * @param string $prefix The prefix to use that goes in front of $id
	 * @param int $id User ID for avatars; internal identifier (sg_id/ug_id) for awards/gifts
	 * @param string $size Size of the image to get
	 * - s for small
	 * - m for medium
	 * - ml for medium-large
	 * - l for large
	 * @param string $ext File extension (currently can be only png, jpg, jpeg, or gif)
	 *
	 * @return string|null Normalized full storage path or null on failure
	 */
	public function getPath( $prefix, $id, $size, $ext ) {
		return $this->getFileBackend()->normalizeStoragePath(
			$this->getContainerStoragePath() .
			'/' . $this->getFileName( $prefix, $id, $size, $ext )
		);
	}

	/**
	 * Get the file name of an image.
	 *
	 * @param string $prefix The prefix to use that goes in front of $id
	 * @param int $id User ID for avatars; internal identifier (sg_id/ug_id) for awards/gifts
	 * @param string $size Size of the image to get
	 * - s for small
	 * - m for medium
	 * - ml for medium-large
	 * - l for large
	 * @param string $ext File extension (currently can be only png, jpg, jpeg, or gif)
	 *
	 * @return string file name
	 */
	public function getFileName( $prefix, $id, $size, $ext ) {
		return $prefix . (string)$id . '_' . $size . '.' . $ext;
	}

	/**
	 * Get the backend container storage path.
	 *
	 * @return string Storage path
	 */
	public function getContainerStoragePath() {
		return $this->getFileBackend()->getContainerStoragePath( $this->container );
	}

	/**
	 * Get the HTTP URL for a file.
	 *
	 * @param string $prefix The prefix to use that goes in front of $id
	 * @param int $id User ID for avatars; internal identifier (sg_id/ug_id) for awards/gifts
	 * @param string $size Size of the image to get
	 * - s for small
	 * - m for medium
	 * - ml for medium-large
	 * - l for large
	 * @param string $ext File extension (can be only png, jpg, jpeg, or gif)
	 *
	 * @return string|null URL or null on failure
	 */
	public function getFileHttpUrl( $prefix, $id, $size, $ext ) {
		return $this->getDefaultUrlPath( $this->getFileName( $prefix, $id, $size, $ext ) );
	}

	/**
	 * Get the HTTP URL for a file by full name of the file.
	 *
	 * @param string $fileName the file name
	 * @return string|null URL or null on failure
	 */
	public function getFileHttpUrlFromName( $fileName ) {
		return $this->getDefaultUrlPath( $fileName );
	}

	/**
	 * Get the HTTP URL for a file by full name of the file.
	 * If getFileHttpUrl() returns null, we fallback to this.
	 *
	 * @param string $fileName the file name
	 * @return string URL
	 */
	public function getDefaultUrlPath( $fileName ) {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();

		$uploadPath = $mainConfig->get( 'UploadBaseUrl' ) ? $mainConfig->get( 'UploadBaseUrl' ) .
			$mainConfig->get( 'UploadPath' ) :
			$mainConfig->get( 'UploadPath' );

		return $uploadPath . '/' . $this->container . '/' . $fileName;
	}

	/**
	 * Check if a file exists in the given backend.
	 *
	 * @param string $prefix The prefix to use that goes in front of $id
	 * @param int $id User ID for avatars; internal identifier (sg_id/ug_id) for awards/gifts
	 * @param string $size Size of the image to get
	 * - s for small
	 * - m for medium
	 * - ml for medium-large
	 * - l for large
	 * @param string $ext File extension (can be only png, jpg, jpeg, or gif)
	 * @return bool|null Whether the file exists or null on failure
	 */
	public function fileExists( $prefix, $id, $size, $ext ) {
		return $this->getFileBackend()->fileExists( [
			'src' => $this->getPath(
				$prefix, $id, $size, $ext
			)
		] );
	}
}
