<?php
/**
 * API module for removing a user's avatar.
 *
 * @file
 * @ingroup API
 * @date 19 May 2024
 */

use MediaWiki\ParamValidator\TypeDef\UserDef;
use Wikimedia\ParamValidator\ParamValidator;

class ApiRemoveAvatar extends ApiBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * Main entry point
	 */
	public function execute() {
		global $wgUploadAvatarInRecentChanges;

		$params = $this->extractRequestParams();

		$user = $this->getUser();
		if ( $user->isAnon() ) {
			// Boo, go away!
			$this->dieWithError( 'apierror-mustbeloggedin-generic', 'login-required' );
		}

		$isPrivileged = $user->isAllowed( 'avatarremove' );

		$userToOperateOn = $user;
		$reason = '';

		if ( $isPrivileged ) {
			$userToOperateOn = User::newFromName( $params['user'] );
			if ( isset( $params['reason'] ) && $params['reason'] ) {
				$reason = $params['reason'];
			}
		}

		$user_id = $userToOperateOn->getId();
		$avatar = new wAvatar( $user_id, 'l' );
		if ( $avatar->isDefault() ) {
			$this->dieWithError( 'apierror-avatar-is-default', 'avatar-is-default' );
		}

		RemoveAvatar::deleteImage( $user_id, 's' );
		RemoveAvatar::deleteImage( $user_id, 'm' );
		RemoveAvatar::deleteImage( $user_id, 'l' );
		RemoveAvatar::deleteImage( $user_id, 'ml' );

		$log = new LogPage( 'avatar' );

		if ( !$wgUploadAvatarInRecentChanges ) {
			$log->updateRecentChanges = false;
		}

		if ( $isPrivileged && $reason !== '' ) {
			$log->addEntry(
				'avatar',
				$user->getUserPage(),
				$this->msg(
					'user-profile-picture-log-delete-entry-with-reason',
					$userToOperateOn->getName(),
					$reason
				)->inContentLanguage()->text(),
				[],
				$user
			);
		} else {
			$log->addEntry(
				'avatar',
				$user->getUserPage(),
				$this->msg( 'user-profile-picture-log-delete-entry', $userToOperateOn->getName() )
					->inContentLanguage()->text(),
				[],
				$user
			);
		}

		// Let the user know that everything went well.
		$this->getResult()->addValue( null, $this->getModuleName(), [ 'status' => 'OK' ] );
	}

	/** @inheritDoc */
	public function needsToken() {
		return 'csrf';
	}

	/** @inheritDoc */
	public function isWriteMode() {
		return true;
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return [
			'user' => [
				ParamValidator::PARAM_TYPE => 'user',
				UserDef::PARAM_ALLOWED_USER_TYPES => [ 'name' ],
				ApiBase::PARAM_REQUIRED => true
			],
			'reason' => [
				ParamValidator::PARAM_TYPE => 'string'
			]
		];
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=removeavatar&user=Ashley&token=123ABC' => 'apihelp-removeavatar-example-1',
			'action=removeavatar&user=Elektra&token=123ABC&reason=Blatant copyvio' => 'apihelp-removeavatar-example-2',
		];
	}

	/** @inheritDoc */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:SocialProfile/API/Avatar_removal';
	}
}
