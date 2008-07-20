<?php

/*
This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*/


if( !defined( 'MEDIAWIKI' ) ) {
  die();
}

$wgExtensionCredits['other'][] = array(

	"name" => "fckeditor extension",
	"author" => "Mafs",
	"version" => "fck/mw-extension (0.7.2) 2006",
	"url" => "http://meta.wikimedia.org/wiki/FCKeditor",
	"description" => "integrating the fckeditor"

 );


# REGISTER HOOKS
$wgHooks['ParserBeforeStrip'][] 		= 'wfFCKeditorBypassParserCut';
$wgHooks['ParserAfterTidy'][] 			= 'wfFCKeditorBypassParserPaste';
$wgHooks['ArticleAfterFetchContent'][] 		= 'wfFCKeditorCheck';
$wgHooks['EditPage::showEditForm:initial'][] 	= 'wfFCKeditorAddFCKScript';


//************************************************************************
// Not used yet, needs some testing ...
/*
$wgHooks['ArticleSave'][] 			= 'wfFCKeditorImageLinks';

function wfFCKeditorImageLinks($q, $user, $text, $summary, $f1, $f2, $f3, $flags) {
	// In case the html text was copied from an other wiki, the src attribute
	// of the image links is no longer valid. 
	// Therefore, the img tags have to be rewritten.
	
	// For nomal images: Extract the image name and create a new url.
	// For tex images:   Extract the tex text (alt="...") and send it to the parser.
	
	global $wgFCKEditorToken;

	if (preg_match("/$wgFCKEditorToken/i", $text, $a )) {
	
	

	global $wgOut, $wgUser;

	$wgUser->setOption("math", 0); // render png

	$list_md5 = array();
	$list_tex = array();
	$list_names = array();
	$list_img = array();
	
	$att_list = array("href", "longdesc", "alt", "src");

	// tex   *************************************************************
	while (preg_match("/<img.*?class=[\'\"]tex[\'\"].*?>/i", $text, $a)) {

		$img_tag = $a[0];
		$img_md5 = "tex_".md5($img_tag);
		$text = str_replace($img_tag, $img_md5, $text );
		$list_md5[] = $img_md5;
		preg_match("/alt=[\'\"](.*?)[\'\"]/", $img_tag, $a);
		$list_tex["$img_md5"] = $a[1];
	}

	foreach($list_md5 as $md5) {
		$tex = $list_tex["$md5"];
		$math = $wgOut->parse("<math>$tex</math>");
		preg_match("/<img.*?>/i", $math, $a);
		$math = $a[0];
		$text = str_replace( $md5, $math, $text );
	}

	//  imgage   **********************************************************
	//           add class="image" to a-tag and replace attributes.

	$list_md5 = array();
	
	//die("replace");
	
	while (preg_match("/<a.*?>.*?<img.*?>.*?<\/a.*?>/i", $text, $a) ) {

		$img_tag = $a[0];
		$img_md5 = "img_".md5($img_tag);
		$text = str_replace($img_tag, $img_md5, $text );
		$list_md5[] = $img_md5;
		
		if (! preg_match("/<a.*?class=[\'\"]image[\'\"].*?>/", $img_tag, $a)) 
		      $img_tag = preg_replace("/<a (.*?)/", "<a class= \"image\" \\1", $img_tag);
		      
		preg_match("/src=[\'\"].*?\/([^\/]+?)[\'\"]/", $img_tag, $a);
		$image_name = $a[1];
		
		echo($image_name."<br>");
		
		$image = $wgOut->parse("[[image:$image_name]]");
		preg_match("/<a.*?>.*?<img.*?>.*?<\/a.*?>/i", $image, $a);
		$image = $a[0];
		
		//echo($image."<br>"."<br>");
		
		foreach ($att_list as $att) {
		
			preg_match("/$att=[\"\']([^\"\'])*?[\"\']/i", $image, $a);
			$content = $a[0];
			
			//echo($att."  ".$image_name."<br>");
			
			$img_tag = preg_replace( "/(.*?)($att=[\"\'][^\"\']*[\"\'])(.*?)/i", "\\1$content\\3", $img_tag );
		}
		
		$list_img["$img_md5"] = $img_tag;
	}
	


	foreach($list_md5 as $md5) {
		$img_tag = $list_img["$md5"];
		$text = str_replace( $md5, $img_tag, $text );
		//echo($img_tag);
	}

	}
	
//die("end");
	return true;
}
*/

function wfFCKeditorAddFCKScript ($q) { 

	global $wgOut, $wgTitle, $wgScriptPath;
	global $wgFCKexcludedNamespaces, $wgFCKUseEditor, $wgFCKEditorToolbarSet; 
	global $wgFCKEditorDir, $wgFCKEditorHeight;

	$ns_allowed = true;
	$ns = $wgTitle->getNamespace();
	if (in_array($ns, $wgFCKexcludedNamespaces)) $ns_allowed = false;
	
	if ($ns_allowed and $wgFCKUseEditor) {
		//include_once XOOPS_ROOT_PATH."/Frameworks/xoops22/class/xoopsformloader.php";
		
		if(!file_exists($config_file = XOOPS_ROOT_PATH."/cache/fckconfig.".$GLOBALS["xoopsModule"]->getVar("dirname").".js")) {
			if ( $fp = fopen( $config_file , "wt" ) ) {
				/*
				$fconfig = XOOPS_ROOT_PATH."/Frameworks/xoops22/class/xoopseditor/FCKeditor/fckeditor.js";
				$fr = fopen($fconfig, "r");
				$fp_content .= fread($fr, filesize($fconfig));
				fclose($fr);
				*/
				$fconfig = XOOPS_ROOT_PATH."/modules/".$GLOBALS["xoopsModule"]->getVar("dirname")."/fckeditor.config.js";
				$fr = fopen($fconfig, "r");
				$fp_content = fread($fr, filesize($fconfig));
				fclose($fr);
				
				/*
				$fp_content = implode("", file("$wgFCKEditorDir/fckeditor.js")) . "\n\n";
				$fp_content .= "// FCKconfig module configuration ";
				if(is_readable($config_mod = XOOPS_ROOT_PATH."/modules/".$GLOBALS["xoopsModule"]->getVar("dirname")."/fckeditor.config.js")) {
					$fp_content .= "// Loaded from module local config file".implode("", file($config_mod));
				}
				*/
				//$fp_content .= "FCKConfig.DefaultLanguage = '".str_replace('_','-',strtolower(_LANGCODE))."';";
				if(is_readable(XOOPS_ROOT_PATH."/modules/".$GLOBALS["xoopsModule"]->getVar("dirname")."/fckeditor.connector.php")) {
					$fp_content .= "var browser_path = '{$wgFCKEditorDir}/editor/filemanager/browser/default/browser.html?Connector=".XOOPS_URL."/modules/".$GLOBALS["xoopsModule"]->getVar("dirname")."/fckeditor.connector.php';";
					$fp_content .= "FCKConfig.LinkBrowserURL = browser_path ;";
					$fp_content .= "FCKConfig.ImageBrowserURL = browser_path + '&Type=Image';";
					$fp_content .= "FCKConfig.FlashBrowserURL = browser_path + '&Type=Flash';";
				}
				if(is_readable(XOOPS_ROOT_PATH."/modules/".$GLOBALS["xoopsModule"]->getVar("dirname")."/fckeditor.upload.php")) {
					$fp_content .= "var uploader_path = '".XOOPS_URL."/modules/".$GLOBALS["xoopsModule"]->getVar("dirname")."/fckeditor.upload.php';";
					$fp_content .= "FCKConfig.LinkUploadURL = uploader_path;";
					$fp_content .= "FCKConfig.ImageUploadURL = uploader_path + '?Type=Image';";
					$fp_content .= "FCKConfig.FlashUploadURL = uploader_path + '?Type=Flash';";
				}
				if(FALSE) {
					$fp_content .= "FCKConfig.LinkUpload = false;";
					$fp_content .= "FCKConfig.ImageUpload = false;";
					$fp_content .= "FCKConfig.FlashUpload = false;";
				}
				
				fwrite( $fp, $fp_content );
				fclose( $fp );
			} else {
				xoops_error( "Cannot create fckeditor config file" );
			}
		}
		
		//$js_content = implode("", file("$wgFCKEditorDir/fckeditor.js")) . "\n\n" . implode("", file($config_file));
		

		//$wgOut->addScript( "<script type=\"text/javascript\">{$js_content}</script>\n" );
		$wgOut->addScript( "<script type=\"text/javascript\" src=\"$wgFCKEditorDir/fckeditor.js\"></script>\n" );
		$script_local_config = XOOPS_URL."/cache/fckconfig.".$GLOBALS["xoopsModule"]->getVar("dirname").".js";
		//$wgOut->addScript( "<script type=\"text/javascript\" src=\"".$script_local_config."\"></script>\n" );
		
		$wgOut->addScript("
			<script type=\"text/javascript\"> 
				function onLoadFCK () { 
					var oFCKeditor = new FCKeditor('wpTextbox1') ; 
					oFCKeditor.BasePath = \"$wgFCKEditorDir/\" ; 		
					oFCKeditor.Config['CustomConfigurationsPath'] = \"$script_local_config\" ; 		
					oFCKeditor.Config['DefaultLanguage'] = \"".str_replace('_','-',strtolower(_LANGCODE))."\" ; 		
					if (document.getElementById(\"wpTextbox1\")) {
						oFCKeditor.Height = \"$wgFCKEditorHeight\" ; 
						oFCKeditor.ToolbarSet = \"$wgFCKEditorToolbarSet\" ; 
						oFCKeditor.ReplaceTextarea() ; 
						var oDiv=document.getElementById(\"toolbar\"); 
						oDiv.style.cssText = 'display: none;'; 
					}
				} 
				addOnloadHook(onLoadFCK); 
			</script>\n
		");
	}
	
	return true;
}

function wfFCKeditorCheck ($q, $text) { 

	global $wgFCKUseEditor, $wgFCKEditorToken;
	
	if (preg_match("/$wgFCKEditorToken/i", $text, $a)) {
		$wgFCKUseEditor = true;
	}
	
	return true;

}


function wfFCKeditorBypassParserCut ($q, $text) {

	global $wgFCKexcludedNamespaces, $wgFCKUseEditor, $wgTitle;
	global $wgFCKBypassText, $wgFCKEditorToken;
	
	$ns_allowed = true;
	$ns = $wgTitle->getNamespace();
	
	if (in_array($ns, $wgFCKexcludedNamespaces)) $ns_allowed = false;
	
	if ($ns_allowed and $wgFCKUseEditor) {
		
		$wgFCKBypassText = $text;
		$text = "";
	}
	
	return true;
}


function wfFCKeditorBypassParserPaste ($q, $text) {

	global $wgOut, $wgTitle, $wgParser;
	global $wgFCKexcludedNamespaces, $wgFCKEditorHeight, $wgFCKEditorToolbarSet, $wgFCKUseEditor;
	global $wgFCKBypassText, $wgFCKEditorToken, $wgFCKEditorAllow_a_tags, $wgFCKEditorAllow_img_tags;
	
	$List   = array();
	
	$ns_allowed = true;
	$ns = $wgTitle->getNamespace();
	if (in_array($ns, $wgFCKexcludedNamespaces)) $ns_allowed = false;
	
	if ($ns_allowed and $wgFCKUseEditor) {
	
		$fcktext = $wgFCKBypassText; 
		
		if ($wgFCKEditorAllow_a_tags) {
			$i = 0;
			$ta = md5("aopen");
			while (preg_match("|(<a.*?>)|i", $fcktext, $a)) {
				$j = $ta."_".md5($i);
				$List[$j]["content"] = $a[0];
				$List[$j]["index"] = $j;
				$fcktext = str_replace($a[0], $j, $fcktext);
				$i++;
			}
			$i = 0;
			$ta = md5("aclose");
			while (preg_match("|(</a>)|i", $fcktext, $a)) {
				$j = $ta."_".md5($i);
				$List[$j]["content"] = $a[0];
				$List[$j]["index"] = $j;
				$fcktext = str_replace($a[0], $j, $fcktext);
				$i++;
			}
		}
		if ($wgFCKEditorAllow_img_tags) {
			$i = 0;
			$timg = md5("img");
			while (preg_match("|(<img[^>]*?/>)|i", $fcktext, $a)) {
				$j = $timg."_".md5($i);
				$List[$j]["content"] = $a[0];
				$List[$j]["index"] = $j;
				$fcktext = str_replace($a[0], $j, $fcktext);
				$i++;
			}
		}
	
		$tagList = array("pre", "math", "gallery", "nowiki", "html");
		foreach($tagList as $tag) {
			while (preg_match("|&lt;($tag.*?)&gt;(.*?)&lt;/$tag&gt;|is", $fcktext, $a)) {	
				$r = preg_replace("|<br.*?>|i", "", $a[0]);
				$r = preg_replace("|&nbsp;|i", " ", $r);
				$fcktext = str_replace($a[0], html_entity_decode($r), $fcktext);
			}
		}

		foreach($q->mTagHooks as $tag => $func) {
			while (preg_match("|&lt;($tag.*?)&gt;(.*?)&lt;/$tag&gt;|is", $fcktext, $a)) {	
				$r = preg_replace("|<br.*?>|i", "", $a[0]);
				$r = preg_replace("|&nbsp;|i", " ", $r);
				$fcktext = str_replace($a[0], html_entity_decode($r), $fcktext);
			}
		}
	
		$x =& $state;
		$fcktext = $q->strip($fcktext, $x);

		$fcktext = preg_replace("/<\/?tbody>/i","", $fcktext);
		$fcktext = preg_replace("/$wgFCKEditorToken/i","", $fcktext);	
		$fcktext = Sanitizer::removeHTMLtags( $fcktext, array( &$q, 'attributeStripCallback' ) );

		$fcktext = $q->replaceVariables($fcktext);
		$fcktext = $q->stripToc( $fcktext ); 	

		$fcktext = $q->replaceInternalLinks( $fcktext );
		$fcktext = $q->replaceExternalLinks( $fcktext );
	
		# replaceInternalLinks may sometimes leave behind
		# absolute URLs, which have to be masked to hide them from replaceExternalLinks
		$fcktext = str_replace($q->mUniqPrefix."NOPARSE", "", $fcktext);
		
		$fcktext = $q->doMagicLinks( $fcktext );
		$fcktext = $q->formatHeadings( $fcktext, true );
	
		$q->replaceLinkHolders( $fcktext );
	
		$fcktext = $q->unstripNoWiki( $fcktext, $state );
		$fcktext = $q->unstrip($fcktext, $state);
	
		foreach($List as $item) {
			$fcktext = str_replace($item["index"], $item["content"], $fcktext);
			$i++;
		}
	
		$text = $fcktext;
	}
	
	return true;
}

function wfSajaxSearchImageFCKeditor( $term ) {
	global $wgContLang, $wgAjaxCachePolicy, $wgOut;
	$limit = 10;
	
	$term = $wgContLang->checkTitleEncoding( $wgContLang->recodeInput( js_unescape( $term ) ) );
	$term1 = str_replace( ' ', '_', $wgContLang->ucfirst( $term ) );
	$term2 = str_replace( ' ', '_', $wgContLang->lc( $term ) );
	$term3 = str_replace( ' ', '_', $wgContLang->uc( $term ) );
	$term = $term1;

	if ( strlen( str_replace( '_', '', $term ) )<3 )
		return "<input type=\"hidden\" name=\"wfSajaxSearchImageFCKeditor\" value=\"\"><br /><b>\"".$term2."\"</b>: Type one more character ...";

	$wgAjaxCachePolicy->setPolicy( 30*60 );

	$db =& wfGetDB( DB_SLAVE );
	$res = $db->select( 'page', 'page_title',
			array(  'page_namespace' => 6,
				"LCASE(page_title) LIKE '%". $db->strencode( $term2 ) ."%'" ),
				"wfSajaxSearch",
				array( 'LIMIT' => $limit+1 )
			);

	$r = "";

	$i=0;
	while ( ( $row = $db->fetchObject( $res ) ) && ( ++$i <= $limit ) ) {

		$im = Image::newFromName($row->page_title);
		$url = $im->getURL();
		
		$ti = Title::makeTitle(6, $row->page_title);
		
		$tiURL = "";
		$tiName= "";
		if (is_object($ti)) {
			$tiURL  = $ti->getLocalURL();
			$tiName = $ti->getPrefixedDBkey();
		}

		$r .= '<li>' . "<div style=\"background-color: #DFDFDF; cursor: pointer;\" onMouseover=\"this.style.backgroundColor='#0099FF'\" onMouseout=\"this.style.backgroundColor='#DFDFDF'\" onclick=\"clickOnList('".$row->page_title."','".$url."','".$tiURL."','".$tiName."')\">".htmlspecialchars( $row->page_title ) .'</div>'. "</li>\n";
	}

	$term = htmlspecialchars( $term );

	return "<input type=\"hidden\" name=\"wfSajaxSearchImageFCKeditor\" value=\"\"><br />Images containing <b>\"".$term2."\"</b><br /><ul>" .$r ."</ul>";
}


?>