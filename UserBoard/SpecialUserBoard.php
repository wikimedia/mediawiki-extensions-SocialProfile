<?php
/**
 * Display User Board messages for a user
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class SpecialViewUserBoard extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'UserBoard' );
	}

	/**
	 * Group this special page under the correct header in Special:SpecialPages.
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'users';
	}

	/**
	 * Show the special page
	 *
	 * @param $params Mixed: parameter(s) passed to the page or null
	 */
	public function execute( $params ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$currentUser = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( 'ext.socialprofile.userboard.css' );
		$out->addModules( 'ext.socialprofile.userboard.js' );

		$ub_messages_show = 25;
		$user_name = $request->getVal( 'user' );
		$user_name_2 = $request->getVal( 'conv' );
		$user_id_2 = ''; // Prevent E_NOTICE
		$page = $request->getInt( 'page', 1 );

		/**
		 * Redirect Non-logged in users to Login Page
		 * It will automatically return them to the UserBoard page
		 */
		if ( $currentUser->getID() == 0 && $user_name == '' ) {
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			$out->redirect( $login->getFullURL( 'returnto=Special:UserBoard' ) );
			return false;
		}

		/**
		 * If no user is set in the URL, we assume its the current user
		 */
		if ( !$user_name ) {
			$user_name = $currentUser->getName();
		}
		$user_id = User::idFromName( $user_name );
		$user = Title::makeTitle( NS_USER, $user_name );

		if ( $user_name_2 ) {
			$user_id_2 = User::idFromName( $user_name_2 );
			$user_2 = Title::makeTitle( NS_USER, $user_name );
		}

		/**
		 * Error message for username that does not exist (from URL)
		 */
		if ( $user_id == 0 ) {
			$out->showErrorPage( 'error', 'userboard_noexist' );
			return false;
		}

		/**
		 * Config for the page
		 */
		$per_page = $ub_messages_show;

		$b = new UserBoard();
		$ub_messages = $b->getUserBoardMessages(
			$user_id,
			$user_id_2,
			$ub_messages_show,
			$page
		);

		if ( !$user_id_2 ) {
			$stats = new UserStats( $user_id, $user_name );
			$stats_data = $stats->getUserStats();
			$total = $stats_data['user_board'];
			// If user is viewing their own board or is allowed to delete
			// others' board messages, show the total count of board messages
			// to them (public + private messages)
			if (
				$currentUser->getName() == $user_name ||
				$currentUser->isAllowed( 'userboard-delete' )
			)
			{
				$total = $total + $stats_data['user_board_priv'];
			}
		} else {
			$total = $b->getUserBoardToBoardCount( $user_id, $user_id_2 );
		}

		if ( !$user_id_2 ) {
			if ( !( $currentUser->getName() == $user_name ) ) {
				$out->setPageTitle( $this->msg( 'userboard_owner', $user_name )->parse() );
			} else {
				$b->clearNewMessageCount( $currentUser->getID() );
				$out->setPageTitle( $this->msg( 'userboard_yourboard' )->parse() );
			}
		} else {
			if ( $currentUser->getName() == $user_name ) {
				$out->setPageTitle( $this->msg( 'userboard_yourboardwith', $user_name_2 )->parse() );
			} else {
				$out->setPageTitle( $this->msg( 'userboard_otherboardwith', $user_name, $user_name_2 )->parse() );
			}
		}

		$output = '<div class="user-board-top-links">';
		$output .= '<a href="' . htmlspecialchars( $user->getFullURL() ) . '">&lt; ' .
			$this->msg( 'userboard_backprofile', $user_name )->parse() . '</a>';
		$output .= '</div>';

		$board_to_board = ''; // Prevent E_NOTICE

		if ( $page == 1 ) {
			$start = 1;
		} else {
			$start = ( $page - 1 ) * $per_page + 1;
		}
		$end = $start + ( count( $ub_messages ) ) - 1;

		if ( $currentUser->getName() != $user_name ) {
			$board_to_board = '<a href="' . UserBoard::getUserBoardToBoardURL( $currentUser->getName(), $user_name ) . '">' .
				$this->msg( 'userboard_boardtoboard' )->plain() . '</a>';
		}

		if ( $total ) {
			$output .= '<div class="user-page-message-top">
			<span class="user-page-message-count">' .
				$this->msg( 'userboard_showingmessages', $total, $start, $end, $end - $start + 1 )->parse() .
			"</span> {$board_to_board}
			</div>";
		}

		/**
		 * Build next/prev navigation links
		 */
		$qs = array();
		if ( $user_id_2 ) {
			$qs['conv'] = $user_name_2;
		}
		$numofpages = $total / $per_page;

		if ( $numofpages > 1 ) {
			$output .= '<div class="page-nav">';
			if ( $page > 1 ) {
				$output .= Linker::link(
					$this->getPageTitle(),
					$this->msg( 'userboard_prevpage' )->plain(),
					array(),
					array(
						'user' => $user_name,
						'page' => ( $page - 1 )
					) + $qs
				);
			}

			if ( ( $total % $per_page ) != 0 ) {
				$numofpages++;
			}
			if ( $numofpages >= 9 && $page < $total ) {
				$numofpages = 9 + $page;
				if ( $numofpages >= ( $total / $per_page ) ) {
					$numofpages = ( $total / $per_page ) + 1;
				}
			}

			for ( $i = 1; $i <= $numofpages; $i++ ) {
				if ( $i == $page ) {
					$output .= ( $i . ' ' );
				} else {
					$output .= Linker::link(
						$this->getPageTitle(),
						$i,
						array(),
						array(
							'user' => $user_name,
							'page' => $i
						) + $qs
					) . $this->msg( 'word-separator' )->plain();
				}
			}

			if ( ( $total - ( $per_page * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->plain() .
					Linker::link(
					$this->getPageTitle(),
					$this->msg( 'userboard_nextpage' )->plain(),
					array(),
					array(
						'user' => $user_name,
						'page' => ( $page + 1 )
					) + $qs
				);
			}
			$output .= '</div><p>';
		}

		$can_post = false;
		$user_name_from = ''; // Prevent E_NOTICE

		if ( !$user_id_2 ) {
			if ( $currentUser->getName() != $user_name ) {
				$can_post = true;
				$user_name_to = htmlspecialchars( $user_name, ENT_QUOTES );
			}
		} else {
			if ( $currentUser->getName() == $user_name ) {
				$can_post = true;
				$user_name_to = htmlspecialchars( $user_name_2, ENT_QUOTES );
				$user_name_from = htmlspecialchars( $user_name, ENT_QUOTES );
			}
		}

		if ( $currentUser->isBlocked() ) {
			// only let them post to admins
			//$user_to = User::newFromId( $user_id );
			// if( !$user_to->isAllowed( 'delete' ) ) {
				$can_post = false;
			// }
		}

		if ( $can_post ) {
			if ( $currentUser->isLoggedIn() && !$currentUser->isBlocked() ) {
				$output .= '<div class="user-page-message-form">
					<input type="hidden" id="user_name_to" name="user_name_to" value="' . $user_name_to . '"/>
					<input type="hidden" id="user_name_from" name="user_name_from" value="' . $user_name_from . '"/>
					<span class="user-board-message-type">' . $this->msg( 'userboard_messagetype' )->plain() . ' </span>
					<select id="message_type">
						<option value="0">' . $this->msg( 'userboard_public' )->plain() . '</option>
						<option value="1">' . $this->msg( 'userboard_private' )->plain() . '</option>
					</select>
					<p>
					<textarea name="message" id="message" cols="63" rows="4"></textarea>

					<div class="user-page-message-box-button">
						<input type="button" value="' . $this->msg( 'userboard_sendbutton' )->plain() . '" class="site-button" data-per-page="' . $per_page . '" />
					</div>

				</div>';
			} else {
				$output .= '<div class="user-page-message-form">'
					. $this->msg( 'userboard_loggedout' )->parse() .
				'</div>';
			}
		}
		$output .= '<div id="user-page-board">';

		if ( $ub_messages ) {
			foreach ( $ub_messages as $ub_message ) {
				$user = Title::makeTitle( NS_USER, $ub_message['user_name_from'] );
				$avatar = new wAvatar( $ub_message['user_id_from'], 'm' );

				$board_to_board = '';
				$board_link = '';
				$ub_message_type_label = '';
				$delete_link = '';

				if ( $currentUser->getName() != $ub_message['user_name_from'] ) {
					$board_to_board = '<a href="' . UserBoard::getUserBoardToBoardURL( $user_name, $ub_message['user_name_from'] ) . '">' .
						$this->msg( 'userboard_boardtoboard' )->plain() . '</a>';
					$board_link = '<a href="' . UserBoard::getUserBoardURL( $ub_message['user_name_from'] ) . '">' .
						$this->msg( 'userboard_sendmessage', $ub_message['user_name_from'] )->parse() . '</a>';
				} else {
					$board_link = '<a href="' . UserBoard::getUserBoardURL( $ub_message['user_name_from'] ) . '">' .
						$this->msg( 'userboard_myboard' )->plain() . '</a>';
				}

				// If the user owns this private message or they are allowed to
				// delete board messages, show the "delete" link to them
				if (
					$currentUser->getName() == $ub_message['user_name'] ||
					$currentUser->isAllowed( 'userboard-delete' )
				)
				{
					$delete_link = "<span class=\"user-board-red\">
						<a href=\"javascript:void(0);\" data-message-id=\"{$ub_message['id']}\">" .
							$this->msg( 'userboard_delete' )->plain() . '</a>
					</span>';
				}

				// Mark private messages as such
				if ( $ub_message['type'] == 1 ) {
					$ub_message_type_label = '(' . $this->msg( 'userboard_private' )->plain() . ')';
				}

				// had global function to cut link text if too long and no breaks
				// $ub_message_text = preg_replace_callback( "/(<a[^>]*>)(.*?)(<\/a>)/i", 'cut_link_text', $ub_message['message_text'] );
				$ub_message_text = $ub_message['message_text'];

				$userPageURL = htmlspecialchars( $user->getFullURL() );
				$output .= "<div class=\"user-board-message\">
					<div class=\"user-board-message-from\">
							<a href=\"{$userPageURL}\" title=\"{$ub_message['user_name_from']}}\">{$ub_message['user_name_from']} </a> {$ub_message_type_label}
					</div>
					<div class=\"user-board-message-time\">"
						. $this->msg( 'userboard_posted_ago', $b->getTimeAgo( $ub_message['timestamp'] ) )->parse() .
					"</div>
					<div class=\"user-board-message-content\">
						<div class=\"user-board-message-image\">
							<a href=\"{$userPageURL}\" title=\"{$ub_message['user_name_from']}\">{$avatar->getAvatarURL()}</a>
						</div>
						<div class=\"user-board-message-body\">
							{$ub_message_text}
						</div>
						<div class=\"cleared\"></div>
					</div>
					<div class=\"user-board-message-links\">
						{$board_link}
						{$board_to_board}
						{$delete_link}
					</div>
				</div>";
			}
		} else {
			$output .= '<p>' . $this->msg( 'userboard_nomessages' )->parse() . '</p>';
		}

		$output .= '</div>';

		$out->addHTML( $output );
	}
}
