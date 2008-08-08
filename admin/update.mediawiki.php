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
require_once( "install-utils.inc" ); //FIXME: strange path?
install_version_checks();

require_once( "$IP/includes/Defines.php" );
require_once( "$IP/LocalSettings.php" );
//require_once( "$IP/includes/DefaultSettings.php" );
require_once( "$IP/includes/AutoLoader.php" );
require_once( "$IP/includes/MagicWord.php" );
require_once( "$IP/includes/Namespace.php" );
require_once( "$IP/includes/ProfilerStub.php" );
require_once( "$IP/includes/GlobalFunctions.php" );

require_once( "$IP/maintenance/updaters.inc" );
$oldversion = $_GET['oldversion'];
header("location: ".XOOPS_URL."/modules/system/admin.php?fct=modulesadmin");
if ($oldversion == 158 || $oldversion == 167 || $oldversion == 171) {
	echo <<<EOT
<p><b>WARNING: If you see this, you have desactivated the header:location redirection, DO NOT RELOAD this page, or your mediawiki tables will be completely messed up, and everything related to users will be permanently broken</b></p>
<p> If you wan to update again the module, please go to <a href='../../system/admin.php?fct=modulesadmin'>the modules admin</a> and restart update</p>
EOT;

}


	chdir( ".." );
	$wgCommandLineMode = true;
	$wgUseDatabaseMessages = false;	/* FIXME: For database failure */
	require_once( "includes/Setup.php" );
	chdir( "admin" );

	$wgDatabase = mwDatabase::newFromParams( $wgDBserver, $wgDBuser, $wgDBpassword, "", 1 );

	chdir( ".." );
	flush();
	do_all_updates();

	/**
	 * Tables not removed but useless:
	 * (and still here after uninstall of mediawiki module, so we have to remove them now)
	 */
	
	$tables_to_remove = array();
	switch ($oldversion) {
		case 158:
		case 167: 
			$tables_to_remove[] = "validate";
		case 171:
			$tables_to_remove[] = "ipblocks_old";
			break;
		default:
			break;
	}
	foreach ($tables_to_remove as $table_name) {
		if ( $wgDatabase->tableExists( $table_name ) ) {
			$table_fullname = $wgDatabase->tableName( $table_name );
			echo "Deleting $table_name...";
			$wgDatabase->query( "DROP TABLE $table_fullname" );		
		}
	}
	unset($tables_to_remove);

	/**
	 * And now we add the xoops users to the MediaWiki users database, and update every MediaWiki tables with new mId and userName
	 */
	

	/******* But first, some functions *******/

	/**
	 * Update the MediaWiki $table: 
	 * - create the MediaWiki user if it does not exists
	 * - update the user id (which was the Xoops one) with the new MediaWiki User
	 * - update the username if $username_field is set 
	 *
	 * @param string $table          : the table name to be updated
	 * @param array $id_fields       : an array of field which permit to identify a unique record
	 * @param string $uid_field      : the uid field name to be updated
	 * @param string $username_field : the username field name to be updated
	 * @param boolean $create_tmp_table : do we have to create the tmp table and copy all the records?(useful if we have several user_id fields to update)
	 * 
	 * @return boolean true if correctly updated
	 */
	/* This array cntains MediaWiki uid and username, and as key the Xoops uid */
	$xoopsUid2mediawikiUser = array();
	$member_handler =& xoops_getHandler("member");
	function mediawikiUpdateUsersFromXoops($table, $id_fields, $uid_field, $username_field = false, $create_tmp_table = true) {
		echo "<br /><br />\nCall:{$table}, ".print_r($id_fields).", {$uid_field}, {$username_field}<br />";
		global $wgDatabase, $member_handler, $xoopsUid2mediawikiUser;
		

		/* the tables names we'll work on */
		$old_table = $wgDatabase->tableName($table);
		$new_table = $wgDatabase->tableName($table."_new");
		$mediawiki_user_table = $wgDatabase->tableName("user");

		if ($create_tmp_table) {
			/* We create a new tmp table */
			$sql = "CREATE TABLE {$new_table} LIKE {$old_table}";
			$result = $wgDatabase->query( $sql , '', true  );
			if(!$result) {
				/* error during criticial operation, need to stop */
				die("ERROR during synchronizing users: create tmp table");
				return false;
			}
			/* We copy the records */
			$sql = "INSERT INTO {$new_table} SELECT * FROM {$old_table} ";
			$result = $wgDatabase->query( $sql, '', true );
			if (!$result) {
				/* error during criticial operation, need to stop */
				die ("ERROR during users update: copy records");
				return false;
			}
		}

		/* We get every record id and user id */		
		$id_fields_names = implode(", ", $id_fields);

		$sql = 	"SELECT {$id_fields_names}, {$uid_field} FROM {$old_table}";

		$masterloopResult = $wgDatabase->query( $sql, '', true );

		echo "total number of records to update:".$wgDatabase->numRows($masterloopResult);
		while ( $values = $wgDatabase->fetchRow( $masterloopResult ) ) {echo "<br />\n";
			$user_id = $values[$uid_field]; // it's the xoops uid

			/* get the xoops user */
	 		$xoopsuser =& $member_handler->getUser($user_id);
			if(!$xoopsuser) {echo "user xoops does not exist";
				//TODO: check if user exists(it should exists, but maybe the admin deleted the user...)
				//TODO: do something like set the user anonymous, but keeping the nickname..
				//TODO: be careful, if it is the first user, we can do others things
				continue;
			}

			$username = mediawiki_username_xoops2mediawiki($xoopsuser->getVar("uname"));echo "|username:{$username}";
			/* do the mediawiki user already exist? and is he not anonymous? */
			if (!isset($xoopsUid2mediawikiUser[$user_id]) || ($user_id == 0)) {echo "|MW user does not exit";
				/* we have to create the mediawiki user */
				/* create the mediawiki user */
				$wgUser = User::newFromName( $username );

				/**
				 * at this point, the user should not be in the mediawiki database,
				 * so $wgUser->isAnon() sould be true,
				 * but in some case, the user already exists. It's a known issue: 
				 *  two differents Xoops users can have the same MediaWiki nickname...
				 *  (MediaWiki replaces "_" by " ", so "Niluge_KiWi" and "Niluge KiWi" are the same user for MediaWiki)
				 */
				if (!$wgUser->isAnon()) {echo "|MWuser already exists: username conflict, not good at all...";
					//TODO: do something about that, even if it can't be fully corrected, try to avoid errors...
					continue;
				}

				// code from XoopsAuth, and LoginForm::initUser()
				//TODO: update this code from XoopsAuth when groups are ok, or better: move to an external function
				$wgUser->addToDatabase();

				if($xoopsuser->isAdmin()){
					$wgUser->addGroup("bureaucrat");
					$wgUser->addGroup("sysop");
				}
				//TODO: check if its the best way to do this
				$effectiveGroups = array_merge( array( '*', 'user' ), $wgUser->mGroups );
				$wgUser->mRights = $wgUser->getGroupPermissions( $effectiveGroups );
		
				$wgUser->setEmail($xoopsuser->getVar("email"));
				$wgUser->setRealName(mediawiki_encoding_xoops2mediawiki($xoopsuser->getVar("name")));

				/* get the xoops encrypted password */
				UpdateMediawikiEncryptedPasswordFromXoopsUser(&$wgUser, $user_id); 
				echo "|MWusername:{$wgUser->getName()}";
				$wgUser->saveSettings();

				/* We update our local progression array */
				$xoopsUid2mediawikiUser[$user_id] = array(
					"uid" => $wgUser->getID(),
					"uname" => $wgUser->getName()
				);

				//TODO: do better for stats, and test it
				# Update user count
				$ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
				$ssUpdate->doUpdate();
			}

			/* The MediaWiki user is now on the database */ 
	
			/* We update the values */
			/* do we have to update the username? */
			$update_username = "";
			if ($username_field && $user_id != 0) {
				/* we update only when asked, and when the user is not anonymous(because in such case this field is the IP) */
				$update_username = ", {$username_field}='{$xoopsUid2mediawikiUser[$user_id]['uname']}'";
			}
			/* and multi fields unique identification */
			$where = array();
			foreach ($id_fields as $id) {
				$where[] = "{$id}='{$values[$id]}'";
			}
			$where = implode(" AND ", $where);

			$sql = "UPDATE {$new_table} SET {$uid_field}={$xoopsUid2mediawikiUser[$user_id]['uid']} {$update_username} WHERE {$where}";
			$result = $wgDatabase->query( $sql, '', true );
			if (!$result) {
				/* error during criticial update, need to stop */
				die ("ERROR during users update: update record");
				return false;
			}
		}
		/* if we are here, everything went fine! */
		return true;
	}


	/* And now we use this ugly update function */
	/**
	 * Update the MediaWiki database: 
	 * convert Xoops users that used mediawiki to new mediawiki users
	 *
	 * @param array $tablesToUpdate : an array of parameters of the ugly precedent function
	 * 
	 * @return boolean true if $wgUser correctly updated
	 */
	function mediawikiProcessUpdateUsers($tablesToUpdate) {
		global $wgDatabase;
		$tables = array(); /* table already processed */


		/**
		 * We have to remove the only existing MediaWiki user, 
		 * which is the Xoops user who installed MediaWiki module. 
		 * What we do is so considere it is the first Xoops user, which can be false
		 */
		$sql = "TRUNCATE TABLE {$wgDatabase->tableName("user")}";
		$result = $wgDatabase->query( $sql, '', true );
		if (!$result) {
			/* error during criticial operation, need to stop */
			die ("ERROR during users update: truncate User table");
		}
		//TODO: maybe it was possible to user MediaWiki groups features with old mediawiki for xoops, in this case we have to update this table and not to drop it.
		$sql = "TRUNCATE TABLE {$wgDatabase->tableName("user_groups")}";
		$result = $wgDatabase->query( $sql, '', true );
		if (!$result) {
			/* error during criticial operation, need to stop */
			die ("ERROR during users update: truncate User_groups table");
		}

		
		/* Main loop */
		foreach ($tablesToUpdate as $params) {
			$create_tmp_table = false;
			if (!isset($tables[$params["table"]])) {
				/* we have to create the tmp table */
				$create_tmp_table = true;
			}
			/* we call the ugly function */
			if (!is_array($params["id"])) {
				$params["id"] = array($params["id"]);
			}
			$result = mediawikiUpdateUsersFromXoops($params["table"], $params["id"], $params["uid"], $params["uname"], $create_tmp_table);
			
			if (!$result) {
				/* we had an error, we have to clean up everything and stop */
				foreach ($tables as $table_name => $value) {
					$new_table = $wgDatabase->tableName($table_name."_new");
					$result = $wgDatabase->query( "DROP TABLE {$new_table}", '', true );
					if (!$result) {
						/* error during criticial operation, need to stop */
						die ("ERROR during cleaning tmp tables({$table_name}), this cleaning was made because an error occured during processing table {$params["table"]}");
					}
				}
				$sql = "DROP TABLE IF EXISTS {$wgDatabase->tableName($params["table"])}";
				$result = $wgDatabase->query( $sql, '', true );
				//XXX: comment die on the function called, and repace by return false.
				die("ERROR during processing table {$params["table"]}");
			}

			$tables[$params["table"]] =  true;
		}

		/* and now the loop which move the tmp tables to the original ones(and remove them) */
		foreach ($tables as $table_name => $value) {
			$old_table = $wgDatabase->tableName($table_name);
			$new_table = $wgDatabase->tableName($table_name."_new");
			$result = $wgDatabase->query( "DROP TABLE {$old_table}", '', true );
			if (!$result) {
				/* error during criticial operation, need to stop */
				//TODO: quit after cleaning tmp table, or not? if we have an issue now, parts of the database will be updated, and others not... we cant repair that..
				die ("ERROR during users update: drop table {$table_name}");
			}
			$result = $wgDatabase->query( "RENAME TABLE {$new_table} TO {$old_table} ", '', true  );
			if (!$result) {
				/* error during criticial operation, need to stop */
				//TODO: quit after cleaning tmp table, or not? if we have an issue now, parts of the database will be updated, and others not... we cant repair that..
				die ("ERROR during users update: rename table {$table_name}");
			}
		}
	}

	
	/******* the functions are now defined *******/

	/* We create the main array which contains all the tables to be updated, and all the fields required */
	/**
	 * We start with the revision table because we try to create users
	 * in the order of first modification on the wiki (even if it's useless, its better) 
	 */
	$tablesToUpdate = array();

	$tablesToUpdate[] = array(
		"table" => "revision",
		"id"    => "rev_id",
		"uid"   => "rev_user",
		"uname" => "rev_user_text"
	);
	$tablesToUpdate[] = array(
		"table" => "archive",
		"id"    => "ar_rev_id",
		"uid"   => "ar_user",
		"uname" => "ar_user_text"
	);
	$tablesToUpdate[] = array(
		"table" => "filearchive",
		"id"    => "fa_id",
		"uid"   => "fa_user",
		"uname" => "fa_user_text"
	);
	$tablesToUpdate[] = array(
		"table" => "filearchive",
		"id"    => "fa_id",
		"uid"   => "fa_deleted_user",
		"uname" => false
	);
	$tablesToUpdate[] = array(
		"table" => "image",
		"id"    => "img_name",
		"uid"   => "img_user",
		"uname" => "img_user_text"
	);
	$tablesToUpdate[] = array(
		"table" => "ipblocks",
		"id"    => "ipb_id",
		"uid"   => "ipb_user",
		"uname" => false
	);
	$tablesToUpdate[] = array(
		"table" => "ipblocks",
		"id"    => "ipb_id",
		"uid"   => "ipb_by",
		"uname" => false
	);
	$tablesToUpdate[] = array(
		"table" => "logging",
		"id"    => "log_id",
		"uid"   => "log_user",
		"uname" => false
	);
	$tablesToUpdate[] = array(
		"table" => "oldimage",
		"id"    => "oi_name",
		"uid"   => "oi_user",
		"uname" => "oi_user_text"
	);
	$tablesToUpdate[] = array(
		"table" => "page_restrictions",
		"id"    => "pr_id",
		"uid"   => "pr_user",
		"uname" => false
	);
	$tablesToUpdate[] = array(
		"table" => "recentchanges",
		"id"    => "rc_id",
		"uid"   => "rc_user",
		"uname" => "rc_user_text"
	);
	$tablesToUpdate[] = array(
		"table" => "user_newtalk",
		"id"    => "user_id",
		"uid"   => "user_id",
		"uname" => false
	);
	/**
	 * We have to do something special for those ones: those tables have no fied which is unique
	 * so we provide an array of fields which can be used to identify a record
	 */
	$tablesToUpdate[] = array(
		"table" => "watchlist",
		"id"    => array("wl_user", "wl_namespace", "wl_title"),
		"uid"   => "wl_user",
		"uname" => false
	);
	$tablesToUpdate[] = array(
		"table" => "protected_titles",
		"id"    => array("pt_namespace", "pt_title"),
		"uid"   => "pt_user",
		"uname" => false
	);


	/* we finally call the main update function */
	//TODO: test all this with 1.5.x version too
	if ($oldversion == 158 || $oldversion == 167 || $oldversion == 171) {
		/* we update only if we come from old mediawiki for xoops which dont copy users to MediaWiki database */
		mediawikiProcessUpdateUsers($tablesToUpdate);
	}
	//echo "<br /><br /><br />\n";
	//print_r($xoopsUid2mediawikiUser);

	chdir( "admin" );

echo "<br /><br /><br /><a href='../../system/admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
xoops_cp_footer();
?>
