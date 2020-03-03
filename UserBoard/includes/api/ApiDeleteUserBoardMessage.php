<?php

use MediaWiki\MediaWikiServices;

class ApiDeleteUserBoardMessage extends ApiBase {
	public function execute() {
		$main = $this->getMain();
		$user = $this->getUser();
		$readOnlyMode = MediaWikiServices::getInstance()->getReadOnlyMode();

		$messageId = $main->getVal( 'id' );

		// Don't allow deleting messages when the database is locked for some reason
		if ( $readOnlyMode->isReadOnly() ) {
			$this->getResult()->addValue( null, 'result', 'You cannot delete messages right now.' );
			return true;
		}

		$b = new UserBoard( $user );
		if (
			$b->doesUserOwnMessage( $user, $messageId ) ||
			$user->isAllowed( 'userboard-delete' )
		) {
			$b->deleteMessage( $messageId );
		}

		$this->getResult()->addValue( null, 'result', 'ok' );

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
			'id' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			]
		] );
	}
}
