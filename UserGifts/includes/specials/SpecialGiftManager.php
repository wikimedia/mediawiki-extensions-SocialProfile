<?php

use MediaWiki\MediaWikiServices;

/**
 * Special page for creating and editing user-to-user gifts.
 *
 * @file
 */
class GiftManager extends SpecialPage {

	public function __construct() {
		parent::__construct( 'GiftManager'/*class*/, 'giftadmin'/*restriction*/ );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Group this special page under the correct header in Special:SpecialPages.
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'wiki';
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

		// Make sure that the user is logged in and that they can use this
		// special page
		$this->requireLogin();

		if ( !$this->canUserManage() ) {
			throw new ErrorPageError( 'error', 'badaccess' );
		}

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If the user is blocked, don't allow access to them
		$block = $user->getBlock();
		if ( $block ) {
			throw new UserBlockedError( $block );
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( [
			'ext.socialprofile.usergifts.css',
			'ext.socialprofile.special.giftmanager.css'
		] );

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			if ( !$request->getInt( 'id' ) ) {
				$giftId = Gifts::addGift(
					$user,
					$request->getVal( 'gift_name' ),
					$request->getVal( 'gift_description' ),
					$request->getInt( 'access' )
				);
				$out->addHTML(
					'<span class="view-status">' .
					$this->msg( 'giftmanager-giftcreated' )->escaped() .
					'</span><br /><br />'
				);
			} else {
				$giftId = $request->getInt( 'id' );
				Gifts::updateGift(
					$giftId,
					$request->getVal( 'gift_name' ),
					$request->getVal( 'gift_description' ),
					$request->getInt( 'access' )
				);
				$out->addHTML(
					'<span class="view-status">' .
					$this->msg( 'giftmanager-giftsaved' )->escaped() .
					'</span><br /><br />'
				);
			}

			$out->addHTML( $this->displayForm( $giftId ) );
		} else {
			$giftId = $request->getInt( 'id' );
			if ( $giftId || $request->getVal( 'method' ) == 'edit' ) {
				$out->addHTML( $this->displayForm( $giftId ) );
			} else {
				// If the user is allowed to create new gifts, show the
				// "add a gift" link to them
				if ( $this->canUserCreateGift() ) {
					$out->addHTML(
						'<div><b><a href="' .
						htmlspecialchars( $this->getPageTitle()->getFullURL( 'method=edit' ) ) .
						'">' . $this->msg( 'giftmanager-addgift' )->escaped() .
						'</a></b></div>'
					);
				}
				$out->addHTML( $this->displayGiftList() );
			}
		}
	}

	/**
	 * Function to check if the user can manage created gifts
	 *
	 * @return bool True if -
	 * - the user has the 'giftadmin' permission
	 * - ..or the max amount of custom user gifts is above zero
	 */
	function canUserManage() {
		global $wgMaxCustomUserGiftCount;

		$user = $this->getUser();

		if (
			$user->isAllowed( 'giftadmin' ) ||
			$wgMaxCustomUserGiftCount > 0
		) {
			return true;
		}

		return false;
	}

	/**
	 * Function to check if the user can delete created gifts
	 *
	 * @return bool True if:
	 * - user has 'giftadmin' permission
	 * - ..or a member of the giftadmin group, otherwise false
	 */
	function canUserDelete() {
		$user = $this->getUser();

		if ( $user->getBlock() ) {
			return false;
		}

		$services = MediaWikiServices::getInstance();

		if (
			$user->isAllowed( 'giftadmin' ) ||
			in_array( 'giftadmin', $services->getUserGroupManager()->getUserGroups( $user ) )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Function to check if the user can create new gifts
	 *
	 * @return bool True if user has 'giftadmin' permission, is
	 * - a member of the giftadmin group
	 * - or if $wgMaxCustomUserGiftCount has been defined, otherwise false
	 */
	private function canUserCreateGift() {
		global $wgMaxCustomUserGiftCount;

		$user = $this->getUser();

		if ( $user->getBlock() ) {
			return false;
		}

		$services = MediaWikiServices::getInstance();
		$createdCount = Gifts::getCustomCreatedGiftCount( $user );

		if (
			$user->isAllowed( 'giftadmin' ) ||
			in_array( 'giftadmin', $services->getUserGroupManager()->getUserGroups( $user ) ) ||
			( $wgMaxCustomUserGiftCount > 0 && $createdCount < $wgMaxCustomUserGiftCount )
		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display the text list of all existing gifts and a delete link to users
	 * who are allowed to delete gifts.
	 *
	 * @return string HTML
	 */
	function displayGiftList() {
		$output = ''; // Prevent E_NOTICE
		$request = $this->getRequest();
		$page = $request->getInt( 'page', 0 );
		$per_page = $request->getInt( 'per_page', 10 );
		$listLookup = new UserGiftListLookup( $this->getContext(), $per_page, $page );
		$gifts = $listLookup->getManagedGiftList();

		if ( $gifts ) {
			foreach ( $gifts as $gift ) {
				$deleteLink = '';
				if ( $this->canUserDelete() ) {
					$deleteLink = '<a href="' .
						htmlspecialchars( SpecialPage::getTitleFor( 'RemoveMasterGift' )->getFullURL( "gift_id={$gift['id']}" ) ) .
						'" style="font-size:10px; color:red;">' .
						$this->msg( 'delete' )->escaped() . '</a>';
				}

				$output .= '<div class="Item">
				<a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL( "id={$gift['id']}" ) ) . '">' .
					htmlspecialchars( $gift['gift_name'] ) . '</a> ' .
					$deleteLink . "</div>\n";
			}
		}

		$total = Gifts::getGiftCount( false );
		if ( ( $total > $per_page ) ) {
			$output .= $this->renderPagination( $total, $per_page, $page );
		}

		return '<div id="views">' . $output . '</div>';
	}

	/**
	 * Build the pagination links
	 *
	 * @see https://phabricator.wikimedia.org/T306748
	 *
	 * @param int $total Total amount of entries
	 * @param int $perPage Show this many entries per page
	 * @param int $page Current page number
	 * @return string HTML
	 */
	private function renderPagination( int $total, int $perPage, int $page ) {
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$pageTitle = $this->getPageTitle();

		// Quick sanity check first...
		if ( !$perPage || $perPage > 500 ) {
			$perPage = 10;
		}

		$numOfPages = $total / $perPage;
		$prevLink = [
			'page' => ( $page - 1 ),
			'per_page' => $perPage
		];
		$nextLink = [
			'page' => ( $page + 1 ),
			'per_page' => $perPage
		];

		$output = '';

		if ( $numOfPages > 1 ) {
			$output .= '<div class="mw-gift-manager-navigation">';

			if ( $page > 1 ) {
				$output .= $linkRenderer->makeLink(
					$pageTitle,
					$this->msg( 'g-prev' )->plain(),
					[],
					$prevLink
				) . ' ';
			}

			if ( ( $total % $perPage ) != 0 ) {
				$numOfPages++;
			}

			if ( $numOfPages >= 9 && $page < $total ) {
				$numOfPages = 9 + $page;
			}

			if ( $numOfPages >= ( $total / $perPage ) ) {
				$numOfPages = ( $total / $perPage ) + 1;
			}

			// @note I don't quite understand why I had to change the condition
			// to have ( $numOfPages - 1 ) instead of just what it was, which was
			// plain $numOfPages...but on my test wiki I had 6 gifts so with $perPage = 3,
			// that meant two pages, right? Except prior to changing this condition this
			// code would render a link to page 3, too, except said page was obviously
			// empty.
			// Note that I "borrowed" this code from ImageRating so the bug might still be present there?
			for ( $i = 1; $i <= ( $numOfPages - 1 ); $i++ ) {
				if ( $i == $page ) {
					$output .= ( $i . ' ' );
				} else {
					$output .= $linkRenderer->makeLink(
						$pageTitle,
						"$i",
						[],
						[
							'page' => $i,
							'per_page' => $perPage
						]
					) . ' ';
				}
			}

			if ( ( $total - ( $perPage * $page ) ) > 0 ) {
				$output .= ' ' . $linkRenderer->makeLink(
					$pageTitle,
					$this->msg( 'g-next' )->plain(),
					[],
					$nextLink
				);
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Display the form for editing an existing gift (if $gift_id is given) or creating a brand new one.
	 *
	 * @param int $gift_id ID of the gift to edit, if not creating a brand new gift
	 * @return string HTML
	 */
	function displayForm( $gift_id ) {
		$user = $this->getUser();

		if ( !$gift_id && !$this->canUserCreateGift() ) {
			return $this->displayGiftList();
		}

		$services = MediaWikiServices::getInstance();

		$form = '<div><b><a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL() ) .
			'">' . $this->msg( 'giftmanager-view' )->escaped() . '</a></b></div>';

		if ( $gift_id ) {
			$gift = Gifts::getGift( $gift_id );

			if (
				$user->getActorId() != $gift['creator_actor'] &&
				(
					!in_array( 'giftadmin', $services->getUserGroupManager()->getUserGroups( $user ) ) &&
					!$user->isAllowed( 'delete' )
				)
			) {
				throw new ErrorPageError( 'error', 'badaccess' );
			}
		}

		$form .= '<form action="" method="post" enctype="multipart/form-data" name="gift">';
		$form .= '<table border="0" cellpadding="5" cellspacing="0" width="500">';
		$form .= '<tr>
		<td width="200" class="view-form">' . $this->msg( 'g-gift-name' )->escaped() . '</td>
		<td width="695"><input type="text" size="45" class="createbox" name="gift_name" value="' .
			( isset( $gift['gift_name'] ) ? htmlspecialchars( $gift['gift_name'] ) : '' ) . '"/></td>
		</tr>
		<tr>
		<td width="200" class="view-form" valign="top">' . $this->msg( 'giftmanager-description' )->escaped() . '</td>
		<td width="695"><textarea class="createbox" name="gift_description" rows="2" cols="30">' .
			( isset( $gift['gift_description'] ) ? htmlspecialchars( $gift['gift_description'] ) : '' ) . '</textarea></td>
		</tr>';
		if ( $gift_id ) {
			// @phan-suppress-next-line PhanTypeArraySuspiciousNullable $gift de facto can't be null
			$creator = User::newFromActorId( $gift['creator_actor'] );
			$form .= '<tr>
			<td class="view-form">' .
				$this->msg( 'g-created-by', $creator->getName() )->parse() .
			'</td>
			<td><a href="' . htmlspecialchars( $creator->getUserPage()->getFullURL() ) . '">' .
				htmlspecialchars( $creator->getName() ) . '</a></td>
			</tr>';
		}

		// If the user isn't in the gift admin group, they can only create
		// private gifts
		if ( !$user->isAllowed( 'giftadmin' ) ) {
			$form .= '<input type="hidden" name="access" value="1" />';
		} else {
			$publicSelected = $privateSelected = '';
			if ( isset( $gift['access'] ) && $gift['access'] == 0 ) {
				$publicSelected = ' selected="selected"';
			}
			if ( isset( $gift['access'] ) && $gift['access'] == 1 ) {
				$privateSelected = ' selected="selected"';
			}
			$form .= '<tr>
				<td class="view-form">' . $this->msg( 'giftmanager-access' )->escaped() . '</td>
				<td>
				<select name="access">
					<option value="0"' . $publicSelected . '>' .
						$this->msg( 'giftmanager-public' )->escaped() .
					'</option>
					<option value="1"' . $privateSelected . '>' .
						$this->msg( 'giftmanager-private' )->escaped() .
					'</option>
				</select>
				</td>
			</tr>';
		}

		if ( $gift_id ) {
			$gml = SpecialPage::getTitleFor( 'GiftManagerLogo' );
			$userGiftIcon = new UserGiftIcon( $gift_id, 'l' );
			$icon = $userGiftIcon->getIconHTML();

			$form .= '<tr>
			<td width="200" class="view-form" valign="top">' . $this->msg( 'giftmanager-giftimage' )->escaped() . '</td>
			<td width="695">' . $icon .
			'<p>
			<a href="' . htmlspecialchars( $gml->getFullURL( 'gift_id=' . $gift_id ) ) . '">' .
				$this->msg( 'giftmanager-image' )->escaped() . '</a>
			</td>
			</tr>';
		}

		if ( isset( $gift['gift_id'] ) ) {
			$button = $this->msg( 'edit' )->escaped();
		} else {
			$button = $this->msg( 'g-create-gift' )->escaped();
		}

		$form .= '<tr>
			<td colspan="2">
				<input type="hidden" name="id" value="' . ( isset( $gift['gift_id'] ) && $gift['gift_id'] ? (int)$gift['gift_id'] : '' ) . '" />
				<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $user->getEditToken(), ENT_QUOTES ) . '" />
				<input type="submit" class="createbox" value="' . $button . '" size="20" />
				<input type="button" class="createbox" value="' . $this->msg( 'cancel' )->escaped() . '" size="20" onclick="history.go(-1)" />
			</td>
		</tr>
		</table>

		</form>';
		return $form;
	}
}
