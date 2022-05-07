<?php

use MediaWiki\MediaWikiServices;

class ApiRelationshipResponse extends ApiBase {
	public function execute() {
		$main = $this->getMain();

		$response = $main->getVal( 'response' );
		$requestId = $main->getVal( 'id' );

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
			$request = $rel->getRequest( $requestId );
			$actorIdFrom = $request[0]['actor_from'];
			$userFrom = User::newFromActorId( $actorIdFrom );
			$rel_type = strtolower( $request[0]['type'] );

			$rel->updateRelationshipRequestStatus( $requestId, intval( $response ) );

			$avatar = new wAvatar( $userFrom->getId(), 'l' );
			$avatar_img = $avatar->getAvatarURL();

			// If the request was accepted, add the relationship.
			if ( $response == 1 ) {
				$rel->addRelationship( $requestId );
			}

			// Build response array
			$retVal = [
				'avatar' => $avatar_img,
				// 'friend' or 'foe'
				'rel_type' => $rel_type,
				'requester' => $userFrom->getName(),
				// action that was done to the request, will be used to build i18n keys
				// in JS in ../resources/js/UserRelationship.js
				'action' => ( $response == 1 ? 'added' : 'reject' )
			];

			// Whatever action was taken, the request's getting deleted either way, that's for sure.
			$rel->deleteRequest( $requestId );
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
