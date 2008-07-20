<?php
/**
 *
 * @package MediaWiki
 * @subpackage SpecialPage
 */

if(!defined('MEDIAWIKI')):
define( 'MEDIAWIKI', true );
require_once XOOPS_ROOT_PATH."/modules/mediawiki/includes/Defines.php";
require_once XOOPS_ROOT_PATH."/modules/mediawiki/LocalSettings.php";
require_once XOOPS_ROOT_PATH."/modules/mediawiki/includes/GlobalFunctions.php";
require_once XOOPS_ROOT_PATH."/modules/mediawiki/include/functions.php";
endif;

/*
 * Display navigation siderbar
 *
 * Not ready yet
 */
function mediawiki_sidebar_show() {
	$block = array();
	$block[] = array("title"=>_MB_MEDIAWIKI_SPECIALPAGES,
					"link"=>XOOPS_URL."/modules/mediawiki/index.php?title=Special:Specialpages"
					);
	$block[] = array("title"=>_MB_MEDIAWIKI_RECENTCHANGES,
					"link"=>XOOPS_URL."/modules/mediawiki/index.php?title=Special:Recentchanges"
					);
	$block[] = array("title"=>_MB_MEDIAWIKI_RANDOM,
					"link"=>XOOPS_URL."/modules/mediawiki/index.php?title=Special:Random"
					);
	$block[] = array("title"=>_MB_MEDIAWIKI_HELP,
					"link"=>XOOPS_URL."/modules/mediawiki/index.php?title=Help:Contents"
					);
	$block[] = array("title"=>_MB_MEDIAWIKI_MWMODE,
					"link"=>XOOPS_URL."/modules/mediawiki/index.php?style=m"
					);
	return $block;
}
 
function mediawiki_recentchanges_show( $options ) {
	global $xoopsDB;
	
	$block = array();
	$limit = $options[0];
	if ( $limit <= 0 || $limit > 5000 ) $limit = 10;

	$version = mysql_get_server_info();
	if(version_compare( $version, "4.1.0", "ge" ) ):
	
	$sql = "SELECT DISTINCT rc_cur_id, rc_timestamp AS rc_update, rc_title, rc_type, rc_namespace, rc_comment, rc_user, rc_user_text, rc_this_oldid  FROM " . $xoopsDB->prefix("mediawiki_recentchanges").
		" WHERE rc_timestamp = ( SELECT MAX(aa.rc_timestamp) FROM " . $xoopsDB->prefix("mediawiki_recentchanges")." AS aa WHERE aa.rc_cur_id = " . $xoopsDB->prefix("mediawiki_recentchanges").".rc_cur_id)".
		" 	AND rc_minor = 0".
		" ORDER BY rc_update DESC";
	else:
	$sql = "SELECT DISTINCT rc_cur_id, rc_timestamp AS rc_update, rc_title, rc_type, rc_namespace, rc_comment, rc_user, rc_user_text, rc_this_oldid  FROM " . $xoopsDB->prefix("mediawiki_recentchanges").
		" WHERE rc_minor =0".
		" ORDER BY rc_update DESC";
	endif;
    if (!$result = $xoopsDB->query($sql, $limit, 0)) {
        return $block;
    }
    $rows = array();
    while ($row = $xoopsDB->fetchArray($result)) {
        $rows[] = $row;
        $author[$row["rc_user"]] = 1;
    }
    if (count($rows) < 1) return $block;
	$authors_name = mediawiki_getUnameFromId(array_keys($author), false, true);
    foreach ($rows as $row) {
	    $_item = array();
	    $_item["new"] = ($row["rc_type"] == 1);
	    $_item["desc"] = htmlspecialchars(mediawiki_encoding_mediawiki2xoops($row["rc_comment"]));
	    $_item["title"] = htmlspecialchars(mediawiki_encoding_mediawiki2xoops(str_replace("_", " ", $row["rc_title"])));
	    $_item["link"] = XOOPS_URL."/modules/mediawiki/index.php?title=".wfUrlencode(str_replace(" ", "_", $row["rc_title"]))."&amp;curid=".$row["rc_cur_id"]."&amp;oldid=".$row["rc_this_oldid"];
	    $_item["time"] = formatTimestamp(wfTimestamp( TS_UNIX, $row["rc_update"] ), "s");
	    $_item["author"] = $authors_name[$row["rc_user"]];
	    $block[] = $_item;
	    unset($_item);
    }
    return $block;
}

function mediawiki_recentchanges_edit($options)
{
    $form = _MB_MEDIAWIKI_ITEMS."&nbsp;&nbsp;<input type=\"text\" name=\"options[0]\" value=\"" . $options[0] . "\" /><br /><br />";
    return $form;
}
 
function mediawiki_top_show( $options ) {
	global $xoopsDB;
	
	$block = array();
	$limit = $options[0];
	if ( $limit <= 0 || $limit > 5000 ) $limit = 10;
	$revision = $xoopsDB->prefix("mediawiki_revision");	
	$page = $xoopsDB->prefix("mediawiki_page");	
	$sql = "
			SELECT
				page_id as id,
				page_namespace as namespace,
				page_title as title,
				page_counter as clicks,
				COUNT(*) as value
			FROM $revision
			LEFT JOIN $page ON page_id = rev_page
			WHERE page_namespace = " . NS_MAIN . "
			GROUP BY rev_page";
			/*
			HAVING COUNT(*) > 1
			";
			*/
    if (!$result = $xoopsDB->query($sql, $limit, 0)) {
        return $block;
    }
    while ($row = $xoopsDB->fetchArray($result)) {
	    $_item = array();
	    $_item["title"] = htmlspecialchars(mediawiki_encoding_mediawiki2xoops(str_replace("_", " ", $row["title"])));
	    $_item["link"] = XOOPS_URL."/modules/mediawiki/index.php?title=".wfUrlencode(str_replace(" ", "_", $row["title"]));
	    $_item["count"] = $row["value"]."/".$row["clicks"];
	    $block[] = $_item;
	    unset($_item);
    }
    return $block;
}

function mediawiki_top_edit($options)
{
    $form = _MB_MEDIAWIKI_ITEMS."&nbsp;&nbsp;<input type=\"text\" name=\"options[0]\" value=\"" . $options[0] . "\" /><br /><br />";
    return $form;
}

function mediawiki_hot_show( $options ) {
	global $xoopsDB;
	
	$block = array();
	$limit = $options[0];
	if ( $limit <= 0 || $limit > 5000 ) $limit = 10;

	$block["changes"] = mediawiki_recentchanges_show($options);
	$block["top"] = mediawiki_top_show($options);
    return $block;
}

function mediawiki_hot_edit($options)
{
    $form = _MB_MEDIAWIKI_ITEMS."&nbsp;&nbsp;<input type=\"text\" name=\"options[0]\" value=\"" . $options[0] . "\" /><br /><br />";
    return $form;
}
?>