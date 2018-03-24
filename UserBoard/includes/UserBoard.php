<?php
/**
 * Functions for managing user board data
 */
class UserBoard {

	public function __construct() {}

	/**
	 * Sends a user board message to another user.
	 * Performs the insertion to user_board table, sends e-mail notification
	 * (if appliable), and increases social statistics as appropriate.
	 *
	 * @param int $user_id_from User ID of the sender
	 * @param mixed $user_name_from User name of the sender
	 * @param int $user_id_to User ID of the reciever
	 * @param mixed $user_name_to User name of the reciever
	 * @param mixed $message Message text
	 * @param int $message_type 0 for public message
	 * @return int The inserted value of ub_id row
	 */
	public function sendBoardMessage( $user_id_from, $user_name_from, $user_id_to, $user_name_to, $message, $message_type = 0 ) {
		$dbw = wfGetDB( DB_MASTER );

		$user_name_from = stripslashes( $user_name_from );
		$user_name_to = stripslashes( $user_name_to );

		$dbw->insert(
			'user_board',
			array(
				'ub_user_id_from' => $user_id_from,
				'ub_user_name_from' => $user_name_from,
				'ub_user_id' => $user_id_to,
				'ub_user_name' => $user_name_to,
				'ub_message' => $message,
				'ub_type' => $message_type,
				'ub_date' => date( 'Y-m-d H:i:s' ),
			),
			__METHOD__
		);

		// Send e-mail notification (if user is not writing on own board)
		if ( $user_id_from != $user_id_to ) {
			$this->sendBoardNotificationEmail( $user_id_to, $user_name_from );

			global $wgMemc;

			$messageCount = new UserBoardMessageCount( $wgMemc, $user_id_to );
			$messageCount->increase();
		}

		$stats = new UserStatsTrack( $user_id_to, $user_name_to );
		if ( $message_type == 0 ) {
			// public message count
			$stats->incStatField( 'user_board_count' );
		} else {
			// private message count
			$stats->incStatField( 'user_board_count_priv' );
		}

		$stats = new UserStatsTrack( $user_id_from, $user_name_from );
		$stats->incStatField( 'user_board_sent' );

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$userFrom = User::newFromId( $user_id_from );

			EchoEvent::create( array(
				'type' => 'social-msg-send',
				'agent' => $userFrom,
				'extra' => array(
					'target' => $user_id_to,
					'from' => $user_id_from,
					'type' => $message_type,
					'message' => $message
				)
			) );
		}

		return $dbw->insertId();
	}

	/**
	 * Sends an email to a user if someone wrote on their board
	 *
	 * @param int $user_id_to User ID of the reciever
	 * @param mixed $user_from The user name of the person who wrote the board message
	 */
	public function sendBoardNotificationEmail( $user_id_to, $user_from ) {
		$user = User::newFromId( $user_id_to );
		$user->loadFromId();

		// Send email if user's email is confirmed and s/he's opted in to recieving social notifications
		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ? $user->getBoolOption( 'echo-subscriptions-email-social-rel' ) : $user->getIntOption( 'notifymessage', 1 );
		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$board_link = SpecialPage::getTitleFor( 'UserBoard' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage( 'message_received_subject', $user_from )->parse();
			$body = array(
				'html' => wfMessage( 'message_received_body_html',
					$user->getName(),
					$user_from
				)->parse(),
				'text' => wfMessage( 'message_received_body',
					$user->getName(),
					$user_from,
					htmlspecialchars( $board_link->getFullURL() ),
					htmlspecialchars( $update_profile_link->getFullURL() )
				)->text()
			);

			$user->sendMail( $subject, $body );
		}
	}

	/**
	 * Checks if the user with ID number $user_id owns the board message with
	 * the ID number $ub_id.
	 *
	 * @param $user_id int User ID number
	 * @param $ub_id int User board message ID number
	 * @return bool True if user owns the message, otherwise false
	 */
	public function doesUserOwnMessage( $user_id, $ub_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_board',
			array( 'ub_user_id' ),
			array( 'ub_id' => $ub_id ),
			__METHOD__
		);
		if ( $s !== false ) {
			if ( $user_id == $s->ub_user_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Deletes a user board message from the database and decreases social
	 * statistics as appropriate (either 'user_board_count' or
	 * 'user_board_count_priv' is decreased by one).
	 *
	 * @param int $ub_id ID Number of the board message that we want to delete
	 */
	public function deleteMessage( $ub_id ) {
		if ( $ub_id ) {
			$dbw = wfGetDB( DB_MASTER );
			$s = $dbw->selectRow(
				'user_board',
				array( 'ub_user_id', 'ub_user_name', 'ub_type' ),
				array( 'ub_id' => $ub_id ),
				__METHOD__
			);
			if ( $s !== false ) {
				$dbw->delete(
					'user_board',
					array( 'ub_id' => $ub_id ),
					__METHOD__
				);

				$stats = new UserStatsTrack( $s->ub_user_id, $s->ub_user_name );
				if ( $s->ub_type == 0 ) {
					$stats->decStatField( 'user_board_count' );
				} else {
					$stats->decStatField( 'user_board_count_priv' );
				}
			}
		}
	}

	/**
	 * Get the user board messages for the user with the ID $user_id.
	 *
	 * @todo FIXME: Rewrite this function to be compatible with non-MySQL DBMS
	 * @param int $user_id User ID number
	 * @param int $user_id_2 User ID number of the second user; only used
	 * in board-to-board stuff
	 * @param int $limit Used to build the LIMIT and OFFSET for the SQL
	 * query
	 * @param int $page Used to build the LIMIT and OFFSET for the SQL
	 * query
	 * @return array Array of user board messages
	 */
	public function getUserBoardMessages( $user_id, $user_id_2 = 0, $limit = 0, $page = 0 ) {
		global $wgUser, $wgOut, $wgTitle;
		$dbr = wfGetDB( DB_REPLICA );

		if ( $limit > 0 ) {
			$limitvalue = 0;
			if ( $page ) {
				$limitvalue = $page * $limit - ( $limit );
			}
			$limit_sql = " LIMIT {$limitvalue},{$limit} ";
		}

		if ( $user_id_2 ) {
			$user_sql = "( (ub_user_id={$user_id} AND ub_user_id_from={$user_id_2}) OR
					(ub_user_id={$user_id_2} AND ub_user_id_from={$user_id}) )";
			if ( !( $user_id == $wgUser->getId() || $user_id_2 == $wgUser->getId() ) ) {
				$user_sql .= ' AND ub_type = 0 ';
			}
		} else {
			$user_sql = "ub_user_id = {$user_id}";
			if ( $user_id != $wgUser->getId() ) {
				$user_sql .= ' AND ub_type = 0 ';
			}
			if ( $wgUser->isLoggedIn() ) {
				$user_sql .= " OR (ub_user_id={$user_id} AND ub_user_id_from={$wgUser->getId()}) ";
			}
		}

		$sql = "SELECT ub_id, ub_user_id_from, ub_user_name_from, ub_user_id, ub_user_name,
			ub_message, ub_date, ub_type
			FROM {$dbr->tableName( 'user_board' )}
			WHERE {$user_sql}
			ORDER BY ub_id DESC
			{$limit_sql}";
		$res = $dbr->query( $sql, __METHOD__ );

		$messages = array();

		foreach ( $res as $row ) {
			$parser = new Parser();
			$message_text = $parser->parse( $row->ub_message, $wgTitle, $wgOut->parserOptions(), true );
			$message_text = $message_text->getText();

			$messages[] = array(
				'id' => $row->ub_id,
				'timestamp' => wfTimestamp( TS_UNIX, $row->ub_date ),
				'user_id_from' => $row->ub_user_id_from,
				'user_name_from' => $row->ub_user_name_from,
				'user_id' => $row->ub_user_id,
				'user_name' => $row->ub_user_name,
				'message_text' => $message_text,
				'type' => $row->ub_type
			);
		}

		return $messages;
	}

	/**
	 * Get the amount of board-to-board messages sent between the users whose
	 * IDs are $user_id and $user_id_2.
	 *
	 * @todo FIXME: Rewrite this function to be compatible with non-MySQL DBMS
	 * @param int $user_id User ID of the first user
	 * @param int $user_id_2 User ID of the second user
	 * @return int The amount of board-to-board messages
	 */
	public function getUserBoardToBoardCount( $user_id, $user_id_2 ) {
		global $wgUser;

		$dbr = wfGetDB( DB_REPLICA );

		$user_sql = " ( (ub_user_id={$user_id} AND ub_user_id_from={$user_id_2}) OR
					(ub_user_id={$user_id_2} AND ub_user_id_from={$user_id}) )";

		if ( !( $user_id == $wgUser->getId() || $user_id_2 == $wgUser->getId() ) ) {
			$user_sql .= ' AND ub_type = 0 ';
		}
		$sql = "SELECT COUNT(*) AS the_count
			FROM {$dbr->tableName( 'user_board' )}
			WHERE {$user_sql}";

		$res = $dbr->query( $sql, __METHOD__ );
		$row = $dbr->fetchObject( $res );

		if ( $row ) {
			$count = $row->the_count;
		}

		return $count;
	}

	public function displayMessages( $user_id, $user_id_2 = 0, $count = 10, $page = 0 ) {
		global $wgUser, $wgTitle;

		$output = ''; // Prevent E_NOTICE
		$messages = $this->getUserBoardMessages( $user_id, $user_id_2, $count, $page );

		if ( $messages ) {
			foreach ( $messages as $message ) {
				$user = Title::makeTitle( NS_USER, $message['user_name_from'] );
				$avatar = new wAvatar( $message['user_id_from'], 'm' );

				$board_to_board = '';
				$board_link = '';
				$message_type_label = '';
				$delete_link = '';

				if ( $wgUser->getName() != $message['user_name_from'] ) {
					$board_to_board = '<a href="' .
						htmlspecialchars(
							SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [
								'user' => $message['user_name'],
								'conv' => $message['user_name_from']
							] ),
							ENT_QUOTES
						)
						. '">' .
						wfMessage( 'userboard_board-to-board' )->plain() . '</a>';
					$board_link = '<a href="' .
						htmlspecialchars(
							SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [ 'user' => $message['user_name_from'] ] ),
							ENT_QUOTES
						) . '">' .
						wfMessage( 'userboard_sendmessage', $message['user_name_from'] )->parse() . '</a>';
				}
				if ( $wgUser->getName() == $message['user_name'] || $wgUser->isAllowed( 'userboard-delete' ) ) {
					$delete_link = "<span class=\"user-board-red\">
							<a href=\"javascript:void(0);\" data-message-id=\"{$message['id']}\">" .
								wfMessage( 'delete' )->plain() . '</a>
						</span>';
				}
				if ( $message['type'] == 1 ) {
					$message_type_label = '(' . wfMessage( 'userboard_private' )->plain() . ')';
				}

				$message_text = $message['message_text'];
				# $message_text = preg_replace_callback( "/(<a[^>]*>)(.*?)(<\/a>)/i", 'cut_link_text', $message['message_text'] );

				$sender = htmlspecialchars( $user->getFullURL() );
				$senderTitle = htmlspecialchars( $message['user_name_from'] );
				$output .= "<div class=\"user-board-message\">
					<div class=\"user-board-message-from\">
					<a href=\"{$sender}\" title=\"{$senderTitle}\">{$message['user_name_from']}</a> {$message_type_label}
					</div>
					<div class=\"user-board-message-time\">" .
						wfMessage( 'userboard_posted_ago', $this->getTimeAgo( $message['timestamp'] ) )->parse() .
					"</div>
					<div class=\"user-board-message-content\">
						<div class=\"user-board-message-image\">
							<a href=\"{$sender}\" title=\"{$senderTitle}\">{$avatar->getAvatarURL()}</a>
						</div>
						<div class=\"user-board-message-body\">
							{$message_text}
						</div>
						<div class=\"visualClear\"></div>
					</div>
					<div class=\"user-board-message-links\">
						{$board_link}
						{$board_to_board}
						{$delete_link}
					</div>
				</div>";
			}
		} elseif ( $wgUser->getName() == $wgTitle->getText() ) {
			$output .= '<div class="no-info-container">' .
				wfMessage( 'userboard_nomessages' )->parse() .
			'</div>';

		}

		return $output;
	}

	/**
	 * Gets the difference between two given dates
	 *
	 * @param int $dt1 Current time, as returned by PHP's time() function
	 * @param int $dt2 Date
	 * @return int Difference between dates
	 */
	public function dateDiff( $date1, $date2 ) {
		$dtDiff = $date1 - $date2;

		$totalDays = intval( $dtDiff / ( 24 * 60 * 60 ) );
		$totalSecs = $dtDiff - ( $totalDays * 24 * 60 * 60 );
		$dif['w'] = intval( $totalDays / 7 );
		$dif['d'] = $totalDays;
		$dif['h'] = $h = intval( $totalSecs / ( 60 * 60 ) );
		$dif['m'] = $m = intval( ( $totalSecs - ( $h * 60 * 60 ) ) / 60 );
		$dif['s'] = $totalSecs - ( $h * 60 * 60 ) - ( $m * 60 );

		return $dif;
	}

	public function getTimeOffset( $time, $timeabrv, $timename ) {
		$timeStr = '';
		if ( $time[$timeabrv] > 0 ) {
			$timeStr = wfMessage( "userboard-time-{$timename}", $time[$timeabrv] )->parse();
		}
		if ( $timeStr ) {
			$timeStr .= ' ';
		}
		return $timeStr;
	}

	/**
	 * Gets the time how long ago the given board message was posted
	 *
	 * @param int $time
	 * @return string Time, such as "20 days" or "11 hours"
	 */
	public function getTimeAgo( $time ) {
		$timeArray = $this->dateDiff( time(), $time );
		$timeStr = '';
		$timeStrD = $this->getTimeOffset( $timeArray, 'd', 'days' );
		$timeStrH = $this->getTimeOffset( $timeArray, 'h', 'hours' );
		$timeStrM = $this->getTimeOffset( $timeArray, 'm', 'minutes' );
		$timeStrS = $this->getTimeOffset( $timeArray, 's', 'seconds' );
		$timeStr = $timeStrD;
		if ( $timeStr < 2 ) {
			$timeStr .= $timeStrH;
			$timeStr .= $timeStrM;
			if ( !$timeStr ) {
				$timeStr .= $timeStrS;
			}
		}
		if ( !$timeStr ) {
			$timeStr = wfMessage( 'userboard-time-seconds', 1 )->parse();
		}
		return $timeStr;
	}
}
