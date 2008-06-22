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
if( ! defined( 'XOOPS_ROOT_PATH' ) ) exit ;

$modversion['name'] = _MI_MEDIAWIKI_NAME;
$modversion['description'] = _MI_MEDIAWIKI_DESC;
$modversion['version'] = "1.67";
$modversion['credits'] = "MediaWiki DEV (http://wikimedia.org/); A.D.Horse (test, http://www.cctv3g.com)";
$modversion['author'] = "D.J. (phppp, http://xoops.org.cn, http://xoopsforge.com)";
$modversion['license'] = "GPL see LICENSE";
$modversion['image'] = "images/mediawiki.png";
$modversion['dirname'] = "mediawiki";

// status
$modversion['codename'] = "";

$modversion['onInstall'] = 'include/action.module.php';
$modversion['onUpdate'] = 'include/action.module.php';

// Templates
$modversion['templates'][0]['file'] = 'mediawiki_content.html';
$modversion['templates'][0]['description'] = '';

// Sql file (must contain sql generated by phpMyAdmin or phpPgAdmin)
//$modversion['sqlfile']['mysql'] = "";

$modversion['tables'] = array(
	"mediawiki_user",
	"mediawiki_user_groups",
	"mediawiki_user_newtalk",
	"mediawiki_page",
	"mediawiki_revision",
	"mediawiki_text",
	"mediawiki_archive",
	"mediawiki_pagelinks",
	"mediawiki_imagelinks",
	"mediawiki_categorylinks",
	"mediawiki_site_stats",
	"mediawiki_hitcounter",
	"mediawiki_ipblocks",
	"mediawiki_image",
	"mediawiki_oldimage",
	"mediawiki_recentchanges",
	"mediawiki_watchlist",
	"mediawiki_math",
	"mediawiki_searchindex",
	"mediawiki_interwiki",
	"mediawiki_querycache",
	"mediawiki_objectcache",
	"mediawiki_validate",
	"mediawiki_logging",
	"mediawiki_trackbacks",
	"mediawiki_transcache",
	"mediawiki_externallinks",
	"mediawiki_job",
	"mediawiki_templatelinks"
	);

// Search
$modversion['hasSearch'] = 1;
$modversion['search']['file'] = "include/search.php";
$modversion['search']['func'] = "mediawiki_search";

// Blocks
$i=0;
$i++;
$modversion["blocks"] = array();
$modversion["blocks"][$i]["file"] = "block.php";
$modversion["blocks"][$i]["name"] = _MI_MEDIAWIKI_BLOCK_RECENTCHANGES;
$modversion["blocks"][$i]["description"] = "";
$modversion["blocks"][$i]["show_func"] = "mediawiki_recentchanges_show";
$modversion["blocks"][$i]["options"] = "10"; // MaxItems
$modversion["blocks"][$i]["edit_func"] = "mediawiki_recentchanges_edit";
$modversion["blocks"][$i]["template"] = "mediawiki_block_recentchanges.html";

//Admin things
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/index.php";
$modversion['adminmenu'] = "admin/menu.php";

$modversion['hasMain'] = 1;

$modversion['hasconfig'] = 1;

$modversion['config'][] = array(
	'name'			=> 'style' ,
	'title'			=> '_MI_MEDIAWIKI_STYLE' ,
	'description'	=> '_MI_MEDIAWIKI_STYLE_DESC' ,
	'formtype'		=> 'select' ,
	'valuetype'		=> 'int' ,
	'default'		=> 0,
	'options'		=> array("XOOPS"=>1, "mediawiki"=>2, "Selectable"=>0) 
);
?>