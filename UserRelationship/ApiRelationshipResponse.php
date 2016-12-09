<?php

class ApiRelationshipResponse extends ApiBase {
	public function execute() {
		$main = $this->getMain();

		$response = $main->getVal( 'response' );
		$requestId = $main->getVal( 'id' );

		$user = $this->getUser();

		// Don't allow blocked users to send messages and also don't allow message
		// sending when the database is locked for some reason
		if ( $user->isBlocked() || wfReadOnly() ) {
			return false;
		}

		$out = '';

		$rel = new UserRelationship( $user->getName() );
		if ( $rel->verifyRelationshipRequest( $requestId ) == true ) {
			$request = $rel->getRequest( $requestId );
			$user_name_from = $request[0]['user_name_from'];
			$user_id_from = User::idFromName( $user_name_from );
			$rel_type = strtolower( $request[0]['type'] );

			$rel->updateRelationshipRequestStatus( $requestId, intval( $response ) );

			$avatar = new wAvatar( $user_id_from, 'l' );
			$avatar_img = $avatar->getAvatarURL();

			if ( $response == 1 ) {
				$rel->addRelationship( $requestId );
				$out .= "<div class=\"relationship-action red-text\">
					{$avatar_img}" .
						wfMessage( "ur-requests-added-message-{$rel_type}", $user_name_from )->escaped() .
					'<div class="visualClear"></div>
				</div>';
			} else {
				$out .= "<div class=\"relationship-action red-text\">
					{$avatar_img}" .
						wfMessage( "ur-requests-reject-message-{$rel_type}", $user_name_from )->escaped() .
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

	public function getDescription() {
		return 'Responds to user relationship (friend/foe) requests.';
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'response' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			),
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			)
		) );
	}

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'response' => 'Response to the relationship request; 1 to accept, -1 to reject.',
			'id' => 'Unique identifier of the relationship request'
		) );
	}

	public function getExamplesMessages() {
		return array();
	}
}
