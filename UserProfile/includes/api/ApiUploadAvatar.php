<?php
/**
 * The API version of Special:UploadAvatar.
 *
 * Somewhat based on core ApiUpload and SP's own SpecialUploadAvatar.php, naturally.
 *
 * @file
 * @date 20-21 May 2024
 */

use Wikimedia\ParamValidator\ParamValidator;

/**
 * @ingroup API
 */
class ApiUploadAvatar extends ApiBase {

	/** @var UploadAvatar|UploadAvatarFromUrl */
	protected $mUpload = null;

	/** @var array */
	protected $mParams;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 */
	public function __construct( ApiMain $mainModule, $moduleName ) {
		parent::__construct( $mainModule, $moduleName );
	}

	/** @inheritDoc */
	public function execute() {
		global $wgAvatarKey, $wgUserProfileDisplay, $wgUploadAvatarInRecentChanges;

		// Check whether avatars (and uploading them) are enabled
		# if ( !UploadBase::isEnabled() ) {
		if ( $wgUserProfileDisplay['avatar'] === false ) {
			$this->dieWithError( 'uploaddisabled' );
		}

		$user = $this->getUser();

		// Parameter handling
		$this->mParams = $this->extractRequestParams();
		$request = $this->getMain()->getRequest();

		// Add the uploaded file to the params array
		$this->mParams['file'] = $request->getFileName( 'file' );

		if ( isset( $this->mParams['file'] ) ) {
			$this->mUpload = new UploadAvatar();
			$this->mUpload->initialize(
				// Pseudorandomness here too, we don't need (nor want) a real file name
				rand() . microtime( true ) . rand(),
				$request->getUpload( 'file' )
			);
		} elseif ( isset( $this->mParams['url'] ) ) {
			// Make sure upload by URL is enabled:
			if ( !UploadFromUrl::isEnabled() ) {
				$this->dieWithError( 'copyuploaddisabled' );
			}

			if ( !UploadFromUrl::isAllowedHost( $this->mParams['url'] ) ) {
				$this->dieWithError( 'apierror-copyuploadbaddomain' );
			}

			if ( !UploadFromUrl::isAllowedUrl( $this->mParams['url'] ) ) {
				$this->dieWithError( 'apierror-copyuploadbadurl' );
			}

			$this->mUpload = new UploadAvatarFromUrl;
			// Some pseudorandomness as the "file name"; we don't care because
			// we're not uploading a "regular" MW file here, so file name can be
			// anything because it doesn't get used anyway.
			$this->mUpload->initialize(
				rand() . microtime( true ) . rand(),
				$this->mParams['url']
			);
		}

		if ( !isset( $this->mUpload ) ) {
			$this->dieDebug( __METHOD__, 'No upload module set' );
		}

		// First check permission to upload
		$this->checkPermissions( $user );

		// Fetch the file (usually a no-op except for upload-by-URL)
		/** @var Status $status */
		$status = $this->mUpload->fetchFile();
		if ( !$status->isGood() ) {
			$this->dieStatus( $status );
		}

		// Check the uploaded file
		// $this->verifyUpload();

		// Get the result based on the current upload context:
		// Check throttle after we've handled warnings
		if ( UploadBase::isThrottled( $user ) ) {
			$this->dieWithError( 'apierror-ratelimited' );
		}

		// This is the most common case -- a normal upload with no warnings
		$status = $this->mUpload->performUpload( '', '', false, $user );

		$result = [];
		if ( $status->isGood() ) {
			$backend = new SocialProfileFileBackend( 'avatars' );
			$uid = $user->getId();
			$ext = $this->mUpload->mExtension;
			// The cache-busting variable
			$ts = rand();

			$result = [
				'result' => 'Success',
				'url-small' => $backend->getFileHttpUrl( $wgAvatarKey . '_', $uid, 's', $ext ) . '?ts=' . $ts,
				'url-medium' => $backend->getFileHttpUrl( $wgAvatarKey . '_', $uid, 'm', $ext ) . '?ts=' . $ts,
				'url-medium-large' => $backend->getFileHttpUrl( $wgAvatarKey . '_', $uid, 'ml', $ext ) . '?ts=' . $ts,
				'url-large' => $backend->getFileHttpUrl( $wgAvatarKey . '_', $uid, 'l', $ext ) . '?ts=' . $ts
			];

			// Run a hook on avatar change
			$this->getHookContainer()->run( 'NewAvatarUploaded', [ $user ] );

			// Log the change
			$log = new LogPage( 'avatar' );
			if ( !$wgUploadAvatarInRecentChanges ) {
				$log->updateRecentChanges = false;
			}
			$log->addEntry(
				'avatar',
				$user->getUserPage(),
				$this->msg( 'user-profile-picture-log-entry' )->inContentLanguage()->text(),
				[],
				$user
			);
		} else {
			$sv = StatusValue::newGood();
			foreach ( $status->getErrors() as $error ) {
				$msg = ApiMessage::create( $error );
				$msg->setApiData( $msg->getApiData() );
				$sv->fatal( $msg );
			}
			$this->dieStatus( $sv );
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );

		// Cleanup any temporary mess
		$this->mUpload->cleanupTempFile();
	}

	/**
	 * Checks that the user has permissions to perform this upload.
	 * Dies with usage message on inadequate permissions.
	 *
	 * @param User $user The user to check.
	 */
	protected function checkPermissions( $user ) {
		// Check whether the user has the appropriate permissions to upload anyway
		$permission = $this->mUpload->isAllowed( $user );

		if ( $permission !== true ) {
			if ( !$user->isRegistered() ) {
				$this->dieWithError( [ 'apierror-mustbeloggedin', $this->msg( 'action-upload' ) ] );
			}

			$this->dieStatus( User::newFatalPermissionDeniedStatus( $permission ) );
		}

		// Check blocks
		if ( $user->isBlockedFromUpload() ) {
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Block is checked and not null
			$this->dieBlocked( $user->getBlock() );
		}

		// Global blocks
		$block = $user->getBlock();
		if ( $block ) {
			$this->dieBlocked( $block );
		}
	}

	/** @inheritDoc */
	public function mustBePosted() {
		return true;
	}

	/** @inheritDoc */
	public function isWriteMode() {
		return true;
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return [
			'file' => [
				ParamValidator::PARAM_TYPE => 'upload',
			],
			'url' => null,
		];
	}

	/** @inheritDoc */
	public function needsToken() {
		return 'csrf';
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=uploadavatar' .
				'&url=https%3A//upload.wikimedia.org/wikipedia/en/b/bc/Wiki.png&token=123ABC'
				=> 'apihelp-uploadavatar-example-url'
		];
	}

	/** @inheritDoc */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:SocialProfile/API/Avatar_upload';
	}
}
