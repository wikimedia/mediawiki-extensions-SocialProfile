<?php
/**
 * Forked from the Echo extension's ApiEchoMute.php file (MW 1.43 version), renamed
 * and cleaned up a bit to remove support for titles since that makes no sense in this context.
 * I did not do any of the creative work here and all the credit goes to the original authors of
 * this file (the Echo developers who developed ApiEchoMute.php).
 *
 * @file
 * @date 17 January 2026
 */

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\Options\UserOptionsManager;
use Wikimedia\ParamValidator\ParamValidator;

class ApiUserBoardMute extends ApiBase {

	/** @var CentralIdLookup */
	private $centralIdLookup;

	/** @var LinkBatchFactory */
	private $linkBatchFactory;

	/** @var UserOptionsManager */
	private $userOptionsManager;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CentralIdLookup $centralIdLookup
	 * @param LinkBatchFactory $linkBatchFactory
	 * @param UserOptionsManager $userOptionsManager
	 */
	public function __construct(
		ApiMain $main,
		$action,
		CentralIdLookup $centralIdLookup,
		LinkBatchFactory $linkBatchFactory,
		UserOptionsManager $userOptionsManager
	) {
		parent::__construct( $main, $action );

		$this->centralIdLookup = $centralIdLookup;
		$this->linkBatchFactory = $linkBatchFactory;
		$this->userOptionsManager = $userOptionsManager;
	}

	public function execute() {
		$user = $this->getUser();
		if ( !$user || !$user->isRegistered() ) {
			$this->dieWithError(
				[ 'apierror-mustbeloggedin', $this->msg( 'action-editmyoptions' ) ],
				'notloggedin'
			);
		}

		$this->checkUserRightsAny( 'editmyoptions' );

		$params = $this->extractRequestParams();
		$prefValue = $this->userOptionsManager->getOption( $user, 'user-board-blacklist' );
		$ids = $this->parsePref( $prefValue );
		$targetsToMute = $params['mute'] ?? [];
		$targetsToUnmute = $params['unmute'] ?? [];

		$changed = false;
		$addIds = $this->centralIdLookup->centralIdsFromNames( $targetsToMute, CentralIdLookup::AUDIENCE_PUBLIC );
		foreach ( $addIds as $id ) {
			if ( !in_array( $id, $ids ) ) {
				$ids[] = $id;
				$changed = true;
			}
		}

		$removeIds = $this->centralIdLookup->centralIdsFromNames( $targetsToUnmute, CentralIdLookup::AUDIENCE_PUBLIC );
		foreach ( $removeIds as $id ) {
			$index = array_search( $id, $ids );
			if ( $index !== false ) {
				array_splice( $ids, $index, 1 );
				$changed = true;
			}
		}

		if ( $changed ) {
			$this->userOptionsManager->setOption(
				$user,
				'user-board-blacklist',
				$this->serializePref( $ids )
			);
			$this->userOptionsManager->saveOptions( $user );
		}

		$this->getResult()->addValue( null, $this->getModuleName(), 'success' );
	}

	private function parsePref( $prefValue ) {
		return preg_split( '/\n/', $prefValue, -1, PREG_SPLIT_NO_EMPTY );
	}

	private function serializePref( $ids ) {
		return implode( "\n", $ids );
	}

	public function getAllowedParams( $flags = 0 ) {
		return [
			'mute' => [
				ParamValidator::PARAM_ISMULTI => true,
			],
			'unmute' => [
				ParamValidator::PARAM_ISMULTI => true,
			]
		];
	}

	public function needsToken() {
		return 'csrf';
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}

}
