<?php
//  ------------------------------------------------------------------------ //
// Author: phppp (D.J.)                                                      //
// URL: http://xoopsforge.com, http://xoops.org.cn                           //
// ------------------------------------------------------------------------- //
define( "MEDIAWIKI", true );
define( "MEDIAWIKI_INSTALL", true );
include "admin_header.php";

xoops_cp_header();

# MediaWiki web-based config/installation
# Copyright (C) 2004 Brion Vibber <brion@pobox.com>, 2006 Rob Church <robchur@gmail.com>
# http://www.mediawiki.org/
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
# http://www.gnu.org/copyleft/gpl.html

$IP = dirname( dirname( __FILE__ ) );
define( 'MW_INSTALL_PATH', $IP );
//$sep = PATH_SEPARATOR;
$sep = strtoupper(substr(PHP_OS,0,3)=='WIN')?';':':';
if( !@ini_set( "include_path", ".$sep$IP$sep$IP/includes$sep$IP/languages" ) ) {
	set_include_path( ".$sep$IP$sep$IP/includes$sep$IP/languages" );
}

# Define an entry point and include some files

// Run version checks before including other files
// so people don't see a scary parse error.
require_once( "install-utils.inc" );
install_version_checks();

require_once( "includes/Defines.php" );
require_once( "LocalSettings.php" );
//require_once( "includes/DefaultSettings.php" );
require_once( "includes/MagicWord.php" );
require_once( "includes/Namespace.php" );

require_once( "maintenance/updaters.inc" );

	chdir( ".." );
	$wgCommandLineMode = true;
	$wgUseDatabaseMessages = false;	/* FIXME: For database failure */
	require_once( "includes/Setup.php" );
	chdir( "admin" );

	$wgDatabase = mwDatabase::newFromParams( $wgDBserver, $wgDBuser, $wgDBpassword, "", 1 );

	if( $wgDBmysql5 ) {
		dbsource( XOOPS_ROOT_PATH."/modules/".MEDIAWIKI_DIRNAME."/maintenance/mysql5/tables.sql", $wgDatabase );
	} else {
		dbsource( XOOPS_ROOT_PATH."/modules/".MEDIAWIKI_DIRNAME."/maintenance/tables.sql", $wgDatabase );
	}
	dbsource( XOOPS_ROOT_PATH."/modules/".MEDIAWIKI_DIRNAME."/maintenance/interwiki.sql", $wgDatabase );

	$wgDatabase->insert( 'site_stats',
		array( 'ss_row_id'        => 1,
		       'ss_total_views'   => 0,
		       'ss_total_edits'   => 0,
		       'ss_good_articles' => 0 ) );


	$u = new User();
	$u->setId($GLOBALS["xoopsUser"]->getVar("uid"));
	$u->setName(mediawiki_username_xoops2mediawiki($GLOBALS["xoopsUser"]->getVar("uname")));
	$u->addToDatabase();
	$u->saveSettings();

	$u->addGroup( "sysop" );
	$u->addGroup( "bureaucrat" );

	$titleobj = Title::newFromText( wfMsgNoDB( "mainpage" ) );
	$article = new Article( $titleobj );
	$newid = $article->insertOn( $wgDatabase );
	$revision = new Revision( array(
		'page'      => $newid,
		'text'      => wfMsg( 'mainpagetext' ) . "\n\n" . wfMsg( 'mainpagedocfooter' ),
		'comment'   => '',
		'user'      => $u->getId(),
		'user_text' => $u->getName(),
		) );
	$revid = $revision->insertOn( $wgDatabase );
	$article->updateRevisionOn( $wgDatabase, $revision );

header("location: ".XOOPS_URL."/modules/system/admin.php?fct=modulesadmin");	
xoops_cp_footer();
?>