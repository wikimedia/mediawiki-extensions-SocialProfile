<?php
/**
 * A special page for viewing all relationships by type
 * Example URL: index.php?title=Special:ViewRelationships&user=Pean&rel_type=1 (viewing friends)
 * Example URL: index.php?title=Special:ViewRelationships&user=Pean&rel_type=2 (viewing foes)
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class SpecialViewRelationships extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ViewRelationships' );
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
		$lang = $this->getLanguage();
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( array(
			'ext.socialprofile.clearfix',
			'ext.socialprofile.userrelationship.css'
		) );

		$output = '';

		/**
		 * Get query string variables
		 */
		$user_name = $request->getVal( 'user' );
		$rel_type = $request->getInt( 'rel_type' );
		$page = $request->getInt( 'page' );

		/**
		 * Redirect Non-logged in users to Login Page
		 * It will automatically return them to the ViewRelationships page
		 */
		if ( !$user->isLoggedIn() && $user_name == '' ) {
			$out->setPageTitle( $this->msg( 'ur-error-page-title' )->plain() );
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			$out->redirect( htmlspecialchars( $login->getFullURL( 'returnto=Special:ViewRelationships' ) ) );
			return false;
		}

		/**
		 * Set up config for page / default values
		 */
		if ( !$page || !is_numeric( $page ) ) {
			$page = 1;
		}
		if ( !$rel_type || !is_numeric( $rel_type ) ) {
			$rel_type = 1;
		}
		$per_page = 50;
		$per_row = 2;

		/**
		 * If no user is set in the URL, we assume its the current user
		 */
		if ( !$user_name ) {
			$user_name = $user->getName();
		}
		$user_id = User::idFromName( $user_name );
		$userPage = Title::makeTitle( NS_USER, $user_name );

		/**
		 * Error message for username that does not exist (from URL)
		 */
		if ( $user_id == 0 ) {
			$out->setPageTitle( $this->msg( 'ur-error-title' )->plain() );
			$output = '<div class="relationship-error-message">' .
				$this->msg( 'ur-error-message-no-user' )->plain() .
			'</div>
			<div class="relationship-request-buttons">
				<input type="button" class="site-button" value="' . $this->msg( 'ur-main-page' )->plain() . '" onclick=\'window.location="index.php?title=' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '"\' />';
			if ( $user->isLoggedIn() ) {
				$output .= '<input type="button" class="site-button" value="' . $this->msg( 'ur-your-profile' )->plain() . '" onclick=\'window.location="' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '"\' />';
			}
			$output .= '</div>';
			$out->addHTML( $output );
			return false;
		}

		/**
		 * Get all relationships
		 */
		$rel = new UserRelationship( $user_name );
		$relationships = $rel->getRelationshipList( $rel_type, $per_page, $page );

		$stats = new UserStats( $rel->user_id, $rel->user_name );
		$stats_data = $stats->getUserStats();
		$friend_count = $stats_data['friend_count'];
		$foe_count = $stats_data['foe_count'];

		$back_link = Title::makeTitle( NS_USER, $rel->user_name );

		if ( $rel_type == 1 ) {
			$out->setPageTitle( $this->msg( 'ur-title-friend', $rel->user_name )->parse() );

			$total = $friend_count;

			$rem = $this->msg( 'ur-remove-relationship-friend' )->plain();
			$output .= '<div class="back-links">
			<a href="' . htmlspecialchars( $back_link->getFullURL() ) . '">' .
				$this->msg( 'ur-backlink', $rel->user_name )->parse() .
			'</a>
		</div>
		<div class="relationship-count">' .
			$this->msg(
				'ur-relationship-count-friends',
				$rel->user_name,
				$total
			)->text() . '</div>';
		} else {
			$out->setPageTitle( $this->msg( 'ur-title-foe', $rel->user_name )->parse() );

			$total = $foe_count;

			$rem = $this->msg( 'ur-remove-relationship-foe' )->plain();

			$output .= '<div class="back-links">
			<a href="' . htmlspecialchars( $back_link->getFullURL() ) . '">' .
				$this->msg( 'ur-backlink', $rel->user_name )->parse() .
			'</a>
		</div>
		<div class="relationship-count">'
			. $this->msg(
				'ur-relationship-count-foes',
				$rel->user_name,
				$total
			)->text() . '</div>';
		}

		if ( $relationships ) {
			$x = 1;

			foreach ( $relationships as $relationship ) {
				$indivRelationship = UserRelationship::getUserRelationshipByID(
					$relationship['user_id'],
					$user->getID()
				);

				// Safe titles
				$userPage = Title::makeTitle( NS_USER, $relationship['user_name'] );
				$addRelationshipLink = SpecialPage::getTitleFor( 'AddRelationship' );
				$removeRelationshipLink = SpecialPage::getTitleFor( 'RemoveRelationship' );
				$giveGiftLink = SpecialPage::getTitleFor( 'GiveGift' );

				$userPageURL = htmlspecialchars( $userPage->getFullURL() );
				$avatar = new wAvatar( $relationship['user_id'], 'ml' );

				$avatar_img = $avatar->getAvatarURL();

				$username_length = strlen( $relationship['user_name'] );
				$username_space = stripos( $relationship['user_name'], ' ' );

				if ( ( $username_space == false || $username_space >= "30" ) && $username_length > 30 ) {
					$user_name_display = substr( $relationship['user_name'], 0, 30 ) .
						' ' . substr( $relationship['user_name'], 30, 50 );
				} else {
					$user_name_display = $relationship['user_name'];
				}

				$output .= "<div class=\"relationship-item\">
					<a href=\"{$userPageURL}\">{$avatar_img}</a>
					<div class=\"relationship-info\">
						<div class=\"relationship-name\">
							<a href=\"{$userPageURL}\">{$user_name_display}</a>
						</div>
					<div class=\"relationship-actions\">";

				if ( $indivRelationship == false ) {
					$output .= $lang->pipeList( array(
						Linker::link(
							$addRelationshipLink,
							$this->msg( 'ur-add-friend' )->plain(),
							array(),
							array( 'user' => $relationship['user_name'], 'rel_type' => 1 )
						),
						Linker::link(
							$addRelationshipLink,
							$this->msg( 'ur-add-foe' )->plain(),
							array(),
							array( 'user' => $relationship['user_name'], 'rel_type' => 2 )
						),
						''
					) );
				} elseif ( $user_name == $user->getName() ) {
					$output .= Linker::link(
						$removeRelationshipLink,
						$rem,
						array(),
						array( 'user' => $relationship['user_name'] )
					);
					$output .= $this->msg( 'pipe-separator' )->escaped();
				}

				$output .= Linker::link(
					$giveGiftLink,
					$this->msg( 'ur-give-gift' )->plain(),
					array(),
					array( 'user' => $relationship['user_name'] )
				);

				$output .= '</div>
					<div class="visualClear"></div>
				</div>';

				$output .= '</div>';
				if ( $x == count( $relationships ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}
				$x++;
			}
		}

		/**
		 * Build next/prev nav
		 */
		$total = intval( $total );
		$numofpages = $total / $per_page;

		$pageLink = $this->getPageTitle();

		if ( $numofpages > 1 ) {
			$output .= '<div class="page-nav">';
			if ( $page > 1 ) {
				$output .= Linker::link(
					$pageLink,
					$this->msg( 'ur-previous' )->plain(),
					array(),
					array(
						'user' => $user_name,
						'rel_type' => $rel_type,
						'page' => ( $page - 1 )
					)
				) . $this->msg( 'word-separator' )->plain();
			}

			if ( ( $total % $per_page ) != 0 ) {
				$numofpages++;
			}
			if ( $numofpages >= 9 && $page < $total ) {
				$numofpages = 9 + $page;
			}
			if ( $numofpages >= ( $total / $per_page ) ) {
				$numofpages = ( $total / $per_page ) + 1;
			}

			for ( $i = 1; $i <= $numofpages; $i++ ) {
				if ( $i == $page ) {
					$output .= ( $i . ' ' );
				} else {
					$output .= Linker::link(
						$pageLink,
						$i,
						array(),
						array(
							'user' => $user_name,
							'rel_type' => $rel_type,
							'page' => $i
						)
					) . $this->msg( 'word-separator' )->plain();
				}
			}

			if ( ( $total - ( $per_page * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->plain() .
					Linker::link(
						$pageLink,
						$this->msg( 'ur-next' )->plain(),
						array(),
						array(
							'user' => $user_name,
							'rel_type' => $rel_type,
							'page' => ( $page + 1 )
						)
					);
			}
			$output .= '</div>';
		}

		$out->addHTML( $output );
	}
}
