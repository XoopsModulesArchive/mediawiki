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

function &mediawiki_search($queryarray, $andor, $limit, $offset, $userid)
{
	global $xoopsDB;
	
	global $wgContLang;
	if(!defined('MEDIAWIKI')):
	define( 'MEDIAWIKI', true );
	require_once XOOPS_ROOT_PATH."/modules/mediawiki/LocalSettings.php";
	require_once XOOPS_ROOT_PATH."/modules/mediawiki/includes/GlobalFunctions.php";
	require_once XOOPS_ROOT_PATH."/modules/mediawiki/include/functions.php";
	endif;
		
	
	$where = array("(rc_minor = 0)");
	if ( is_array($queryarray) && $count = count($queryarray) ) {
		$str_query = array();
		for($i = 0; $i < $count; $i ++){
			$query = mediawiki_encoding_xoops2mediawiki($queryarray[$i]);
			$str_query[] = "(rc_title LIKE '%".$query."%' OR rc_comment LIKE '%".$query."%')";
		}
		$where[] = "(".implode(" $andor ", $str_query).")";
	}
	if ($userid) {
		$userid = intval($userid);
		$where[] = "(rc_user=".$userid.")";
	}
	
	$version = mysql_get_server_info();
	if(version_compare( $version, "4.1.0", "ge" ) ):
	$sql = "SELECT DISTINCT rc_cur_id, rc_timestamp AS rc_update, rc_title, rc_type, rc_namespace, rc_comment, rc_user, rc_user_text, rc_this_oldid  FROM " . $xoopsDB->prefix("mediawiki_recentchanges").
		" WHERE rc_timestamp = ( SELECT MAX(aa.rc_timestamp) FROM " . $xoopsDB->prefix("mediawiki_recentchanges")." AS aa WHERE aa.rc_cur_id = " . $xoopsDB->prefix("mediawiki_recentchanges").".rc_cur_id)".
		(($where)?" AND ".implode(" AND ", $where):"").
		" ORDER BY rc_update DESC";
	else:
	$sql = "SELECT DISTINCT rc_cur_id, rc_timestamp AS rc_update, rc_title, rc_type, rc_namespace, rc_comment, rc_user, rc_user_text, rc_this_oldid  FROM " . $xoopsDB->prefix("mediawiki_recentchanges").
		(($where)?" WHERE ".implode(" AND ", $where):"").
		" ORDER BY rc_update DESC";
	endif;
	$ret = array();
    if (!$result = $xoopsDB->query($sql, $limit, $offset)) {
        return $ret;
    }
	$myts =& MyTextSanitizer::getInstance();
	$rec = array();
	while($row = $xoopsDB->fetchArray($result)){
		//if(isset($rec[$row["rc_cur_id"]])) continue;
		$rec[$row["rc_cur_id"]] = 1;
	    $_item = array();
	    $_item["new"] = ($row["rc_type"] == 1);
	    $_item["title"] = htmlspecialchars(mediawiki_encoding_mediawiki2xoops(str_replace("_", " ", $row["rc_title"])));
	    $_item["link"] = XOOPS_URL."/modules/mediawiki/index.php?title=".wfUrlencode(str_replace(" ", "_", $row["rc_title"]))."&amp;curid=".$row["rc_cur_id"]."&amp;oldid=".$row["rc_this_oldid"];
	    $_item["time"] = wfTimestamp( TS_UNIX, $row["rc_update"] );
	    $_item["uid"] = $row["rc_user"];
	    $ret[] = $_item;
	    unset($_item);
	}
	unset($rec);
	return $ret;
}
?>