<?php

/*
This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*/

/**
 * This file is a MediaWiki extension which permit MediaWiki to authenticate 
 * Xoops users. This forces users to have a Xoops account in order to log into
 * MediaWiki.
 *
 * It has to be used with some modifications in Xoops and in MediaWiki and 
 * should not be released alone.
 *
 * @package MediaWiki
 * @subpackage XoopsAuth
 * @author Niluge_KiWi (kiwiiii@gmail.com), parts from phppp (D.J.)
 * @copyright 2006-2008 Xoops
 * @license http://www.gnu.org/copyleft/gpl.html
 * @link http://www.xoops.org
 * @version 1.0
 *
 */



if( !defined( 'MEDIAWIKI' ) ) {
  die();
}

$wgExtensionCredits['other'][] = array(

	"name" => "XoopsAuth",
	"author" => "Niluge_kiWi and phppp",
	"version" => "1.0 - july-august 2008",
	"url" => "http://dev.xoops.com",
	"description" => "xoops authentication for 'mediawiki for xoops'"

 );

require_once ( 'AuthPlugin.php' );

class XoopsAuth extends AuthPlugin {
	
	/** 
	 * Xoops uid and mediawiki mId are not synchronized, 
	 * this could maybe changed, but it would break the clean MediaWiki database 
	 * (mediawiki could no more be used alone)
	 */
	var $xoops_uid; // currently not yet used

	/**
	 * XoopsAuth Constructor
	 *  it adds the hooks used by this extension
	 *
	 * @public
	 */
	function XoopsAuth(){
		global $wgHooks, $wgGroupPermissions;
		// Until MediaWiki 1.12
		$wgHooks['AutoAuthenticate'][] = array(&$this, "AutoAuthenticate");
		// Since MediaWiki 1.13
		$wgHooks['UserLoadFromSession'][] = array(&$this, "UserLoadFromSession");

		$wgHooks['PersonalUrls'][] = array(&$this, "PersonalUrls"); /* Hook to replace login link */
		$wgHooks['UserLogout'][] = array(&$this, "UserLogout");

		// disable registration
		//$wgGroupPermissions['*']['edit'] = false; // MediaWiki 1.5+ Settings
		$wgGroupPermissions['*']['createaccount'] = false; // MediaWiki 1.5+ Settings
	}
	
	/**
	 * Return the UserName of the Xoops user
	 *
	 * @return string
	 * @private
	 */
	function getCurrentXoopsUserName() {
		if ( !is_object($GLOBALS["xoopsUser"]) ) {
			return NULL; //username is anonymous
		}
		return mediawiki_username_xoops2mediawiki($GLOBALS["xoopsUser"]->getVar("uname"));
	}

	/**
	 * AutoAuthenticate is the function called by the hook with the same name.
	 * It tries to auto authenticate the user with its xoops session.
	 *
	 * @param &$wgUser User object: the user to be authenticated.
	 * @public
	 */
	function AutoAuthenticate(&$wgUser){
		// Inspiration from AuthDrupal and code from phppp

		$rc = $wgUser->load();
		// If there's a prior session, check that it matches the current Drupal user
		if ($rc && $wgUser->isLoggedIn()) {
			if ( $this->authenticate( $wgUser->getName(), '' ) ) {
				// update email, real name, etc.
				$this->updateUser( $wgUser );

				return true;

			} else {
				// log out the existing user and continue below to start over
				$wgUser->logout();
			}
		}

		$username = $this->getCurrentXoopsUserName();

		if ( empty( $username ) ) {
			/* user is not logged-in in Xoops */
			$wgUser->setId(0); // probably useful only
			return false; // used only by UserLoadFromSession with MediaWiki >=1.13
		}

		$wgUser = User::newFromName( $username );

		// is it a new user?
		if ( 0 == $wgUser->getID() ) {
			// we have a new user to add...
			$this->initUser($wgUser, true);
		} 

		// update email, real name, rights, and user password if is a real Xoops user
		$this->updateUser( $wgUser );

		// Go ahead and log 'em in
		$wgUser->setToken();
		$wgUser->saveSettings();
		$wgUser->setupSession();
		$wgUser->setCookies();
		$this->xoops_uid = $wgUser->getID();

		return true;
	}

	/**
	 * UserLoadFromSession is the function called by the hook with the same name.
	 * It tries to auto authenticate the user with its xoops session.
	 *
	 * @param &$wgUser User object: the user to be authenticated.
	 * @public
	 */
	function UserLoadFromSession($user, &$result) {
		return $this->AutoAuthenticate(&$user);
	}

	/**
	 * PersonalUrls is the function called by the hook with the same name.
	 * It modify the url bar on the top right:
	 * - login link is now xoops login
	 * - adds a link to change the style to the xoops one
	 *
	 * @param &$wgUser User object: the user to be logged-out.
	 * @public
	 */
	function PersonalUrls(&$personal_urls, &$title) {
		global $wgUser, $wgRequest, $wgTitle;

		if ( !$wgUser->isLoggedIn() ) {
			/* the modifications are here only for anonymous users */
			//FIXME: when we disconnect from MediaWiki, this code is executed before disconnecting, 
			// so that user is still connected and the link to connect is wrong

			if ( $GLOBALS['wgShowIPinHeader'] ) {
				$personal_urls['anonlogin']['href'] = XOOPS_URL . '/user.php?xoops_redirect=' . urlencode($wgRequest->getRequestURL());
			} else {
				$personal_urls['login']['href']     = XOOPS_URL . '/user.php?xoops_redirect=' . urlencode($wgRequest->getRequestURL());
			}
		}

		/* link to the Xoops skin */
		if($GLOBALS['xoopsModuleConfig']["style"] == 0) {
			$personal_urls['changestyle'] = array(
				'text' => mediawiki_getStyle()?_MD_MEDIAWIKI_MEDIAWIKIMODE:_MD_MEDIAWIKI_XOOPSMODE,
				'href' => $wgRequest->appendQuery( "style=".(mediawiki_getStyle()?"m":"x") )
			);
		}
		return true;
	}
	
	/**
	 * UserLogout is the function called by the hook with the same name.
	 * It logs-out the user
	 *
	 * @param &$wgUser User object: the user to be logged-out.
	 * @public
	 */
	function UserLogout(&$wgUser){
		// Author: phppp
		global $xoopsConfig, $xoopsUser, $_SESSION;
		
		$_SESSION = array();
		session_destroy();
		if ($xoopsConfig['use_mysession'] && $xoopsConfig['session_name'] != '') {
			setcookie($xoopsConfig['session_name'], '', time()- 3600, '/',  '', 0);
		}
		// clear entry from online users table
		if (is_object($xoopsUser)) {
			$online_handler =& xoops_gethandler('online');
			$online_handler->destroy($wgUser->mId);
			unset($xoopsUser);
		}
		return true;
	}


/* And now for something completely different: the methods of AuthPlugin */

	/**
	 * Check whether there exists a user account with the given name.
	 * The name will be normalized to MediaWiki's requirements, so
	 * you might need to munge it (for instance, for lowercase initial
	 * letters).
	 *
	 * @param $username String: username.
	 * @return bool
	 * @public
	 */
	function userExists( $username ) {
		//TODO: do something here? Probably not because it seems to be used when login from MediaWiki form
		return true;
	}

	/**
	 * Check if a username+password pair is a valid login.
	 * The name will be normalized to MediaWiki's requirements, so
	 * you might need to munge it (for instance, for lowercase initial
	 * letters).
	 *
	 * @param $username String: username.
	 * @param $password String: user password.
	 * @return bool
	 * @public
	 */
	function authenticate( $username, $password ) {
		/* NK: This is only used internally, and not from the MediaWiki login form since it's desactivated */		
		
		return $this->getCurrentXoopsUserName() == $username;
	}

	/**
	 * Modify options in the login template.
	 *
	 * @param $template UserLoginTemplate object.
	 * @public
	 */
	function modifyUITemplate( &$template ) {
		/* NK: We do not log in from MediaWiki, so the login template should not be rendered */
                //$template->set('domain',      false);  // Don't get in touch with domain
                //$template->set('usedomain',   false); // We do not want a domain name.
                //$template->set('create',      false); // Remove option to create new accounts from the wiki.
                //$template->set('useemail',    false); // Disable the mail new password box.
                //$template->set('remember',    false); // Disable 'remember me' box
	}

	/**
	 * Set the domain this plugin is supposed to use when authenticating.
	 *
	 * @param $domain String: authentication domain.
	 * @public
	 */
	function setDomain( $domain ) {
		/* NK: We do not use this */
		$this->domain = $domain;
	}

	/**
	 * Check to see if the specific domain is a valid domain.
	 *
	 * @param $domain String: authentication domain.
	 * @return bool
	 * @public
	 */
	function validDomain( $domain ) {
		/* NK: Probably not used */
		return true;
	}

	/**
	 * When a user logs in, optionally fill in preferences and such.
	 * For instance, you might pull the email address or real name from the
	 * external user database.
	 *
	 * The User object is passed by reference so it can be modified; don't
	 * forget the & on your function declaration.
	 *
	 * @param User $user
	 * @public
	 */
	function updateUser( &$user ) {

		if($GLOBALS["xoopsUser"]->isAdmin()){
			//TODO: maybe add also the "bureaucrat" group
			$user->addGroup("sysop");
		}
		//TODO: it seems mediawiki now use an other table for that, so, we have to look at the code, and probably change this
		$effectiveGroups = array_merge( array( '*', 'user' ), $user->mGroups );
		$user->mRights = $user->getGroupPermissions( $effectiveGroups );
		
		$user->setEmail($GLOBALS["xoopsUser"]->getVar("email"));
		$user->setRealName(mediawiki_encoding_xoops2mediawiki($GLOBALS["xoopsUser"]->getVar("name")));

		/* get the xoops encrypted password, only works with Xoops user, and not users that can be looged in by Xoops with other ways */
		UpdateMediawikiEncryptedPasswordFromXoopsUser(&$user, $GLOBALS["xoopsUser"]->getVar("uid"));		
		/*global $xoopsDB;
		$sql = 'SELECT pass FROM '.$xoopsDB->prefix('users').' WHERE uid='.$GLOBALS["xoopsUser"]->getVar("uid");
		$result = $xoopsDB->query($sql, 1, 0);
		if ($result) {
            		list($encryptedPassword) = $xoopsDB->fetchRow($result);
			$user->mPassword = $encryptedPassword;
			$user->mNewpassword = '';
			$user->mNewpassTime = null;
        	}*/
		
		return true;
	}


	/**
	 * Return true if the wiki should create a new local account automatically
	 * when asked to login a user who doesn't exist locally but does in the
	 * external auth database.
	 *
	 * If you don't automatically create accounts, you must still create
	 * accounts in some way. It's not possible to authenticate without
	 * a local account.
	 *
	 * This is just a question, and shouldn't perform any actions.
	 *
	 * @return bool
	 * @public
	 */
	function autoCreate() {
		/* NK: We create a new user in the MediaWiki DataBase in order not to have to hack many files in MediaWiki */
		return true;
	}

	/**
	 * Can users change their passwords?
	 *
	 * @return bool
	 */
	function allowPasswordChange() {
		return false;
	}

	/**
	 * Set the given password in the authentication database.
	 * As a special case, the password may be set to null to request
	 * locking the password to an unusable value, with the expectation
	 * that it will be set later through a mail reset or other method.
	 *
	 * Return true if successful.
	 *
	 * @param $user User object.
	 * @param $password String: password.
	 * @return bool
	 * @public
	 */
	function setPassword( $user, $password ) {
		/* NK: Since we do not allow to change password, we do nothing here */
		return false;
	}

	/**
	 * Update user information in the external authentication database.
	 * Return true if successful.
	 *
	 * @param $user User object.
	 * @return bool
	 * @public
	 */
	function updateExternalDB( $user ) {
		//TODO: do something here?
		// complicated because xoops can forbid to change the email adress, but mediawiki cant...
		// but we could at least update when we can... Because the infos from xoops overwrite MediaWiki ones at each login in MediaWiki.
		return true;
	}

	/**
	 * Check to see if external accounts can be created.
	 * Return true if external accounts can be created.
	 * @return bool
	 * @public
	 */
	function canCreateAccounts() {
		return false;
	}

	/**
	 * Add a user to the external authentication database.
	 * Return true if successful.
	 *
	 * @param User $user - only the name should be assumed valid at this point
	 * @param string $password
	 * @param string $email
	 * @param string $realname
	 * @return bool
	 * @public
	 */
	function addUser( $user, $password, $email='', $realname='' ) {
		/* NK: Since we do not allow to create users from MediaWiki, we do nothing here */
		return false;
	}


	/**
	 * Return true to prevent logins that don't authenticate here from being
	 * checked against the local database's password fields.
	 *
	 * This is just a question, and shouldn't perform any actions.
	 *
	 * @return bool
	 * @public
	 */
	function strict() {
		/* NK: the user authentication is done with Xoops, and not in MediaWiki */
		return true;
	}

	/**
	 * Check if a user should authenticate locally if the global authentication fails.
	 * If either this or strict() returns true, local authentication is not used.
	 *
	 * @param $username String: username.
	 * @return bool
	 * @public
	 */
	function strictUserAuth( $username ) {
		/**
		 * NK: the user authentication is done with Xoops, and not in MediaWiki,
		 *  even if it fails in Xoops
		 */
		return true;
	}

	/**
	 * When creating a user account, optionally fill in preferences and such.
	 * For instance, you might pull the email address or real name from the
	 * external user database.
	 *
	 * The User object is passed by reference so it can be modified; don't
	 * forget the & on your function declaration.
	 *
	 * @param $user User object.
	 * @param $autocreate bool True if user is being autocreated on login
	 * @public
	 */
	function initUser( &$u, $autocreate=false ) {
		// code from LoginForm::initUser()
		$u->addToDatabase();

		if ($autocreate) {
			$this->updateUser($u);
		}
		$u->setToken();

		$u->setOption( 'rememberpassword', 0 );
		$u->saveSettings();

		# Update user count
		$ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
		$ssUpdate->doUpdate();
	}

	/**
	 * If you want to munge the case of an account name before the final
	 * check, now is your chance.
	 */
	function getCanonicalName( $username ) {
		//TODO: test, and maybe use mediawiki_username_xoops2mediawiki($username) instead
		//global $wgContLang;
		//return $wgContLang->ucfirst($username);
		return $username;
	}
}



?>
