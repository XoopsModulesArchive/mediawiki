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
 
/**
 * @package MediaWiki
 */

/**
 * This is not a valid entry point, perform no further processing unless MEDIAWIKI is defined
 */
if( defined( 'MEDIAWIKI' ) ) {

/**
 * @todo document
 * @package MediaWiki
 */
class XoopsOutputPage extends OutputPage{

	var $inXoops = true;
	
	/**
	 * Constructor
	 * Initialise private variables
	 */
	function XoopsOutputPage() {
		//global $wgInputEncoding, $wgOutputEncoding, $wgEditEncoding;

		$this->setTemplate();
		parent::__construct();
	}

	/**
	 * To skip encoding conversion which is to be done in output
	 */
	function out( $ins ) {
		print $ins;
	}

	/**
	 * Get theme style
	 *  
	 * The front-page style could be:
	 * 1. Xoops style: mediawiki wrapped inside XOOPS as a regular module
	 * 2. mediawiki style: same style as a standalone mediawiki
	 * 3. Selectable: end users can switch the style on-fly
	 *
	 */
	function getStyle()
	{
		static $style;
		if(isset($style)){
			return intval($style);
		}	
		$style = mediawiki_getStyle();
		if(isset($_GET["style"])){
			setcookie("mw_style", $_GET["style"], time()+3600, "/", "", 0);
			$this->enableClientCache( false );
		}
		return intval($style);
	}
	
	function setTemplate(){
		include_once dirname(__FILE__)."/functions.php";		
		$this->inXoops = $this->getStyle();
		if($this->inXoops){
			global $xoopsUser, $xoopsModule, $xoopsConfig, $xoopsOption, $xoopsLogger, $xoopsTpl;
			global $xoopsUserIsAdmin;
			global $xoopsCachedTemplate, $xoopsCachedTemplateId;
			
			global $wgUser, $wgRequest, $wgCommandLineMode;
			extract( $wgRequest->getValues( 'action', 'oldid', 'diff', 'redirect', 'printable' ) );
	
			if(
			!$wgCommandLineMode
			and	($wgUser && !$wgUser->getNewtalk())
			and (empty( $action ) || $action == 'view')
			and (empty($oldid))
			and (empty($diff))
			and (empty($redirect))
			and (empty($printable))
			){
			}else{
				$xoopsConfig['module_cache'][$xoopsModule->getVar('mid')] = 0;
			}
			// This should be taken care by xoops::theme			
			$xoopsOption['template_main'] = "mediawiki_content.html";
			include XOOPS_ROOT_PATH."/header.php";
		}
	}

	/**
	 * Finally, all the text has been munged and accumulated into
	 * the object, let's actually output it:
	 */
	public function output() {
		//global $wgInputEncoding, $wgOutputEncoding;
		global $wgTitle;
		# add XOOPS user profile link
		if ($wgTitle->getNamespace() == NS_USER && $user_id = User::idFromName($wgTitle->getDBkey())) {
			$user_name = preg_replace("/^".preg_quote(MEDIAWIKI_USERPREFIX)."/", "", User::whoIsReal($user_id));
			$this->mBodytext = '<a href="'.XOOPS_URL.'/userinfo.php?uid='.$user_id.'">'.$user_name.'</a><br /><br />' . $this->mBodytext;
		}
		
		($this->inXoops)?$this->outputxoops():$this->outputmediawiki();
	}
	
	function outputxoops() {
		global $xoopsUser, $xoopsModule, $xoopsConfig, $xoopsOption, $xoopsLogger, $xoopsTpl;
		global $xoopsUserIsAdmin;
		global $xoopsCachedTemplate, $xoopsCachedTemplateId;
		
		global $wgUser, $wgLang, $wgDebugComments, $wgCookieExpiration;
		global $wgInputEncoding, $wgOutputEncoding, $wgEditEncoding, $wgContLanguageCode;
		global $wgDebugRedirects, $wgMimeType, $wgProfiler;
		global $IP;

		if( $this->mDoNothing ){
			return;
		}
		
		// moved from XoopsOutputPage() because it was erased by includes/Language.php
		/** We speak UTF-8 all the time now, unless some oddities happen */
		/* Niluge KiWi: in fact many websites use local charset */
		//$wgInputEncoding	= 'UTF-8';
		$wgOutputEncoding	= empty($GLOBALS["xlanguage"]['charset_base'])?_CHARSET:$GLOBALS["xlanguage"]['charset_base'];
		//$wgEditEncoding	= '';

		$fname = 'OutputPage::output';
		wfProfileIn( $fname );
		$sn = "Xoops";
		require_once( $IP.'/templates/'.$sn.'.php' );

		# Check if we got if not failback to default skin
		$className = 'Skin'.$sn;
		if( !class_exists( $className ) ) {
			return null;
		}
		$sk =& new $className;

		if ( '' != $this->mRedirect ) {
			if( substr( $this->mRedirect, 0, 4 ) != 'http' ) {
				# Standards require redirect URLs to be absolute
				global $wgServer;
				$this->mRedirect = $wgServer . $this->mRedirect;
			}
			if( $this->mRedirectCode == '301') {
				if( !$wgDebugRedirects ) {
					header("HTTP/1.1 {$this->mRedirectCode} Moved Permanently");
				}
				$this->mLastModified = wfTimestamp( TS_RFC2822 );
			}

			$this->sendCacheControl_xoops();

			if( $wgDebugRedirects ) {
				$url = htmlspecialchars( $this->mRedirect );
				print "<html>\n<head>\n<title>Redirect</title>\n</head>\n<body>\n";
				print "<p>Location: <a href=\"$url\">$url</a></p>\n";
				print "</body>\n</html>\n";
			} else {
				header( 'Location: '.$this->mRedirect );
			}
			if ( isset( $wgProfiler ) ) { wfDebug( $wgProfiler->getOutput() ); }
			wfProfileOut( $fname );
			return;
		}


		# Buffer output; final headers may depend on later processing
		ob_start();
		if ($this->mArticleBodyOnly) {
			$this->out($this->mBodytext);
		} else {
			wfProfileIn( 'Output-skin' );
			$sk->outputPage( $this );
			wfProfileOut( 'Output-skin' );
		}
		$this->sendCacheControl_xoops();
		$output = ob_get_contents();
		ob_end_clean();
		
		//echo XoopsLocal::convert_encoding($output, $wgOutputEncoding, $wgInputEncoding);
		
		$xoopsTpl->assign_by_ref("mediawiki_content", XoopsLocal::convert_encoding($output, $wgOutputEncoding, $wgInputEncoding));
		include XOOPS_ROOT_PATH."/footer.php";
		
		wfProfileOut( $fname );
		return true;
	}

	/**
	 * Finally, all the text has been munged and accumulated into
	 * the object, let's actually output it:
	 */
	function outputmediawiki() {
		global $wgUser, $wgLang, $wgDebugComments, $wgCookieExpiration;
		global $wgInputEncoding, $wgOutputEncoding, $wgContLanguageCode;
		global $wgDebugRedirects, $wgMimeType, $wgProfiler;

		if( $this->mDoNothing ){
			return;
		}
		$fname = 'OutputPage::output';
		wfProfileIn( $fname );
		$sk = $wgUser->getSkin();

		if ( '' != $this->mRedirect ) {
			if( substr( $this->mRedirect, 0, 4 ) != 'http' ) {
				# Standards require redirect URLs to be absolute
				global $wgServer;
				$this->mRedirect = $wgServer . $this->mRedirect;
			}
			if( $this->mRedirectCode == '301') {
				if( !$wgDebugRedirects ) {
					header("HTTP/1.1 {$this->mRedirectCode} Moved Permanently");
				}
				$this->mLastModified = wfTimestamp( TS_RFC2822 );
			}

			$this->sendCacheControl();

			if( $wgDebugRedirects ) {
				$url = htmlspecialchars( $this->mRedirect );
				print "<html>\n<head>\n<title>Redirect</title>\n</head>\n<body>\n";
				print "<p>Location: <a href=\"$url\">$url</a></p>\n";
				print "</body>\n</html>\n";
			} else {
				header( 'Location: '.$this->mRedirect );
			}
			if ( isset( $wgProfiler ) ) { wfDebug( $wgProfiler->getOutput() ); }
			wfProfileOut( $fname );
			return;
		}


		# Buffer output; final headers may depend on later processing

		# Disable temporary placeholders, so that the skin produces HTML
		$sk->postParseLinkColour( false );

		header( "Content-type: $wgMimeType; charset={$wgOutputEncoding}" );
		header( 'Content-language: '.$wgContLanguageCode );

		ob_start();
		if ($this->mArticleBodyOnly) {
			$this->out($this->mBodytext);
		} else {
			wfProfileIn( 'Output-skin' );
			$sk->outputPage( $this );
			wfProfileOut( 'Output-skin' );
		}

		$this->sendCacheControl();
		$output = ob_get_contents();
		ob_end_clean();
		
		wfProfileOut( $fname );
		
		echo XoopsLocal::convert_encoding($output, $wgOutputEncoding, $wgInputEncoding);
		return;
	}

	public static function setEncodings() {
		global $wgOutputEncoding;

		$_wgOutputEncoding = $wgOutputEncoding;
		parent::setEncodings();
		$wgOutputEncoding = $_wgOutputEncoding;
	}

	function sendCacheControl_xoops() {
		return;
	}

	/**
	 * return from error messages or notes
	 * @param $auto automatically redirect the user after 10 seconds
	 * @param $returnto page title to return to. Default is Main Page.
	 */
	function returnToMain( $auto = true, $returnto = NULL ) {
		global $wgRequest;

		if ( $returnto == NULL ) {
			$returnto = $wgRequest->getVal( 'returnto' );
		}
		parent::returnToMain( $auto, $returnto );
	}
}
}

?>
