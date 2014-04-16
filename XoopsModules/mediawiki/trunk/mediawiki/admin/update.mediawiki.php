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
# 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
# http://www.gnu.org/copyleft/gpl.html

# Relative includes seem to break if a parent directory is not readable;
# this is common for public_html subdirs under user home directories.
#
# As a dirty hack, we'll try to set up the include path first.
#
$IP = dirname( dirname( __FILE__ ) );
define( 'MW_INSTALL_PATH', $IP );
//$sep = PATH_SEPARATOR;
$sep = strtoupper(substr(PHP_OS,0,3)=='WIN')?';':':';
if( !@ini_set( "include_path", ".$sep$IP$sep$IP/includes$sep$IP/languages" ) ) {
	set_include_path( ".$sep$IP$sep$IP/includes$sep$IP/languages" );
}


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
header("location: ".XOOPS_URL."/modules/system/admin.php?fct=modulesadmin");	

	chdir( ".." );
	$wgCommandLineMode = true;
	$wgUseDatabaseMessages = false;	/* FIXME: For database failure */
	require_once( "includes/Setup.php" );
	chdir( "admin" );

	$wgDatabase = mwDatabase::newFromParams( $wgDBserver, $wgDBuser, $wgDBpassword, "", 1 );

	chdir( ".." );
	flush();
	do_all_updates();
	chdir( "admin" );

xoops_cp_footer();
?>