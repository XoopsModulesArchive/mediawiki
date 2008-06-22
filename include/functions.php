<?php
// $Id: xoops_version.php,v 1.8 2005/06/03 01:35:02 phppp Exp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: phppp (D.J.)                                                      //
// URL: http://xoopsforge.com, http://xoops.org.cn                           //
// ------------------------------------------------------------------------- //
 
include_once (XOOPS_ROOT_PATH."/Frameworks/art/functions.php");

if(!defined("MEDIAWIKI_FUNCTIONS")):
define("MEDIAWIKI_FUNCTIONS", 1);

/**
 * convert username from XOOPS to mediawiki
 * 
 * Xoops user identity is mapped to mediawiki user due to mediawiki's link rule
 *
 * The constant "MEDIAWIKI_USERPREFIX" is used to protect Xoops username,
 * however, the protection is still not complete, an example: Xoops_user and Xoops-user share the same link of 
 * User:Xo.Xoops_user (suppose MEDIAWIKI_USERPREFIX is defined as "Xo.")
 *
 * @param	string	$text	the content to be converted
 * @return 	string 	converted content
 */
function mediawiki_username_xoops2mediawiki($text)
{
	return mediawiki_encoding_xoops2mediawiki(MEDIAWIKI_USERPREFIX.$text);
}

/**
 * convert username from mediawiki to XOOPS
 * 
 * @param	string	$text	the content to be converted
 * @return 	string 	converted content
 */
function mediawiki_username_mediawiki2xoops($text)
{
	return mediawiki_encoding_mediawiki2xoops(preg_replace("/^".preg_quote(MEDIAWIKI_USERPREFIX)."/", "", $text));
}

/**
 * convert data from XOOPS to mediawiki
 * 
 * @param	string	$text	the content to be converted
 * @param	bool	$fromOutput	TRUE for from output; FALSE for internal conversion
 * @param	bool	$forOutput	TRUE for client side output; FALSE for internal conversion
 * @return 	string 	converted content
 */
function mediawiki_encoding_xoops2mediawiki($text, $fromOutput = false, $forOutput = false)
{
	global $xlanguage, $wgInputEncoding, $wgOutputEncoding;
	$from_charset = ( empty($fromOutput) && !empty($xlanguage["action"]) && !empty($xlanguage['charset_base']) ) ? $xlanguage['charset_base'] : _CHARSET;
	$to_charset = empty($forOutput)?$wgInputEncoding:$wgOutputEncoding;
	$text = XoopsLocal::convert_encoding($text, $to_charset, $from_charset);
	return $text;
}

/**
 * convert data from mediawiki to XOOPS
 * 
 * @param	string	$text	the content to be converted
 * @param	bool	$fromOutput	TRUE for from output; FALSE for internal conversion
 * @param	bool	$forOutput	TRUE for client side output; FALSE for internal conversion
 * @return 	string 	converted content
 */
function mediawiki_encoding_mediawiki2xoops($text, $forOutput = false)
{
	global $xlanguage, $wgInputEncoding;
	
	$to_charset = ( empty($forOutput) && !empty($xlanguage["action"]) && !empty($xlanguage['charset_base']) ) ? $xlanguage['charset_base'] : _CHARSET;
	$from_charset = empty($wgInputEncoding)?"utf-8":$wgInputEncoding;
	$text = XoopsLocal::convert_encoding($text, $to_charset, $from_charset);
	return $text;
}

/**
 * Function to a list of user names associated with their user IDs
 * 
 */
function &mediawiki_getUnameFromId( $userid, $usereal = 0, $linked = false )
{
	if(!is_array($userid))  $userid = array($userid);
	$users = mod_getUnameFromIds($userid, $usereal, $linked);

    return $users;
}


/**
 * Get theme style
 *  
 * The front-page style could be:
 * 1. Xoops style: mediawiki wrapped inside XOOPS as a regular module
 * 2. mediawiki style: same style as a standalone mediawiki
 * 3. Selectable: end users can switch the style on-fly
 *
 */
function mediawiki_getStyle()
{
	static $style;
	if(isset($style)){
		return intval($style);
	}	
	
	switch($GLOBALS['xoopsModuleConfig']["style"]){
		// fixed as XOOPS
		case 1:
			$style = 1;
			break;
		// fixed as MediaWiki
		case 2:
			$style = 0;
			break;
		// selectable
		default:
			$style = isset($_GET["style"])?$_GET["style"]:(isset($_COOKIE["mw_style"])?$_COOKIE["mw_style"]:"");
			$style = ($style=="m")?0:1;
			if(isset($_GET["style"])){
				setcookie("mw_style", $_GET["style"], time()+3600, "/", "", 0);
			}
			break;
	}
	return intval($style);
}
endif;
?>