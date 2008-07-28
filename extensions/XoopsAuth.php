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

//  ------------------------------------------------------------------------ //
// Author: Niluge_KiWi (kiwiiii@gmail.com), many parts from phppp (D.J.)     //
// ------------------------------------------------------------------------- //

if( !defined( 'MEDIAWIKI' ) ) {
  die();
}

$wgExtensionCredits['other'][] = array(

	"name" => "XoopsAuth",
	"author" => "Niluge_kiWi and phpp",
	"version" => "1.0 - july 2008",
	"url" => "http://dev.xoops.com",
	"description" => "xoops auto authentification for 'mediawiki for xoops'"

 );

require_once ( 'AuthPlugin.php' );

class XoopsAuth extends AuthPlugin {
// mediawiki for XOOPS - by D.J.
	
	var $instance;
	
	function XoopsAuth(){
		$this->instance = true;
		$GLOBALS["wgHooks"]['AutoAuthenticate'][] = array(&$this, "AutoAuthenticate");
		$GLOBALS["wgHooks"]['UserLogout'][] = array(&$this, "UserLogout");
	}
	
	function AutoAuthenticate(&$wgUser){
		$wgUser = User::newFromSession();
		if ( is_object($GLOBALS["xoopsUser"]) ) {
			$wgUser->mId = $GLOBALS["xoopsUser"]->getVar("uid");
			$wgUser->mName = mediawiki_encoding_xoops2mediawiki($GLOBALS["xoopsUser"]->getVar("uname"));
			$wgUser->mEmail = $GLOBALS["xoopsUser"]->getVar("email");
			$wgUser->mRealName = mediawiki_encoding_xoops2mediawiki($GLOBALS["xoopsUser"]->getVar("name"));
			if($GLOBALS["xoopsUser"]->isAdmin()){
				$wgUser->mGroups[] = 'sysop';
			}
			$effectiveGroups = array_merge( array( '*', 'user' ), $wgUser->mGroups );
			$wgUser->mRights = $wgUser->getGroupPermissions( $effectiveGroups );
		}else{
			$wgUser->mId = 0;
		}
		if ( !isset( $_SESSION['wsUserID'] ) || $_SESSION['wsUserID'] != $wgUser->mId ) {
			/** Clear client-side caching of pages */
			$GLOBALS["wgCachePages"] = false;
		}
		$_SESSION['wsUserID'] = $wgUser->mId;
		return true;
	}
	
	function UserLogout(&$wgUser){
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
	    
	    //redirect_header(XOOPS_URL.'/user.php?op=logout&amp;xoops_redirect='.$_SERVER['REQUEST_URI']);
	    return true;
    }

}



?>
