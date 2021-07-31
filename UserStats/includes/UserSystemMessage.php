<?php

use MediaWiki\MediaWikiServices;

/**
 * UserSystemMessage class
 * Used to send "You have advanced to level [fill in this]" messages
 * to users when User Levels is activated ($wgUserLevels is defined)
 *
 * @file
 * @ingroup Extensions
 */
class UserSystemMessage {
	// Constants for the um_type field.
	/**
	 * @var int The default type; de facto unused.
	 */
	const TYPE_DEFAULT = 0;

	/**
	 * @var int Used by the NewSignupPage extension for "user A recruited user B" events.
	 */
	const TYPE_RECRUIT = 1;

	/**
	 * @var int Used by the UserStatsTrack class for "user A advanced to level X" events.
	 */
	const TYPE_LEVELUP = 2;

	/**
	 * Adds the message into the database
	 *
	 * @param User $user The user (object) related to the message (i.e. user who advanced to a new level etc.)
	 * @param int $type One of the TYPE_* constants; 0/TYPE_DEFAULT by default
	 * @param string $message Message to be sent out
	 */
	public function addMessage( $user, $type = 0, $message ) {
		$actorId = $user->getActorId();
		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'user_system_messages',
			[
				'um_actor' => $actorId,
				'um_type' => $type,
				'um_message' => $message,
				'um_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) ),
			], __METHOD__
		);
	}

	/**
	 * Deletes a message from the user_system_messages table in the database
	 *
	 * @param int $um_id Internal ID number of the message to delete
	 */
	public static function deleteMessage( $um_id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'user_system_messages',
			[ 'um_id' => $um_id ],
			__METHOD__
		);
	}

	/**
	 * Sends out the "you have advanced to level [fill in this]" messages to the users
	 *
	 * @param User $userTo User receiving the email
	 * @param mixed $level Name of the level that the user advanced to
	 */
	public function sendAdvancementNotificationEmail( $userTo, $level ) {
		$userTo->load();

		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ?
			$userOptionsLookup->getBoolOption( $userTo, 'echo-subscriptions-email-social-level-up' ) :
			$userOptionsLookup->getIntOption( $userTo, 'notifyhonorifics', 1 );
		if ( $userTo->isEmailConfirmed() && $wantsEmail ) {
			$updateProfileLink = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage( 'level-advance-subject', $level )->text();
			if ( trim( $userTo->getRealName() ) ) {
				$name = $userTo->getRealName();
			} else {
				$name = $userTo->getName();
			}
			$body = [
				'html' => wfMessage( 'level-advance-body-html', $name, $level )->parse(),
				'text' => wfMessage( 'level-advance-body',
					$name,
					$level,
					$updateProfileLink->getFullURL()
				)->text()
			];

			$userTo->sendMail( $subject, $body );
		}
	}

}
