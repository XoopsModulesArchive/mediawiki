<?php
/**
 * Mediawiki module for XOOPS
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		1.58
 * @version		$Id$
 * @package		module
 */

function xoops_module_install_mediawiki(&$module) 
{
	header("location: ".XOOPS_URL."/modules/".$module->getVar("dirname")."/admin/install.mediawiki.php?mid=".$module->getVar("mid"));
	return true;
}

function xoops_module_update_mediawiki(&$module, $oldversion = null) 
{
	header("location: ".XOOPS_URL."/modules/".$module->getVar("dirname")."/admin/update.mediawiki.php?mid=".$module->getVar("mid"));
	return true;
}
?>