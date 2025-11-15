<?php

use MediaWiki\Html\Html;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Request\WebRequestUpload;
use MediaWiki\Shell\Shell;

/**
 * A special page to upload images for gifts.
 * This is mostly copied from an old version of Special:Upload and changed a
 * bit.
 *
 * @file
 * @ingroup Extensions
 */
class GiftManagerLogo extends UnlistedSpecialPage {

	/** @var string|null */
	public $mUploadSaveName;
	/** @var string|null */
	public $mUploadTempName;
	/** @var int|null */
	public $mUploadSize;
	/** @var string|null */
	public $mUploadCopyStatus;
	/** @var string|null */
	public $mUploadSource;
	/** @var string|null */
	public $mAction;
	/** @var bool */
	public $mUpload;
	/** @var string|null */
	public $mOname;
	/** @var string|null */
	public $mDestFile;
	/** @var string|null */
	public $mSavedFile;
	/** @var bool|null */
	public $mTokenOk;
	/** @var string[]|null */
	public $fileExtensions;
	/** @var int|null */
	public $gift_id;

	public function __construct() {
		parent::__construct( 'GiftManagerLogo' );
	}

	/**
	 * Group this special page under the correct header in Special:SpecialPages.
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'wiki';
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$this->gift_id = $this->getRequest()->getInt( 'gift_id' );
		$this->initLogo();
		$this->executeLogo();
	}

	private function canUserManage() {
		$user = $this->getUser();

		if ( $user->isAllowed( 'giftadmin' ) ) {
			return true;
		} elseif ( $this->gift_id ) {
			$gift = Gifts::getGift( $this->gift_id );
			return $user->getActorId() == $gift['creator_actor'];
		}

		return false;
	}

	function initLogo() {
		$this->fileExtensions = [ 'gif', 'jpg', 'jpeg', 'png' ];

		$request = $this->getRequest();

		if ( !$request->wasPosted() ) {
			# GET requests just give the main form; no data except wpDestfile.
			return;
		}
		$this->gift_id = $request->getInt( 'gift_id' );
		$this->mUpload = $request->getCheck( 'wpUpload' );

		$this->mUploadCopyStatus = $request->getText( 'wpUploadCopyStatus' );
		$this->mUploadSource = $request->getText( 'wpUploadSource' );

		$this->mAction = $request->getVal( 'action' );
		/**
		 * Check for a newly uploaded file.
		 */
		$this->mUploadTempName = $request->getFileTempname( 'wpUploadFile' );
		$file = new WebRequestUpload( $request, 'wpUploadFile' );
		$this->mUploadSize = $file->getSize();
		$this->mOname = $request->getFileName( 'wpUploadFile' );

		// If it was posted check for the token (no remote POST'ing with user credentials)
		$token = $request->getVal( 'wpEditToken' );
		$this->mTokenOk = $this->getUser()->matchEditToken( $token );
	}

	/**
	 * Start doing stuff
	 */
	public function executeLogo() {
		global $wgEnableUploads;

		$out = $this->getOutput();
		$user = $this->getUser();

		/** Show an error message if file upload is disabled */
		if ( !$wgEnableUploads ) {
			$out->addWikiMsg( 'uploaddisabled' );
			return;
		}

		// user needs to be logged in to access
		$this->requireLogin();

		/** Various rights checks */
		if ( !$user->isAllowed( 'upload' ) ) {
			throw new PermissionsError( 'upload' );
		}

		// Check blocks
		$block = $user->getBlock();
		if ( $block || $user->isBlockedFromUpload() ) {
			throw new UserBlockedError(
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Fake news!
				$block,
				$user,
				$this->getLanguage(),
				$this->getRequest()->getIP()
			);
		}

		$this->checkReadOnly();

		// Set the robot policies, etc.
		$out->setArticleRelated( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		if ( $this->mAction == 'submit' || $this->mUpload ) {
			if ( $this->mTokenOk ) {
				$this->processUpload();
			} else {
				// Possible CSRF attempt or something...
				$this->mainUploadForm( $this->msg( 'session_fail_preview' )->parse() );
			}
		} else {
			$this->mainUploadForm();
		}
	}

	/**
	 * Really do the upload
	 * Checks are made in SpecialUpload::execute()
	 *
	 * @return void|string Might return an HTML snippet, error message, or nothing on success
	 */
	function processUpload() {
		/**
		 * If there was no filename or a zero size given, give up quick.
		 */
		if ( trim( $this->mOname ) == '' || empty( $this->mUploadSize ) ) {
			return $this->mainUploadForm( '<li>' . htmlspecialchars( $this->msg( 'emptyfile' )->plain() ) . '</li>' );
		}

		# Chop off any directories in the given filename
		if ( $this->mDestFile ) {
			$basename = basename( $this->mDestFile );
		} else {
			$basename = basename( $this->mOname );
		}

		/**
		 * We'll want to blacklist against *any* 'extension', and use
		 * only the final one for the whitelist.
		 */
		[ $partname, $ext ] = UploadBase::splitExtensions( $basename );
		if ( count( $ext ) ) {
			$finalExt = $ext[count( $ext ) - 1];
		} else {
			$finalExt = '';
		}
		$fullExt = implode( '.', $ext );

		$this->mUploadSaveName = $basename;
		$filtered = $basename;

		/* Don't allow users to override the blacklist (check file extension) */
		global $wgStrictFileExtensions, $wgFileBlacklist;

		if ( UploadBase::checkFileExtensionList( $ext, $wgFileBlacklist ) ||
			( $wgStrictFileExtensions &&
				!UploadBase::checkFileExtension( $finalExt, $this->fileExtensions ) ) ) {
			return $this->uploadError( $this->msg( 'filetype-banned', htmlspecialchars( $fullExt ) )->escaped() );
		}

		/**
		 * Look at the contents of the file; if we can recognize the
		 * type but it's corrupt or data of the wrong type, we should
		 * probably not accept it.
		 */
		$veri = $this->verify( $this->mUploadTempName, $finalExt );

		if ( !$veri->isGood() ) {
			return $this->uploadError( $this->getOutput()->parseAsInterface( $veri->getWikiText() ) );
		}

		/**
		 * Check for wrong file type/too big/empty file
		 */
		global $wgCheckFileExtensions;
		if ( $wgCheckFileExtensions ) {
			if ( !UploadBase::checkFileExtension( $finalExt, $this->fileExtensions ) ) {
				return $this->uploadError( $this->msg( 'filetype-banned', htmlspecialchars( $fullExt ) )->escaped() );
			}
		}

		global $wgUploadSizeWarning;
		// @todo FIXME: This should probably check that 100 kB limit explained to the user
		// in the instructions msg rather than $wgUploadSizeWarning.
		// Currently uploading a file larger than that results in hitting the fatal
		// error condition in saveUploadedFile() whereas ideally it'd be caught here.
		if ( $wgUploadSizeWarning && ( $this->mUploadSize > $wgUploadSizeWarning ) ) {
			$lang = $this->getLanguage();
			$wsize = $lang->formatSize( $wgUploadSizeWarning );
			$asize = $lang->formatSize( $this->mUploadSize );
			return $this->uploadError( $this->msg( 'large-file', $wsize, $asize )->escaped() );
		}

		if ( $this->mUploadSize == 0 ) {
			return $this->uploadError( $this->msg( 'emptyfile' )->escaped() );
		}

		/**
		 * Try actually saving the thing...
		 * It will show an error form on failure.
		 */
		$status = $this->saveUploadedFile(
			$this->mUploadSaveName,
			$this->mUploadTempName,
			strtoupper( $fullExt )
		);

		if ( $status > 0 ) {
			$this->showSuccess( $status );
		}
	}

	/**
	 * Create the gift image thumbnails, either with ImageMagick or GD.
	 *
	 * @param string $imageSrc Path to the temporary file
	 * @param string $ext File extension (gif, jpg, png); de facto unused when using GD
	 * @param string $imgDest <gift ID>_<size code>, e.g. 20_l for a large image for gift ID #20
	 * @param int $thumbWidth Thumbnail image width in pixels
	 */
	function createThumbnail( $imageSrc, $ext, $imgDest, $thumbWidth ) {
		global $wgUseImageMagick, $wgImageMagickConvertCommand;

		[ $origWidth, $origHeight, $typeCode ] = getimagesize( $imageSrc );

		$backend = new SocialProfileFileBackend( 'awards' );
		$dir = $backend->getContainerStoragePath();

		$fileBackend = $backend->getFileBackend();
		$status = $fileBackend->prepare( [ 'dir' => $dir ] );

		if ( !$status->isOK() ) {
			throw new Exception(
				$this->msg( 'backend-fail-internal', Status::wrap( $status )->getWikitext() )
			);
		}

		if ( $wgUseImageMagick ) { // ImageMagick is enabled
			if ( $origWidth < $thumbWidth ) {
				$thumbWidth = $origWidth;
			}
			$thumbHeight = ( $thumbWidth * $origHeight / $origWidth );
			$border = '';
			if ( $thumbHeight < $thumbWidth ) {
				$border = ' -bordercolor white -border 0x' . ( ( $thumbWidth - $thumbHeight ) / 2 );
			}
			if ( $typeCode == 2 ) {
				exec(
					Shell::escape( $wgImageMagickConvertCommand ) . ' -size ' . $thumbWidth . 'x' .
					$thumbWidth . ' -resize ' . $thumbWidth . '  -quality 100 ' .
					$border . ' ' . Shell::escape( $imageSrc ) . ' ' .
					wfTempDir() . '/' . $imgDest . '.jpg'
				);

				$status = $fileBackend->quickStore( [
					'src' => wfTempDir() . '/' . $imgDest . '.jpg',
					'dst' => $dir . '/' . $imgDest . '.jpg'
				] );

				if ( !$status->isOK() ) {
					throw new Exception(
						$this->msg( 'backend-fail-internal', Status::wrap( $status )->getWikitext() )
					);
				}
			}
			if ( $typeCode == 1 ) {
				exec(
					Shell::escape( $wgImageMagickConvertCommand ) . ' -size ' . $thumbWidth . 'x' .
					$thumbWidth . ' -resize ' . $thumbWidth . ' ' . Shell::escape( $imageSrc ) .
					' ' . $border . ' ' .
					wfTempDir() . '/' . $imgDest . '.gif'
				);

				$status = $fileBackend->quickStore( [
					'src' => wfTempDir() . '/' . $imgDest . '.gif',
					'dst' => $dir . '/' . $imgDest . '.gif'
				] );

				if ( !$status->isOK() ) {
					throw new Exception(
						$this->msg( 'backend-fail-internal', Status::wrap( $status )->getWikitext() )
					);
				}
			}
			if ( $typeCode == 3 ) {
				exec(
					Shell::escape( $wgImageMagickConvertCommand ) . ' -size ' . $thumbWidth . 'x' .
					$thumbWidth . ' -resize ' . $thumbWidth . ' ' . Shell::escape( $imageSrc ) .
					' ' . wfTempDir() . '/' . $imgDest . '.png'
				);

				$status = $fileBackend->quickStore( [
					'src' => wfTempDir() . '/' . $imgDest . '.png',
					'dst' => $dir . '/' . $imgDest . '.png'
				] );

				if ( !$status->isOK() ) {
					throw new Exception(
						$this->msg( 'backend-fail-internal', Status::wrap( $status )->getWikitext() )
					);
				}
			}
		} else {
			// ImageMagick is not enabled, so fall back to PHP's GD library
			// Get the image size, used in calculations later.
			$fullImage = '';
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
				wfTempDir() . '/' . $imgDest . '.' . $ext
			);

			$status = $fileBackend->quickStore( [
				'src' => wfTempDir() . '/' . $imgDest . '.' . $ext,
				'dst' => $dir . '/' . $imgDest . '.' . $ext
			] );

			if ( !$status->isOK() ) {
				throw new Exception(
					$this->msg( 'backend-fail-internal', Status::wrap( $status )->getWikitext() )
				);
			}
		}
	}

	/**
	 * Move the uploaded file from its temporary location to the final
	 * destination. If a previous version of the file exists, move
	 * it into the archive subdirectory.
	 *
	 * @todo If the later save fails, we may have disappeared the original file.
	 *
	 * @param string $saveName
	 * @param string $tempName Full path to the temporary file
	 * @param string $ext File extension
	 *
	 * @return int
	 */
	function saveUploadedFile( $saveName, $tempName, $ext ) {
		$backend = new SocialProfileFileBackend( 'awards' );

		$dest = $backend->getContainerStoragePath();
		$this->mSavedFile = "{$dest}/{$saveName}";

		$this->createThumbnail( $tempName, $ext, $this->gift_id . '_l', 75 );
		$this->createThumbnail( $tempName, $ext, $this->gift_id . '_ml', 50 );
		$this->createThumbnail( $tempName, $ext, $this->gift_id . '_m', 30 );
		$this->createThumbnail( $tempName, $ext, $this->gift_id . '_s', 16 );

		$type = 0;
		if ( $ext == 'JPG' && is_file( wfTempDir() . '/' . $this->gift_id . '_l.jpg' ) ) {
			$type = 2;
		}
		if ( $ext == 'GIF' && is_file( wfTempDir() . '/' . $this->gift_id . '_l.gif' ) ) {
			$type = 1;
		}
		if ( $ext == 'PNG' && is_file( wfTempDir() . '/' . $this->gift_id . '_l.png' ) ) {
			$type = 3;
		}

		if ( $ext != 'JPG' ) {
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_s.jpg' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_s.jpg' );
			}
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_m.jpg' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_m.jpg' );
			}
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_ml.jpg' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_ml.jpg' );
			}
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_l.jpg' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_l.jpg' );
			}
		}
		if ( $ext != 'GIF' ) {
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_s.gif' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_s.gif' );
			}
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_m.gif' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_m.gif' );
			}
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_ml.gif' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_ml.gif' );
			}
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_l.gif' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_l.gif' );
			}
		}
		if ( $ext != 'PNG' ) {
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_s.png' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_s.png' );
			}
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_m.png' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_m.png' );
			}
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_ml.png' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_ml.png' );
			}
			if ( is_file( wfTempDir() . '/' . $this->gift_id . '_l.png' ) ) {
				unlink( wfTempDir() . '/' . $this->gift_id . '_l.png' );
			}
		}

		foreach ( [ 'gif', 'jpg', 'jpeg', 'png' ] as $fileExtension ) {
			if ( $fileExtension === strtolower( $ext ) ) {
				// Our brand new logo; skip over it in order to _not_ delete it, obviously
			} else {
				foreach ( [ 's', 'm', 'ml', 'l' ] as $size ) {
					if ( $backend->fileExists( '', $this->gift_id, $size, $fileExtension ) ) {
						$backend->getFileBackend()->quickDelete( [
							'src' => $backend->getPath(
								'', $this->gift_id, $size, $fileExtension
							)
						] );
					}
				}
			}
		}

		if ( $type === 0 ) {
			throw new FatalError( $this->msg( 'filecopyerror', $tempName, $dest )->escaped() );
		}

		return $type;
	}

	/**
	 * Show some text and linkage on successful upload.
	 *
	 * @param int $status
	 */
	function showSuccess( $status ) {
		$ext = 'jpg';

		$output = '<h2>' . $this->msg( 'g-uploadsuccess' )->escaped() . '</h2>';
		$output .= '<h5>' . $this->msg( 'g-imagesbelow' )->escaped() . '</h5>';
		if ( $status == 1 ) {
			$ext = 'gif';
		}
		if ( $status == 2 ) {
			$ext = 'jpg';
		}
		if ( $status == 3 ) {
			$ext = 'png';
		}

		$output .= '<table cellspacing="0" cellpadding="5">';
		$output .= '<tr><td valign="top" style="color:#666666;font-weight:800">' . $this->msg( 'g-large' )->escaped() . '</td>
		<td>' . ( new UserGiftIcon( $this->gift_id, 'l' ) )->getIconHTML() . '</td></tr>';
		$output .= '<tr><td valign="top" style="color:#666666;font-weight:800">' . $this->msg( 'g-mediumlarge' )->escaped() . '</td>
		<td>' . ( new UserGiftIcon( $this->gift_id, 'ml' ) )->getIconHTML() . '</td></tr>';
		$output .= '<tr><td valign="top" style="color:#666666;font-weight:800">' . $this->msg( 'g-medium' )->escaped() . '</td>
		<td>' . ( new UserGiftIcon( $this->gift_id, 'm' ) )->getIconHTML() . '</td></tr>';
		$output .= '<tr><td valign="top" style="color:#666666;font-weight:800">' . $this->msg( 'g-small' )->escaped() . '</td>
		<td>' . ( new UserGiftIcon( $this->gift_id, 's' ) )->getIconHTML() . '</td></tr>';
		$output .= '<tr><td><input type="button" onclick="javascript:history.go(-1)" value="' . $this->msg( 'g-go-back' )->escaped() . '"></td></tr>';

		$giftManager = SpecialPage::getTitleFor( 'GiftManager' );
		$output .= $this->getLanguage()->pipeList( [
			'<tr><td><a href="' . htmlspecialchars( $giftManager->getFullURL() ) . '">' .
				$this->msg( 'g-back-gift-list' )->escaped() . '</a>&#160;',
			'&#160;<a href="' . htmlspecialchars( $giftManager->getFullURL( 'id=' . $this->gift_id ) ) .
				'">' . $this->msg( 'g-back-edit-gift' )->escaped() . '</a></td></tr>'
		] );
		$output .= '</table>';
		$this->getOutput()->addHTML( $output );
	}

	/**
	 * @param string $error as sanitized HTML
	 */
	function uploadError( $error ) {
		$out = $this->getOutput();
		$sub = $this->msg( 'uploadwarning' )->escaped();
		$out->addHTML( "<h2>{$sub}</h2>\n" );
		$out->addHTML( "<h4 class='error'>{$error}</h4>\n" );
		$out->addHTML( '<br /><input type="button" onclick="javascript:history.go(-1)" value="' . $this->msg( 'g-go-back' )->escaped() . '">' );
	}

	/**
	 * Displays the main upload form, optionally with a highlighted
	 * error message up at the top.
	 *
	 * @param string $msg sanitized HTML
	 */
	function mainUploadForm( $msg = '' ) {
		global $wgUseCopyrightUpload;

		if ( !$this->canUserManage() ) {
			throw new ErrorPageError( 'error', 'badaccess' );
		}

		$out = $this->getOutput();

		if ( $msg != '' ) {
			$sub = htmlspecialchars( $this->msg( 'uploaderror' )->plain() );
			$out->addHTML( "<h2>{$sub}</h2>\n" .
				"<h4 class='error'>{$msg}</h4>\n" );
		}

		$ulb = htmlspecialchars( $this->msg( 'uploadbtn' )->plain() );

		$source = null;

		if ( $wgUseCopyrightUpload ) {
			$source = "
	<td align='right' nowrap='nowrap'>" . htmlspecialchars( $this->msg( 'filestatus' )->plain() ) . "</td>
	<td><input tabindex='3' type='text' name=\"wpUploadCopyStatus\" value=\"" .
			htmlspecialchars( $this->mUploadCopyStatus ) . "\" size='40' /></td>
	</tr><tr>
	<td align='right'>" . htmlspecialchars( $this->msg( 'filesource' )->plain() ) . "</td>
	<td><input tabindex='4' type='text' name='wpUploadSource' value=\"" .
			htmlspecialchars( $this->mUploadSource ) . "\" style='width:100px' /></td>
	";
		}

		$userGiftIcon = new UserGiftIcon( $this->gift_id, 'l' );
		$icon = $userGiftIcon->getIconHTML();
		if ( $icon != '' ) {
			$output = '<table><tr><td style="color:#666666;font-weight:800">' .
				htmlspecialchars( $this->msg( 'g-current-image' )->plain() ) . '</td></tr>';
			$output .= '<tr><td>' . $icon . '</td></tr></table><br />';
			$out->addHTML( $output );
		}

		$token = Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() );

		$out->addHTML( "
	<form id='upload' method='post' enctype='multipart/form-data' action=\"\">
	<table border='0'><tr>

	<td style='color:#666666;font-weight:800'>" . $this->msg( 'g-file-instructions' )->escaped() .
	'<p>' . htmlspecialchars( $this->msg( 'g-choose-file' )->plain() ) . "<br />
	<input tabindex='1' type='file' name='wpUploadFile' id='wpUploadFile' style='width:100px' />
	</td></tr><tr>
	{$source}
	</tr>
	<tr><td>
	{$token}
	<input tabindex='5' type='submit' name='wpUpload' value=\"{$ulb}\" />
	</td></tr></table></form>\n" );
	}

	/**
	 * Verifies that it's ok to include the uploaded file
	 *
	 * @param string $tmpfile The full path of the temporary file to verify
	 * @param string $extension The filename extension that the file is to be served with
	 * @return Status
	 */
	function verify( $tmpfile, $extension ) {
		global $wgDisableUploadScriptChecks, $wgVerifyMimeType, $wgMimeTypeBlacklist;

		# magically determine mime type
		$magic = \MediaWiki\MediaWikiServices::getInstance()->getMimeAnalyzer();
		$mime = $magic->guessMimeType( $tmpfile, false );

		# check mime type, if desired
		if ( $wgVerifyMimeType ) {
			# check mime type against file extension
			if ( !UploadBase::verifyExtension( $mime, $extension ) ) {
				return Status::newFatal( 'filetype-mime-mismatch', $extension, $mime );
			}

			# check mime type blacklist
			if ( isset( $wgMimeTypeBlacklist )
				&& UploadBase::checkFileExtension( $mime, $wgMimeTypeBlacklist ) ) {
				return Status::newFatal( 'badfiletype', htmlspecialchars( $mime ) );
			}
		}

		# check for HTML-ish code and JavaScript
		if ( !$wgDisableUploadScriptChecks ) {
			if ( UploadBase::detectScript( $tmpfile, $mime, $extension ) ) {
				return Status::newFatal( 'uploadscripted' );
			}
		}

		/**
		 * Scan the uploaded file for viruses
		 */
		$virus = UploadBase::detectVirus( $tmpfile );
		if ( $virus ) {
			return Status::newFatal( 'uploadvirus', htmlspecialchars( $virus ) );
		}

		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "{method}: all clear; passing.\n", [
			'method' => __METHOD__
		] );

		return Status::newGood();
	}
}
