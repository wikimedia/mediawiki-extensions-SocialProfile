<?php
/**
 * Special page for creating and editing user-to-user gifts.
 *
 * @file
 */
class GiftManager extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'GiftManager'/*class*/, 'giftadmin'/*restriction*/ );
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
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		$out->setPageTitle( $this->msg( 'giftmanager' )->plain() );

		// Make sure that the user is logged in and that they can use this
		// special page
		if ( $user->isAnon() || !$this->canUserManage() ) {
			throw new ErrorPageError( 'error', 'badaccess' );
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$out->readOnlyPage();
			return;
		}

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.usergifts.css' );

		if ( $request->wasPosted() ) {
			if ( !$request->getInt( 'id' ) ) {
				$giftId = Gifts::addGift(
					$request->getVal( 'gift_name' ),
					$request->getVal( 'gift_description' ),
					$request->getInt( 'access' )
				);
				$out->addHTML(
					'<span class="view-status">' .
					$this->msg( 'giftmanager-giftcreated' )->plain() .
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
					$this->msg( 'giftmanager-giftsaved' )->plain() .
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
						'">' . $this->msg( 'giftmanager-addgift' )->plain() .
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
	 * @return Boolean: true if user has 'giftadmin' permission or is
	 *			a member of the giftadmin group, otherwise false
	 */
	function canUserManage() {
		global $wgMaxCustomUserGiftCount;

		$user = $this->getUser();

		if ( $user->isBlocked() ) {
			return false;
		}

		if ( $wgMaxCustomUserGiftCount > 0 ) {
			return true;
		}

		if (
			$user->isAllowed( 'giftadmin' ) ||
			in_array( 'giftadmin', $user->getGroups() )
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * Function to check if the user can delete created gifts
	 *
	 * @return Boolean: true if user has 'giftadmin' permission or is
	 *			a member of the giftadmin group, otherwise false
	 */
	function canUserDelete() {
		$user = $this->getUser();

		if ( $user->isBlocked() ) {
			return false;
		}

		if (
			$user->isAllowed( 'giftadmin' ) ||
			in_array( 'giftadmin', $user->getGroups() )
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * Function to check if the user can create new gifts
	 *
	 * @return Boolean: true if user has 'giftadmin' permission, is
	 *			a member of the giftadmin group or if $wgMaxCustomUserGiftCount
	 *			has been defined, otherwise false
	 */
	function canUserCreateGift() {
		global $wgMaxCustomUserGiftCount;

		$user = $this->getUser();

		if ( $user->isBlocked() ) {
			return false;
		}

		$createdCount = Gifts::getCustomCreatedGiftCount( $user->getID() );
		if (
			$user->isAllowed( 'giftadmin' ) ||
			in_array( 'giftadmin', $user->getGroups() ) ||
			( $wgMaxCustomUserGiftCount > 0 && $createdCount < $wgMaxCustomUserGiftCount )
		)
		{
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display the text list of all existing gifts and a delete link to users
	 * who are allowed to delete gifts.
	 *
	 * @return String: HTML
	 */
	function displayGiftList() {
		$output = ''; // Prevent E_NOTICE
		$page = 0;
		/**
		 * @todo FIXME: this is a dumb hack. The value of this variable used to
		 * be 10, but then it would display only the *first ten* gifts, as this
		 * special page seems to lack pagination.
		 * @see https://www.mediawiki.org/w/index.php?oldid=988111#Gift_administrator_displays_10_gifts_only
		 */
		$per_page = 1000;
		$gifts = Gifts::getManagedGiftList( $per_page, $page );
		if ( $gifts ) {
			foreach ( $gifts as $gift ) {
				$deleteLink = '';
				if ( $this->canUserDelete() ) {
					$deleteLink = '<a href="' .
						htmlspecialchars( SpecialPage::getTitleFor( 'RemoveMasterGift' )->getFullURL( "gift_id={$gift['id']}" ) ) .
						'" style="font-size:10px; color:red;">' .
						$this->msg( 'delete' )->plain() . '</a>';
				}

				$output .= '<div class="Item">
				<a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL( "id={$gift['id']}" ) ) . '">' .
					$gift['gift_name'] . '</a> ' .
					$deleteLink . "</div>\n";
			}
		}
		return '<div id="views">' . $output . '</div>';
	}

	function displayForm( $gift_id ) {
		$user = $this->getUser();

		if ( !$gift_id && !$this->canUserCreateGift() ) {
			return $this->displayGiftList();
		}

		$form = '<div><b><a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL() ) .
			'">' . $this->msg( 'giftmanager-view' )->plain() . '</a></b></div>';

		if ( $gift_id ) {
			$gift = Gifts::getGift( $gift_id );
			if (
				$user->getID() != $gift['creator_user_id'] &&
				(
					!in_array( 'giftadmin', $user->getGroups() ) &&
					!$user->isAllowed( 'delete' )
				)
			)
			{
				throw new ErrorPageError( 'error', 'badaccess' );
			}
		}

		$form .= '<form action="" method="post" enctype="multipart/form-data" name="gift">';
		$form .= '<table border="0" cellpadding="5" cellspacing="0" width="500">';
		$form .= '<tr>
		<td width="200" class="view-form">' . $this->msg( 'g-gift-name' )->plain() . '</td>
		<td width="695"><input type="text" size="45" class="createbox" name="gift_name" value="' .
			( isset( $gift['gift_name'] ) ? $gift['gift_name'] : '' ) . '"/></td>
		</tr>
		<tr>
		<td width="200" class="view-form" valign="top">' . $this->msg( 'giftmanager-description' )->plain() . '</td>
		<td width="695"><textarea class="createbox" name="gift_description" rows="2" cols="30">' .
			( isset( $gift['gift_description'] ) ? $gift['gift_description'] : '' ) . '</textarea></td>
		</tr>';
		if ( $gift_id ) {
			$creator = Title::makeTitle( NS_USER, $gift['creator_user_name'] );
			$form .= '<tr>
			<td class="view-form">' .
				$this->msg( 'g-created-by', $gift['creator_user_name'] )->parse() .
			'</td>
			<td><a href="' . htmlspecialchars( $creator->getFullURL() ) . '">' .
				$gift['creator_user_name'] . '</a></td>
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
				<td class="view-form">' . $this->msg( 'giftmanager-access' )->plain() . '</td>
				<td>
				<select name="access">
					<option value="0"' . $publicSelected . '>' .
						$this->msg( 'giftmanager-public' )->plain() .
					'</option>
					<option value="1"' . $privateSelected . '>' .
						$this->msg( 'giftmanager-private' )->plain() .
					'</option>
				</select>
				</td>
			</tr>';
		}

		if ( $gift_id ) {
			global $wgUploadPath;
			$gml = SpecialPage::getTitleFor( 'GiftManagerLogo' );
			$gift_image = '<img src="' . $wgUploadPath . '/awards/' .
				Gifts::getGiftImage( $gift_id, 'l' ) . '" border="0" alt="' .
				$this->msg( 'g-gift' )->plain() . '" />';
			$form .= '<tr>
			<td width="200" class="view-form" valign="top">' . $this->msg( 'giftmanager-giftimage' )->plain() . '</td>
			<td width="695">' . $gift_image .
			'<p>
			<a href="' . htmlspecialchars( $gml->getFullURL( 'gift_id=' . $gift_id ) ) . '">' .
				$this->msg( 'giftmanager-image' )->plain() . '</a>
			</td>
			</tr>';
		}

		if ( isset( $gift['gift_id'] ) ) {
			$button = $this->msg( 'edit' )->plain();
		} else {
			$button = $this->msg( 'g-create-gift' )->plain();
		}

		$form .= '<tr>
			<td colspan="2">
				<input type="hidden" name="id" value="' . ( isset( $gift['gift_id'] ) ? $gift['gift_id'] : '' ) . '" />
				<input type="button" class="createbox" value="' . $button . '" size="20" onclick="document.gift.submit()" />
				<input type="button" class="createbox" value="' . $this->msg( 'cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
			</td>
		</tr>
		</table>

		</form>';
		return $form;
	}
}
