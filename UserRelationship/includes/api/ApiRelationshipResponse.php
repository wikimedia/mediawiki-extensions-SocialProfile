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
		if ( $user->isBlocked() || $readOnlyMode->isReadOnly() ) {
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

			// @todo FIXME: super legacy code is legacy. This API module should return a more structured,
			// programmatical response and leave the HTML up to the caller(s).
			// e.g. [ 'avatar' => avatar URL here, 'rel_type' => $rel_type, 'userFrom' => $userFrom ]
			// or something like that...
			if ( $response == 1 ) {
				$rel->addRelationship( $requestId );
				$out .= "<div class=\"relationship-action red-text\">
					{$avatar_img}" .
						wfMessage( "ur-requests-added-message-{$rel_type}", $userFrom->getName() )->escaped() .
					'<div class="visualClear"></div>
				</div>';
			} else {
				$out .= "<div class=\"relationship-action red-text\">
					{$avatar_img}" .
						wfMessage( "ur-requests-reject-message-{$rel_type}", $userFrom->getName() )->escaped() .
					'<div class="visualClear"></div>
				</div>';
			}

			$rel->deleteRequest( $requestId );
		} else {
			return false;
		}

		$this->getResult()->addValue( null, 'html', $out );

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
