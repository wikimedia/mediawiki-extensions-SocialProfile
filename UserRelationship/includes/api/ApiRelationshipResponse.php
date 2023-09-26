<?php

use MediaWiki\MediaWikiServices;

class ApiRelationshipResponse extends ApiBase {
	public function execute() {
		$main = $this->getMain();

		$response = $main->getVal( 'response' );
		$requestId = (int)$main->getVal( 'id' );

		$user = $this->getUser();
		$readOnlyMode = MediaWikiServices::getInstance()->getReadOnlyMode();

		// Don't allow blocked users to send messages and also don't allow message
		// sending when the database is locked for some reason
		if ( $user->getBlock() || $readOnlyMode->isReadOnly() ) {
			return false;
		}

		$out = '';

		$rel = new UserRelationship( $user );
		if ( $rel->verifyRelationshipRequest( $requestId ) == true ) {
			$retVal = SpecialViewRelationshipRequests::doAction( $rel, $requestId, $response );
		} else {
			return false;
		}

		$this->getResult()->addValue( null, 'response', $retVal );

		return true;
	}

	public function needsToken() {
		return 'csrf';
	}

	public function isWriteMode() {
		return true;
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), [
			'response' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			],
			'id' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			]
		] );
	}
}
