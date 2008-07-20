<?php 
/**
 * FCKeditor adapter for XOOPS
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		4.00
 * @version		$Id$
 * @package		xoopseditor
 */
//include_once dirname(__FILE__) . "/LocalSettings.php";
$mainfile = dirname(dirname(dirname(__FILE__)))."/mainfile.php";
include_once $mainfile;

define("MEDIAWIKI_DISABLE_UPLOAD", 0);

if(defined("MEDIAWIKI_DISABLE_UPLOAD") && constant("MEDIAWIKI_DISABLE_UPLOAD")){
	define("FCKUPLOAD_DISABLED", 1);
}
define("XOOPS_FCK_FOLDER", $xoopsModule->getVar("dirname"));
include XOOPS_ROOT_PATH."/class/xoopseditor/FCKeditor/editor/filemanager/upload/php/upload.php";
?>