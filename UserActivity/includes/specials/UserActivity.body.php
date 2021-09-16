<?php
/**
 * Special:UserActivity - a special page for showing recent social activity
 * The class is called "UserHome" because the "UserActivity" class is at
 * UserActivityClass.php.
 *
 * @file
 * @ingroup Extensions
 */

class UserHome extends SpecialPage {

	public function __construct() {
		parent::__construct( 'UserActivity' );
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
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.useractivity.css' );

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		$out->setPageTitle( $this->msg( 'useractivity-title' )->plain() );

		$output = '';
		// Initialize all of these or otherwise we get a lot of E_NOTICEs about
		// undefined variables when the filtering feature (described below) is
		// active and we're viewing a filtered-down feed
		$edits = $votes = $comments = $comments = $gifts = $relationships =
			$messages = $system_gifts = $messages_sent = $network_updates = 0;

		$rel_type = $request->getVal( 'rel_type' );
		$item_type = $request->getVal( 'item_type' );

		if ( !$rel_type ) {
			$rel_type = 1;
		}
		if ( !$item_type ) {
			$item_type = 'all';
		}

		// If not otherwise specified, display everything but votes in the feed
		if ( $item_type == 'edits' || $item_type == 'all' ) {
			$edits = 1;
		}
		if ( $item_type == 'votes' || $item_type == 'all' ) {
			// @phan-suppress-next-line PhanPluginRedundantAssignment
			$votes = 0;
		}
		if ( $item_type == 'comments' || $item_type == 'all' ) {
			$comments = 1;
		}
		if ( $item_type == 'gifts' || $item_type == 'all' ) {
			$gifts = 1;
		}
		if ( $item_type == 'relationships' || $item_type == 'all' ) {
			$relationships = 1;
		}
		if ( $item_type == 'advancements' || $item_type == 'all' ) {
			$messages = 1;
		}
		if ( $item_type == 'awards' || $item_type == 'all' ) {
			$system_gifts = 1;
		}
		if ( $item_type == 'messages' || $item_type == 'all' ) {
			$messages_sent = 1;
		}
		if ( $item_type == 'thoughts' || $item_type == 'all' ) {
			$network_updates = 1;
		}

		$linkRenderer = $this->getLinkRenderer();
		$pageTitle = $this->getPageTitle();
		// Filtering feature, if enabled
		// The filter message's format is:
		// *filter name (item_type URL parameter)|Displayed text (can be the name of a MediaWiki: message, too)|Type icon name (*not* the image name; see UserActivity::getTypeIcon())
		// For example:
		// *messages|Board Messages|user_message
		// This would add a link that allows filtering non-board messages
		// related events from the filter, only showing board message activity

		$filterMsg = $this->msg( 'useractivity-friendsactivity-filter' );
		if ( !$filterMsg->isDisabled() ) {
			$output .= '<div class="user-home-links-container">
			<h2>' . htmlspecialchars( $this->msg( 'useractivity-filter' )->plain() ) . '</h2>
			<div class="user-home-links">';

			$lines = explode( "\n", $filterMsg->inContentLanguage()->text() );

			foreach ( $lines as $line ) {
				if ( strpos( $line, '*' ) !== 0 ) {
					continue;
				} else {
					$line = explode( '|', trim( $line, '* ' ), 3 );
					$filter = $line[0];
					$link_text = $line[1];

					// Maybe it's the name of a MediaWiki: message? I18n is
					// always nice, so at least try it and see what happens...
					$linkMsgObj = $this->msg( $link_text );
					if ( !$linkMsgObj->isDisabled() ) {
						$link_text = $linkMsgObj->parse();
					} else {
						$link_text = htmlspecialchars( $link_text );
					}

					$link_image = $line[2];

					$activityFilterIcon = new UserActivityIcon( $link_image );
					$filterIcon = $activityFilterIcon->getIconHTML();

					$output .= '<a href="' . htmlspecialchars( $pageTitle->getFullURL( [ 'item_type' => $filter ] ) ) .
						"\">{$filterIcon}{$link_text}</a>";

				}
			}

			$output .= $linkRenderer->makeLink(
				$pageTitle,
				$this->msg( 'useractivity-all' )->plain()
			);
			$output .= '</div>
			</div>';
		}

		$output .= '<div class="user-home-feed">';

		$rel = new UserActivity( $user, ( ( $rel_type == 1 ) ? ' friends' : 'foes' ), 50 );
		$rel->setActivityToggle( 'show_edits', $edits );
		$rel->setActivityToggle( 'show_votes', $votes );
		$rel->setActivityToggle( 'show_comments', $comments );
		$rel->setActivityToggle( 'show_gifts_rec', $gifts );
		$rel->setActivityToggle( 'show_relationships', $relationships );
		$rel->setActivityToggle( 'show_system_messages', $messages );
		$rel->setActivityToggle( 'show_system_gifts', $system_gifts );
		$rel->setActivityToggle( 'show_messages_sent', $messages_sent );
		$rel->setActivityToggle( 'show_network_updates', $network_updates );

		/**
		 * Get all relationship activity
		 */
		$activity = $rel->getActivityListGrouped();
		$border_fix = '';

		if ( $activity ) {
			$x = 1;

			foreach ( $activity as $item ) {
				if ( $x < 40 ) {
					if (
						( ( count( $activity ) > 40 ) && ( $x == 39 ) ) ||
						( ( count( $activity ) < 40 ) && ( $x == ( count( $activity ) - 1 ) ) )
					) {
						$border_fix = ' border-fix';
					}

					$userActivityIcon = new UserActivityIcon( $item['type'] );
					$icon = $userActivityIcon->getIconHTML();
					$output .= "<div class=\"user-home-activity{$border_fix}\">
						{$icon}{$item['data']}
					</div>";
					$x++;
				}
			}
		}

		$output .= '</div>
		<div class="visualClear"></div>';
		// @phan-suppress-next-line SecurityCheck-XSS
		$out->addHTML( $output );
	}
}
