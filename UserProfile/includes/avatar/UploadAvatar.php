<?php

use MediaWiki\MediaWikiServices;

class UploadAvatar extends UploadFromFile {
	/** @var string */
	public $mExtension;
	/** @var string */
	public $avatarUploadDirectory;

	function createThumbnail( $imageSrc, $imageInfo, $imgDest, $thumbWidth ) {
		global $wgUseImageMagick, $wgImageMagickConvertCommand;

		if ( $wgUseImageMagick ) { // ImageMagick is enabled
			list( $origWidth, $origHeight, $typeCode ) = $imageInfo;

			if ( $origWidth < $thumbWidth ) {
				$thumbWidth = $origWidth;
			}
			$thumbHeight = ( $thumbWidth * $origHeight / $origWidth );
			$border = ' -bordercolor white  -border  0x';
			if ( $thumbHeight < $thumbWidth ) {
				$border = ' -bordercolor white  -border  0x' . ( ( $thumbWidth - $thumbHeight ) / 2 );
			}
			if ( $typeCode == 2 ) {
				exec(
					$wgImageMagickConvertCommand . ' -size ' . $thumbWidth . 'x' . $thumbWidth .
					' -resize ' . $thumbWidth . ' -crop ' . $thumbWidth . 'x' .
					$thumbWidth . '+0+0   -quality 100 ' . $border . ' ' .
					$imageSrc . ' ' . $this->avatarUploadDirectory . '/' . $imgDest . '.jpg'
				);
			}
			if ( $typeCode == 1 ) {
				exec(
					$wgImageMagickConvertCommand . ' -size ' . $thumbWidth . 'x' . $thumbWidth .
					' -resize ' . $thumbWidth . ' -crop ' . $thumbWidth . 'x' .
					$thumbWidth . '+0+0 ' . $imageSrc . ' ' . $border . ' ' .
					$this->avatarUploadDirectory . '/' . $imgDest . '.gif'
				);
			}
			if ( $typeCode == 3 ) {
				exec(
					$wgImageMagickConvertCommand . ' -size ' . $thumbWidth . 'x' . $thumbWidth .
					' -resize ' . $thumbWidth . ' -crop ' . $thumbWidth . 'x' .
					$thumbWidth . '+0+0 ' . $imageSrc . ' ' .
					$this->avatarUploadDirectory . '/' . $imgDest . '.png'
				);
			}
		} else { // ImageMagick is not enabled, so fall back to PHP's GD library
			// Get the image size, used in calculations later.
			list( $origWidth, $origHeight, $typeCode ) = getimagesize( $imageSrc );

			switch ( $typeCode ) {
				case '1':
					$fullImage = imagecreatefromgif( $imageSrc );
					$ext = 'gif';
					break;
				case '2':
					$fullImage = imagecreatefromjpeg( $imageSrc );
					$ext = 'jpg';
					break;
				case '3':
					$fullImage = imagecreatefrompng( $imageSrc );
					$ext = 'png';
					break;
			}

			$scale = ( $thumbWidth / $origWidth );

			// Create our thumbnail size, so we can resize to this, and save it.
			$tnImage = imagecreatetruecolor(
				$origWidth * $scale,
				$origHeight * $scale
			);

			// Resize the image.
			imagecopyresampled(
				$tnImage,
				$fullImage,
				0, 0, 0, 0,
				$origWidth * $scale,
				$origHeight * $scale,
				$origWidth,
				$origHeight
			);

			// Create a new image thumbnail.
			if ( $typeCode == 1 ) {
				imagegif( $tnImage, $imageSrc );
			} elseif ( $typeCode == 2 ) {
				imagejpeg( $tnImage, $imageSrc );
			} elseif ( $typeCode == 3 ) {
				imagepng( $tnImage, $imageSrc );
			}

			// Clean up.
			imagedestroy( $fullImage );
			imagedestroy( $tnImage );

			// Copy the thumb
			copy(
				$imageSrc,
				$this->avatarUploadDirectory . '/' . $imgDest . '.' . $ext
			);
		}
	}

	/**
	 * Create the thumbnails and delete old files
	 *
	 * @param string $comment
	 * @param string $pageText
	 * @param bool $watch
	 * @param User $user
	 * @param string[] $tags
	 * @param string|null $watchlistExpiry Optional watchlist expiry timestamp in any format
	 *   acceptable to wfTimestamp(). [unused here]
	 *
	 * @return Status
	 */
	public function performUpload( $comment, $pageText, $watch, $user, $tags = [], ?string $watchlistExpiry = null ) {
		global $wgUploadDirectory, $wgAvatarKey, $wgTmpDirectory;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$this->avatarUploadDirectory = $wgUploadDirectory . '/avatars';

		$imageInfo = getimagesize( $this->mTempPath );
		if ( empty( $imageInfo[2] ) ) {
			return Status::newFatal( 'empty-file' );
		}

		switch ( $imageInfo[2] ) {
			case 1:
				$ext = 'gif';
				break;
			case 2:
				$ext = 'jpg';
				break;
			case 3:
				$ext = 'png';
				break;
			default:
				return Status::newFatal( 'filetype-banned' );
		}

		$dest = $this->avatarUploadDirectory;

		$uid = $user->getId();
		$avatar = new wAvatar( $uid, 'l' );
		// If this is the user's first custom avatar, update statistics (in
		// case if we want to give out some points to the user for uploading
		// their first avatar)
		if ( $avatar->isDefault() ) {
			$stats = new UserStatsTrack( $uid, $user->getName() );
			$stats->incStatField( 'user_image' );
		}

		$this->createThumbnail( $this->mTempPath, $imageInfo, $wgAvatarKey . '_' . $uid . '_l', 75 );
		$this->createThumbnail( $this->mTempPath, $imageInfo, $wgAvatarKey . '_' . $uid . '_ml', 50 );
		$this->createThumbnail( $this->mTempPath, $imageInfo, $wgAvatarKey . '_' . $uid . '_m', 30 );
		$this->createThumbnail( $this->mTempPath, $imageInfo, $wgAvatarKey . '_' . $uid . '_s', 16 );

		'@phan-var string $wgTmpDirectory';
		if ( $ext != 'jpg' ) {
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_s.jpg' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_s.jpg' );
			}
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_m.jpg' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_m.jpg' );
			}
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_ml.jpg' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_ml.jpg' );
			}
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_l.jpg' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_l.jpg' );
			}
		}
		if ( $ext != 'gif' ) {
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_s.gif' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_s.gif' );
			}
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_m.gif' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_m.gif' );
			}
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_ml.gif' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_ml.gif' );
			}
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_l.gif' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_l.gif' );
			}
		}
		if ( $ext != 'png' ) {
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_s.png' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_s.png' );
			}
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_m.png' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_m.png' );
			}
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_ml.png' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_ml.png' );
			}
			if ( is_file( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_l.png' ) ) {
				unlink( $wgTmpDirectory . '/' . $wgAvatarKey . '_' . $uid . '_l.png' );
			}
		}

		$sizes = [ 's', 'm', 'ml', 'l' ];

		// Also delete any and all old versions of the user's _current_ avatar
		// because the code in wAvatar#getAvatarImage assumes that there is only
		// one current avatar (which, in all fairness, *is* a reasonable assumption)
		foreach ( [ 'gif', 'jpg', 'png' ] as $fileExtension ) {
			if ( $fileExtension === $ext ) {
				// Our brand new avatar; skip over it in order to _not_ delete it, obviously
			} else {
				// Delete every other avatar image for this user that exists in the
				// avatars directory (usually <path to MW installation>/images/avatars)
				foreach ( $sizes as $size ) {
					if ( is_file( $dest . '/' . $wgAvatarKey . '_' . $uid . '_' . $size . '.' . $fileExtension ) ) {
						unlink( $dest . '/' . $wgAvatarKey . '_' . $uid . '_' . $size . '.' . $fileExtension );
					}
				}
			}
		}

		// Purge caches as well
		foreach ( $sizes as $size ) {
			$key = $cache->makeKey( 'user', 'profile', 'avatar', $uid, $size );
			$cache->delete( $key );
		}

		$this->mExtension = $ext;
		return Status::newGood();
	}

	/**
	 * Don't verify the upload, since it all dangerous stuff is killed by
	 * making thumbnails
	 *
	 * @return array
	 */
	public function verifyUpload() {
		// Need this for AbuseFilter/generic UploadBase suckage.
		// Alternatively we could just comment out the stashing logic in
		// ../specials/SpecialUploadAvatar.php, function showRecoverableUploadError()
		// @see https://phabricator.wikimedia.org/T239447
		// @note $this->mFinalExtension appears to be always empty (?!) even if I set it
		// in performUpload() above. It probably doesn't really matter b/c "our" code
		// doesn't use that variable per se, this stuff in this method is here just to
		// keep AbuseFilter happy and such. (And who knows, perhaps some other things also
		// blindly assume that mFileProps is always set...)
		$mwProps = new MWFileProps( MediaWikiServices::getInstance()->getMimeAnalyzer() );
		$this->mFileProps = $mwProps->getPropsFromPath( $this->mTempPath, $this->mFinalExtension );
		return [ 'status' => self::OK ];
	}

	/**
	 * Only needed for the redirect; needs fixage
	 *
	 * @return Title
	 */
	public function getTitle() {
		return Title::makeTitle( NS_FILE, 'Avatar-placeholder' . uniqid() . '.jpg' );
	}

	/**
	 * We don't overwrite stuff, so don't care
	 *
	 * @param User|null $user Ignored; required for type fit with upstream.
	 * @return array
	 */
	public function checkWarnings( $user = null ) {
		return [];
	}
}
