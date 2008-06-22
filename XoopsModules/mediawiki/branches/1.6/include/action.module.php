<?php
//  ------------------------------------------------------------------------ //
// Author: phppp (D.J.)                                                      //
// URL: http://xoopsforge.com, http://xoops.org.cn                           //
// ------------------------------------------------------------------------- //

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