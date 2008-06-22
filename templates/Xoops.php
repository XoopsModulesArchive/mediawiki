<?php
/**
 * XOOPS template
 *
 *
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */

if( !defined( 'MEDIAWIKI' ) )
	die();


/**
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */
class SkinXoops extends skin {

	function initPage(&$out) {
		parent::initPage( $out );
		$this->skinname  = 'xoops';
		$this->stylename = 'xoops';
	}

	function getStylesheet() {
		return 'common/xoops.css';
	}
	function getSkinName() {
		return "xoops";
	}

	function doBeforeContent() {
		global $wgUser, $wgOut, $wgTitle;

		//$s = "\n<div id='content'>\n<div id='topbar'>";
		$s = "\n<div>\n<div id='topbar'>";
		$s .= $this->logoText( "right" );

		$s .= $this->pageTitle();
		$s .= $this->pageSubtitle() . "\n";

		$s .= $this->topLinks() . "\n<br />";

		$notice = wfGetSiteNotice();
		if( $notice ) {
			$s .= "\n<div id='siteNotice'>$notice</div>\n";
		}
		$s .= $this->pageTitleLinks();

		$ol = $this->otherLanguages();
		if($ol) $s .= "<br />" . $ol;
		
		$cat = $this->getCategoryLinks();
		if($cat) $s .= "<br />" . $cat;

		$s .= "<br clear='all' /><hr />\n</div>\n";
		$s .= "\n<div id='article'>";

		return $s;
	}

	function styleLink() {
		global $wgTitle;

		return $this->makeKnownLinkObj( $wgTitle,
		  mediawiki_encoding_xoops2mediawiki(_MD_MEDIAWIKI_MEDIAWIKIMODE), 'style=m' );
	}

	function topLinks() {
		global $wgOut, $wgUser;
		$sep = " |\n";

		$s = $this->mainPageLink() . $sep
		  . $this->specialLink( "recentchanges" );

		if ( $wgOut->isArticle() ) {
			$s .=  $sep . $this->editThisPage()
			  . $sep . $this->historyLink();
		}
		$s .= $sep . $this->styleLink();
		/*
		if ( $wgUser->isAnon() ) {
			$s .= $sep . $this->specialLink( "userlogin" );
		} else {
			$s .= $sep . $this->specialLink( "userlogout" );
		}
		*/
		$s .= $this->specialPagesList();

		return $s;
	}

	function doAfterContent() {
		$s = "\n</div><br clear='all' />\n";

		$s .= "\n<div id='mw-footer'><hr />";

		$s .= $this->bottomLinks();
		$s .= "\n<br />" . $this->pageStats();
		$s .= "\n<br /><br />" . $this->mainPageLink()
		  . " | " . $this->aboutLink()
		  . " | " . $this->styleLink();
		$s .= "\n<br />" . $this->searchForm();

		$s .= "\n</div>\n</div>\n";

		return $s;
	}

	function outputPage( &$out ) {
		global $xoopsUser, $xoopsModule, $xoopsConfig, $xoopsOption, $xoopsLogger, $xoopsTpl;
		global $wgDebugComments;
		global $xoopsTpl;
		global $wgStylePath, $wgUseTrackbacks, $wgTitle;
		global $wgInputEncoding, $wgOutputEncoding;

		$module_header = $out->getHeadLinks();
		if( $out->isPrintable() ) {
			$media = '';
		} else {
			$media = "media='print'";
		}
		$printsheet = htmlspecialchars( "$wgStylePath/common/wikiprintable.css" );
		$module_header .= "<link rel='stylesheet' type='text/css' $media href='$printsheet' />\n";

		$module_header .= $this->getHeadScripts();
		$module_header .= $out->mScripts;
		$module_header .= $this->getUserStyles();

		if ($wgUseTrackbacks && $out->isArticleRelated())
			$module_header .= $wgTitle->trackbackRDF();
	
		$xoopsTpl->assign("xoops_pagetitle", $GLOBALS["xoopsModule"]->getVar("name")." - ".XoopsLocal::convert_encoding(htmlspecialchars( $out->getPageTitle() ), $wgOutputEncoding, $wgInputEncoding) );
		$xoopsTpl->assign("xoops_module_header", $module_header);

		wfProfileIn( 'Skin::outputPage' );
		$this->initPage( $out );

		/*
		$out->out( $out->headElement() );

		$out->out( "\n<body" );
		$ops = $this->getBodyOptions();
		foreach ( $ops as $name => $val ) {
			$out->out( " $name='$val'" );
		}
		$out->out( ">\n" );
		*/
		if ( $wgDebugComments ) {
			$out->out( "<!-- Wiki debugging output:\n" .
			  $out->mDebugtext . "-->\n" );
		}

		$out->out( $this->beforeContent() );

		$out->out( $out->mBodytext . "\n" );

		$out->out( $this->afterContent() );

		wfProfileClose();
		$out->out( $out->reportTime() );

		//$out->out( "\n</body></html>" );
	}
}
?>
