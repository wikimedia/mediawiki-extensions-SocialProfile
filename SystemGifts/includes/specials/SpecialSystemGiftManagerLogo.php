<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Shell\Shell;

/**
 * A special page to upload images for system gifts (awards).
 * This is mostly copied from an old version of Special:Upload and changed a
 * bit.
 *
 * @file
 * @ingroup Extensions
 */
class SystemGiftManagerLogo extends UnlistedSpecialPage {

	/** @var UploadBase */
	public $mUploadFile;
	/** @var string|null */
	public $mUploadDescription;
	/** @var bool|null */
	public $mIgnoreWarning;
	/** @var string|null */
	public $mUploadSaveName;
	/** @var string|null */
	public $mUploadTempName;
	/** @var int|null */
	public $mUploadSize;
	/** @var string|null */
	public $mUploadOldVersion;
	/** @var string|null */
	public $mUploadCopyStatus;
	/** @var string|null */
	public $mUploadSource;
	/** @var bool|null */
	public $mReUpload;
	/** @var string|null */
	public $mAction;
	/** @var bool */
	public $mUpload;
	/** @var string|null */
	public $mOname;
	/** @var string|null */
	public $mSessionKey;
	/** @var bool */
	public $mStashed;
	/** @var string|null */
	public $mDestFile;
	/** @var bool|null */
	public $mSavedFile;
	/** @var bool|null */
	public $mWatchthis;
	/** @var bool|null */
	public $mTokenOk;
	/** @var string|null */
	public $awardsUploadDirectory;
	/** @var string[]|null */
	public $fileExtensions;
	/** @var int|null */
	public $gift_id;

	public function __construct() {
		parent::__construct( 'SystemGiftManagerLogo', 'awardsmanage' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// make sure user has the correct permissions
		$this->checkPermissions();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the robot policies, etc.
		$out->setArticleRelated( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.special.systemgiftmanagerlogo.css' );

		$this->gift_id = $this->getRequest()->getInt( 'gift_id' );
		$this->initLogo();
		$this->executeLogo();
	}

	function initLogo() {
		$this->fileExtensions = [ 'gif', 'jpg', 'jpeg', 'png' ];

		$request = $this->getRequest();
		if ( !$request->wasPosted() ) {
			# GET requests just give the main form; no data except wpDestfile.
			return;
		}
		$this->gift_id = $request->getInt( 'gift_id' );
		$this->mIgnoreWarning = $request->getCheck( 'wpIgnoreWarning' );
		$this->mReUpload = $request->getCheck( 'wpReUpload' );
		$this->mUpload = $request->getCheck( 'wpUpload' );

		$this->mUploadDescription = $request->getText( 'wpUploadDescription' );
		$this->mUploadCopyStatus = $request->getText( 'wpUploadCopyStatus' );
		$this->mUploadSource = $request->getText( 'wpUploadSource' );
		$this->mWatchthis = $request->getBool( 'wpWatchthis' );

		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "{method}: watchthis is: '{watchthis}'\n", [
			'method' => __METHOD__,
			'watchthis' => $this->mWatchthis
		] );

		$this->mAction = $request->getVal( 'action' );
		$this->mSessionKey = $request->getInt( 'wpSessionKey' );
		if ( !empty( $this->mSessionKey ) && isset( $_SESSION['wsUploadData'][$this->mSessionKey] ) ) {
			/**
			 * Confirming a temporarily stashed upload.
			 * We don't want path names to be forged, so we keep
			 * them in the session on the server and just give
			 * an opaque key to the user agent.
			 */
			$data = $_SESSION['wsUploadData'][$this->mSessionKey];
			$this->mUploadTempName = $data['mUploadTempName'];
			$this->mUploadSize = $data['mUploadSize'];
			$this->mOname = $data['mOname'];
			$this->mStashed = true;
		} else {
			/**
			 * Check for a newly uploaded file.
			 */
			$this->mUploadTempName = $request->getFileTempname( 'wpUploadFile' );
			$file = new WebRequestUpload( $request, 'wpUploadFile' );
			$this->mUploadSize = $file->getSize();
			$this->mOname	= $request->getFileName( 'wpUploadFile' );
			$this->mSessionKey	= false;
			$this->mStashed	= false;
		}

		// If it was posted check for the token (no remote POST'ing with user credentials)
		$token = $request->getVal( 'wpEditToken' );
		$this->mTokenOk = $this->getUser()->matchEditToken( $token );
	}

	/**
	 * Start doing stuff
	 */
	public function executeLogo() {
		global $wgEnableUploads, $wgUploadDirectory;

		$this->awardsUploadDirectory = $wgUploadDirectory . '/awards';

		/** Show an error message if file upload is disabled */
		if ( !$wgEnableUploads ) {
			$this->getOutput()->addWikiMsg( 'uploaddisabled' );
			return;
		}

		/** Check if the user is allowed to upload files */
		if ( !$this->getUser()->isAllowed( 'upload' ) ) {
			throw new ErrorPageError( 'uploadnologin', 'uploadnologintext' );
		}

		/** Check if the image directory is writeable, this is a common mistake */
		if ( !is_writable( $wgUploadDirectory ) ) {
			$this->getOutput()->addWikiMsg( 'upload_directory_read_only', $wgUploadDirectory );
			return;
		}

		if ( $this->mReUpload ) {
			$this->unsaveUploadedFile();
			$this->mainUploadForm();
		} elseif ( $this->mAction == 'submit' || $this->mUpload ) {
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
			return $this->mainUploadForm( '<li>' . $this->msg( 'emptyfile' )->escaped() . '</li>' );
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
		list( $partname, $ext ) = UploadBase::splitExtensions( $basename );
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
		if ( !$this->mStashed ) {
			// @phan-suppress-next-line SecurityCheck-PathTraversal False positive
			$veri = $this->verify( $this->mUploadTempName, $finalExt );

			if ( !$veri->isGood() ) {
				return $this->uploadError( $this->getOutput()->parseAsInterface( $veri->getWikiText() ) );
			}
		}

		/**
		 * Check for non-fatal conditions
		 */
		if ( !$this->mIgnoreWarning ) {
			$warning = '';

			global $wgCheckFileExtensions;
			if ( $wgCheckFileExtensions ) {
				if ( !UploadBase::checkFileExtension( $finalExt, $this->fileExtensions ) ) {
					$warning .= '<li>' . $this->msg( 'filetype-banned', htmlspecialchars( $fullExt ) )->escaped() . '</li>';
				}
			}

			global $wgUploadSizeWarning;
			if ( $wgUploadSizeWarning && ( $this->mUploadSize > $wgUploadSizeWarning ) ) {
				$lang = $this->getLanguage();
				$wsize = $lang->formatSize( $wgUploadSizeWarning );
				$asize = $lang->formatSize( $this->mUploadSize );
				$warning .= '<li>' . $this->msg( 'large-file', $wsize, $asize )->escaped() . '</li>';
			}

			if ( $this->mUploadSize == 0 ) {
				$warning .= '<li>' . $this->msg( 'emptyfile' )->escaped() . '</li>';
			}

			if ( $warning != '' ) {
				/**
				 * Stash the file in a temporary location; the user can choose
				 * to let it through and we'll complete the upload then.
				 */
				return $this->uploadWarning( $warning );
			}
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
	 * Create the award image thumbnails, either with ImageMagick or GD.
	 *
	 * @param string $imageSrc Path to the temporary file
	 * @param string $ext File extension (gif, jpg, png); de facto unused when using GD
	 * @param string $imgDest <award ID>_<size code>, e.g. 20_l for a large image for award ID #20
	 * @param int $thumbWidth Thumbnail image width in pixels
	 */
	function createThumbnail( $imageSrc, $ext, $imgDest, $thumbWidth ) {
		global $wgUseImageMagick, $wgImageMagickConvertCommand;

		list( $origWidth, $origHeight, $typeCode ) = getimagesize( $imageSrc );

		if ( $wgUseImageMagick ) { // ImageMagick is enabled
			if ( $origWidth < $thumbWidth ) {
				$thumbWidth = $origWidth;
			}
			$thumbHeight = ( $thumbWidth * $origHeight / $origWidth );
			if ( $thumbHeight < $thumbWidth ) {
				$border = ' -bordercolor white -border 0x' . ( ( $thumbWidth - $thumbHeight ) / 2 );
			}
			if ( $typeCode == 2 ) {
				exec(
					Shell::escape( $wgImageMagickConvertCommand ) . ' -size ' . $thumbWidth . 'x' .
					$thumbWidth . ' -resize ' . $thumbWidth . '  -quality 100 ' .
					$border . ' ' . Shell::escape( $imageSrc ) . ' ' .
					$this->awardsUploadDirectory . '/sg_' . $imgDest . '.jpg'
				);
			}
			if ( $typeCode == 1 ) {
				exec(
					Shell::escape( $wgImageMagickConvertCommand ) . ' -size ' . $thumbWidth . 'x' .
					$thumbWidth . ' -resize ' . $thumbWidth . ' ' . Shell::escape( $imageSrc ) .
					' ' . $border . ' ' .
					$this->awardsUploadDirectory . '/sg_' . $imgDest . '.gif'
				);
			}
			if ( $typeCode == 3 ) {
				exec(
					Shell::escape( $wgImageMagickConvertCommand ) . ' -size ' . $thumbWidth . 'x' .
					$thumbWidth . ' -resize ' . $thumbWidth . ' ' . Shell::escape( $imageSrc ) .
					' ' . $this->awardsUploadDirectory . '/sg_' . $imgDest . '.png'
				);
			}
		} else { // ImageMagick is not enabled, so fall back to PHP's GD library
			// Get the image size, used in calculations later.
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
				$this->awardsUploadDirectory . '/sg_' . $imgDest . '.' . $ext
			);
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
	 * @param string $ext File extension (jpg, gif or png)
	 *
	 * @return int
	 */
	function saveUploadedFile( $saveName, $tempName, $ext ) {
		$dest = $this->awardsUploadDirectory;

		$this->mSavedFile = "{$dest}/{$saveName}";

		$this->createThumbnail( $tempName, $ext, $this->gift_id . '_l', 75 );
		$this->createThumbnail( $tempName, $ext, $this->gift_id . '_ml', 50 );
		$this->createThumbnail( $tempName, $ext, $this->gift_id . '_m', 30 );
		$this->createThumbnail( $tempName, $ext, $this->gift_id . '_s', 16 );

		if ( $ext == 'JPG' && is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_l.jpg' ) ) {
			$type = 2;
		}
		if ( $ext == 'GIF' && is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_l.gif' ) ) {
			$type = 1;
		}
		if ( $ext == 'PNG' && is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_l.png' ) ) {
			$type = 3;
		}

		if ( $ext != 'JPG' ) {
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_s.jpg' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_s.jpg' );
			}
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_m.jpg' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_m.jpg' );
			}
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_ml.jpg' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_ml.jpg' );
			}
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_l.jpg' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_l.jpg' );
			}
		}
		if ( $ext != 'GIF' ) {
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_s.gif' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_s.gif' );
			}
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_m.gif' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_m.gif' );
			}
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_ml.gif' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_ml.gif' );
			}
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_l.gif' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_l.gif' );
			}
		}
		if ( $ext != 'PNG' ) {
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_s.png' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_s.png' );
			}
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_m.png' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_m.png' );
			}
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_ml.png' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_ml.png' );
			}
			if ( is_file( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_l.png' ) ) {
				unlink( $this->awardsUploadDirectory . '/sg_' . $this->gift_id . '_l.png' );
			}
		}

		if ( $type < 0 ) {
			throw new FatalError( $this->msg( 'filecopyerror', $tempName, /*$stash*/'' )->escaped() );
		}

		return $type;
	}

	/**
	 * Stash a file in a temporary directory for later processing
	 * after the user has confirmed it.
	 *
	 * If the user doesn't explicitly cancel or accept, these files
	 * can accumulate in the temp directory.
	 *
	 * @param string $saveName The destination filename
	 * @param string $tempName The source temporary file to save
	 * @return string Full path the stashed file, or false on failure
	 */
	function saveTempUploadedFile( $saveName, $tempName ) {
		$archive = wfImageArchiveDir( $saveName, 'temp' );
		$stash = $archive . '/' . gmdate( 'YmdHis' ) . '!' . $saveName;

		if ( !move_uploaded_file( $tempName, $stash ) ) {
			throw new FatalError( $this->msg( 'filecopyerror', $tempName, $stash )->escaped() );
		}

		return $stash;
	}

	/**
	 * Stash a file in a temporary directory for later processing,
	 * and save the necessary descriptive info into the session.
	 * Returns a key value which will be passed through a form
	 * to pick up the path info on a later invocation.
	 *
	 * @return int
	 */
	function stashSession() {
		$stash = $this->saveTempUploadedFile(
			$this->mUploadSaveName, $this->mUploadTempName );

		if ( !$stash ) {
			# Couldn't save the file.
			return false;
		}

		$key = mt_rand( 0, 0x7fffffff );
		$_SESSION['wsUploadData'][$key] = [
			'mUploadTempName' => $stash,
			'mUploadSize' => $this->mUploadSize,
			'mOname' => $this->mOname
		];
		return $key;
	}

	/**
	 * Remove a temporarily kept file stashed by saveTempUploadedFile().
	 */
	function unsaveUploadedFile() {
		Wikimedia\suppressWarnings();
		$success = unlink( $this->mUploadTempName );
		Wikimedia\restoreWarnings();
		if ( !$success ) {
			throw new FatalError( $this->msg( 'filedeleteerror', $this->mUploadTempName )->escaped() );
		}
	}

	/**
	 * Show some text and linkage on successful upload.
	 *
	 * @param int $status
	 */
	function showSuccess( $status ) {
		global $wgUploadBaseUrl, $wgUploadPath;

		$uploadPath = $wgUploadBaseUrl ? $wgUploadBaseUrl . $wgUploadPath : $wgUploadPath;

		$ext = 'jpg';
		$ts = rand();

		$output = '<h2>' . $this->msg( 'ga-uploadsuccess' )->escaped() . '</h2>';
		$output .= '<h5>' . $this->msg( 'ga-imagesbelow' )->escaped() . '</h5>';
		if ( $status == 1 ) {
			$ext = 'gif';
		}
		if ( $status == 2 ) {
			$ext = 'jpg';
		}
		if ( $status == 3 ) {
			$ext = 'png';
		}

		$output .= '<table class="ga-upload-success-page">
		<tr>
			<td class="title-cell" valign="top">' . $this->msg( 'ga-large' )->escaped() . '</td>
			<td><img src="' . $uploadPath . '/awards/sg_' . $this->gift_id . '_l.' . $ext . '?ts=' . $ts . '" alt="" /></td>
		</tr>
		<tr>
			<td class="title-cell" valign="top">' . $this->msg( 'ga-mediumlarge' )->escaped() . '</td>
			<td><img src="' . $uploadPath . '/awards/sg_' . $this->gift_id . '_ml.' . $ext . '?ts=' . $ts . '" alt="" /></td>
		</tr>
		<tr>
			<td class="title-cell" valign="top">' . $this->msg( 'ga-medium' )->escaped() . '</td>
			<td><img src="' . $uploadPath . '/awards/sg_' . $this->gift_id . '_m.' . $ext . '?ts=' . $ts . '" alt="" /></td>
		</tr>
		<tr>
			<td class="title-cell" valign="top">' . $this->msg( 'ga-small' )->escaped() . '</td>
			<td><img src="' . $uploadPath . '/awards/sg_' . $this->gift_id . '_s.' . $ext . '?ts=' . $ts . '" alt="" /></td>
		</tr>
		<tr>
			<td>
				<input type="button" onclick="javascript:history.go(-1)" value="' . $this->msg( 'ga-goback' )->escaped() . '" />
			</td>
		</tr>';

		$systemGiftManager = SpecialPage::getTitleFor( 'SystemGiftManager' );
		$output .= $this->getLanguage()->pipeList( [
			'<tr><td><a href="' . htmlspecialchars( $systemGiftManager->getFullURL() ) . '">' .
				$this->msg( 'ga-back-gift-list' )->escaped() . '</a>&#160;',
			'&#160;<a href="' . htmlspecialchars( $systemGiftManager->getFullURL( 'id=' . $this->gift_id ) ) . '">' .
				$this->msg( 'ga-back-edit-gift' )->escaped() . '</a></td></tr>'
		] );
		$output .= '</table>';
		$this->getOutput()->addHTML( $output );
	}

	/**
	 * @param string $error HTML
	 */
	function uploadError( $error ) {
		$out = $this->getOutput();
		$sub = $this->msg( 'uploadwarning' )->escaped();
		$out->addHTML( "<h2>{$sub}</h2>\n" );
		$out->addHTML( "<h4 class='error'>{$error}</h4>\n" );
		$out->addHTML( '<br /><input type="button" onclick="javascript:history.go(-1)" value="' . $this->msg( 'ga-goback' )->escaped() . '">' );
	}

	/**
	 * There's something wrong with this file, not enough to reject it
	 * totally but we require manual intervention to save it for real.
	 * Stash it away, then present a form asking to confirm or cancel.
	 *
	 * @param string $warning HTML
	 */
	function uploadWarning( $warning ) {
		global $wgUseCopyrightUpload;

		$this->mSessionKey = $this->stashSession();
		if ( !$this->mSessionKey ) {
			# Couldn't save file; an error has been displayed so let's go.
			return;
		}

		$out = $this->getOutput();
		$sub = $this->msg( 'uploadwarning' )->escaped();
		$out->addHTML( "<h2>{$sub}</h2>\n" );
		$out->addHTML( "<ul class='warning'>{$warning}</ul><br />\n" );

		$titleObj = SpecialPage::getTitleFor( 'Upload' );
		$action = htmlspecialchars( $titleObj->getLocalURL( 'action=submit' ) );

		if ( $wgUseCopyrightUpload ) {
			$copyright = "
	<input type='hidden' name='wpUploadCopyStatus' value=\"" . htmlspecialchars( $this->mUploadCopyStatus ) . "\" />
	<input type='hidden' name='wpUploadSource' value=\"" . htmlspecialchars( $this->mUploadSource ) . "\" />
	";
		} else {
			$copyright = '';
		}

		$out->addHTML( "
	<form id='uploadwarning' method='post' enctype='multipart/form-data' action='$action'>
		<input type='hidden' name='gift_id' value=\"" . ( $this->gift_id ) . "\" />
		<input type='hidden' name='wpIgnoreWarning' value='1' />
		<input type='hidden' name='wpSessionKey' value=\"" . htmlspecialchars( $this->mSessionKey ) . "\" />
		<input type='hidden' name='wpUploadDescription' value=\"" . htmlspecialchars( $this->mUploadDescription ) . "\" />
		<input type='hidden' name='wpDestFile' value=\"" . htmlspecialchars( $this->mDestFile ) . "\" />
		<input type='hidden' name='wpWatchthis' value=\"" . htmlspecialchars( intval( $this->mWatchthis ) ) . "\" />
	{$copyright}
	<table>
		<tr>

			<tr>
				<td align='right'>
					<input tabindex='2' type='button' onclick=javascript:history.go(-1) value='" . $this->msg( 'ga-goback' )->escaped() . "' />
				</td>

			</tr>
		</tr>
	</table></form>\n" );
	}

	/**
	 * Displays the main upload form, optionally with a highlighted
	 * error message up at the top.
	 *
	 * @param string $msg HTML
	 */
	function mainUploadForm( $msg = '' ) {
		global $wgUseCopyrightUpload;

		$out = $this->getOutput();
		if ( $msg != '' ) {
			$sub = $this->msg( 'uploaderror' )->escaped();
			$out->addHTML( "<h2>{$sub}</h2>\n" .
				"<h4 class='error'>{$msg}</h4>\n" );
		}

		$ulb = $this->msg( 'uploadbtn' )->escaped();

		$source = null;

		if ( $wgUseCopyrightUpload ) {
			$source = "
	<td align='right' nowrap='nowrap'>" . $this->msg( 'filestatus' )->escaped() . "</td>
	<td><input tabindex='3' type='text' name=\"wpUploadCopyStatus\" value=\"" .
	htmlspecialchars( $this->mUploadCopyStatus ) . "\" size='40' /></td>
	</tr><tr>
	<td align='right'>" . $this->msg( 'filesource' )->escaped() . "</td>
	<td><input tabindex='4' type='text' name='wpUploadSource' id='wpUploadSource' value=\"" .
	htmlspecialchars( $this->mUploadSource ) . "\" /></td>
	";
		}

		$systemGiftIcon = new SystemGiftIcon( $this->gift_id, 'l' );

		$output = '<table>
			<tr>
				<td class="title-cell">' .
					$this->msg( 'ga-currentimage' )->escaped() .
				'</td>
			</tr>
			<tr>
				<td>' .
					$systemGiftIcon->getIconHTML() .
				'</td>
			</tr>
		</table>
		<br />';
		$out->addHTML( $output );

		$token = Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() );

		$out->addHTML( '
	<form id="upload" method="post" enctype="multipart/form-data" action="">
	<table>
		<tr>
			<td class="title-cell">' .
				$this->msg( 'ga-file-instructions' )->escaped() . $this->msg( 'ga-choosefile' )->escaped() . '<br />
				<input tabindex="1" type="file" name="wpUploadFile" id="wpUploadFile" />
			</td>
		</tr>
		<tr>' . $source . '</tr>
		<tr>
			<td>' . $token .
				'<input tabindex="5" type="submit" name="wpUpload" value="' . $ulb . '" />
			</td>
		</tr>
		</table></form>' . "\n"
		);
	}

	/**
	 * Verifies that it's ok to include the uploaded file
	 *
	 * @param string $tmpfile The full path opf the temporary file to verify
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
