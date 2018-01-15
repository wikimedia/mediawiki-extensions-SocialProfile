<?php
/**
 * UserSystemMessage class
 * Used to send "You have advanced to level [fill in this]" messages
 * to users when User Levels is activated ($wgUserLevels is defined)
 *
 * @file
 * @ingroup Extensions
 */
class UserSystemMessage {

	/**
	 * Adds the message into the database
	 *
	 * @param mixed $userName The name of the user who's receiving the message
	 * @param int $type 0 by default
	 * @param string $message Message to be sent out
	 */
	public function addMessage( $userName, $type = 0, $message ) {
		$userId = User::idFromName( $userName );
		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'user_system_messages',
			array(
				'um_user_id' => $userId,
				'um_user_name' => $userName,
				'um_type' => $type,
				'um_message' => $message,
				'um_date' => date( 'Y-m-d H:i:s' ),
			), __METHOD__
		);
	}

	/**
	 * Deletes a message from the user_system_messages table in the database
	 *
	 * @param int $um_id Internal ID number of the message to delete
	 */
	static function deleteMessage( $um_id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'user_system_messages',
			array( 'um_id' => $um_id ),
			__METHOD__
		);
	}

	/**
	 * Gets a list of system messages for the current user from the database
	 *
	 * @param int $type 0 by default
	 * @param int $limit LIMIT for database queries, 0 by default
	 * @param int $page 0 by default
	 * @return array
	 */
	public function getMessageList( $type, $limit = 0, $page = 0 ) {
		$dbr = wfGetDB( DB_REPLICA );

		if ( $limit > 0 ) {
			$limitvalue = 0;
			if ( $page ) {
				$limitvalue = $page * $limit - ( $limit );
			}
			$params['LIMIT'] = $limit;
			$params['OFFSET'] = $limitvalue;
		}

		$params['ORDER BY'] = 'ug_id DESC';
		$res = $dbr->select(
			array( 'user_gift', 'gift' ),
			array(
				'ug_id', 'ug_user_id_from', 'ug_user_name_from', 'ug_gift_id',
				'ug_date', 'ug_status', 'gift_name', 'gift_description',
				'gift_given_count'
			),
			array( "ug_user_id_to = {$this->user_id}" ),
			__METHOD__,
			$params,
			array( 'gift' => array( 'INNER JOIN', 'ug_gift_id = gift_id' ) )
		);

		$requests = array();
		foreach ( $res as $row ) {
			$requests[] = array(
				'id' => $row->ug_id,
				'gift_id' => $row->ug_gift_id,
				'timestamp' => ( $row->ug_date ),
				'status' => $row->ug_status,
				'user_id_from' => $row->ug_user_id_from,
				'user_name_from' => $row->ug_user_name_from,
				'gift_name' => $row->gift_name,
				'gift_description' => $row->gift_description,
				'gift_given_count' => $row->gift_given_count
			);
		}

		return $requests;
	}

	/**
	 * Sends out the "you have advanced to level [fill in this]" messages to the users
	 *
	 * @param int $userIdTo User ID of the receiver
	 * @param mixed $level Name of the level that the user advanced to
	 */
	public function sendAdvancementNotificationEmail( $userIdTo, $level ) {
		$user = User::newFromId( $userIdTo );
		$user->loadFromDatabase();

		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ? $user->getBoolOption( 'echo-subscriptions-email-social-level-up' ) : $user->getIntOption( 'notifyhonorifics', 1 );
		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage( 'level-advance-subject', $level )->text();
			if ( trim( $user->getRealName() ) ) {
				$name = $user->getRealName();
			} else {
				$name = $user->getName();
			}
			$body = array(
				'html' => wfMessage( 'level-advance-body-html', $name, $level )->parse(),
				'text' => wfMessage( 'level-advance-body',
					$name,
					$level,
					$updateProfileLink->getFullURL()
				)->text()
			);

			$user->sendMail( $subject, $body );
		}
	}

}
