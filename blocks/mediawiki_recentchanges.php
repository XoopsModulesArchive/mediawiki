<?php
/**
 *
 * @package MediaWiki
 * @subpackage SpecialPage
 */

require_once XOOPS_ROOT_PATH."/modules/mediawiki/includes/GlobalFunctions.php";
require_once XOOPS_ROOT_PATH."/modules/mediawiki/include/functions.php";
 
function mediawiki_recentchanges_show( $options ) {
	global $xoopsDB;
	
	$block = array();
	$limit = $options[0];
	if ( $limit <= 0 || $limit > 5000 ) $limit = 10;

	$sql = "SELECT DISTINCT rc_cur_id, *  FROM " . $xoopDB->prefix("mediawiki_recentchanges").
		" WHERE rc_type =0 OR rc_type=1".
		" ORDER BY rc_timestamp DESC";
    if (!$result = $xoopsDB->query($query, $limit, 0)) {
        return $block;
    }
    $rows = array();
    $author = array();
    while ($row = $xoopsDB->fetchArray($result)) {
        $rows[] = $row;
        $author[$row["uid"]] = 1;
    }
    if (count($rows) < 1) return $block;
	$author_name = mediawiki_getUnameFromId(array_keys($author), false, true);
    foreach ($rows as $row) {
	    $_item = array();
	    $title = str_replace(" ", "_", $row["rc_title"]);
	    $_item["new"] = ($row["rc_type"] == 1);
	    $_item["title"] = htmlspecialchars(mediawiki_encoding_mediawiki2xoops($title));
	    $_item["link"] = XOOPS_URL."/modules/mediawiki/index.php?title=".wfUrlencode($title);
	    $_item["time"] = formatTimestamp(wfTimestamp( TS_UNIX, $row["rc_timestamp"] ));
	    $_item["author"] = empty($author_name[$row["rc_user"]])?$row["rc_ip"]:$author_name[$row["rc_user"]];
	    $block[] = $_item;
	    unset($_item);
    }
    return $block;
}

function mediawiki_recentchanges_edit($options)
{
    $form = MEDIAWIKI_MB_ITEMS."&nbsp;&nbsp;<input type=\"text\" name=\"options[0]\" value=\"" . $options[0] . "\" /><br /><br />";
    return $form;
}
?>