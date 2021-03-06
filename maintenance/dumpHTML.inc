<?php
/**
 * @package MediaWiki
 * @subpackage Maintenance
 */

define( 'REPORTING_INTERVAL', 10 );

require_once( 'includes/ImagePage.php' );
require_once( 'includes/CategoryPage.php' );
require_once( 'includes/RawPage.php' );

class DumpHTML {
	# Destination directory
	var $dest;

	# Show interlanguage links?
	var $interwiki = true;

	# Depth of HTML directory tree
	var $depth = 3;

	# Directory that commons images are copied into
	var $sharedStaticPath;

	# Relative path to image directory
	var $imageRel = 'upload';

	# Copy commons images instead of symlinking
	var $forceCopy = false;

	# Make links assuming the script path is in the same directory as
	# the destination
	var $alternateScriptPath = false;

	# Original values of various globals
	var $oldArticlePath = false, $oldCopyrightIcon = false;

	# Has setupGlobals been called?
	var $setupDone = false;

	# List of raw pages used in the current article
	var $rawPages;

	# Skin to use
	var $skin = 'dumphtml';

	function DumpHTML( $settings ) {
		foreach ( $settings as $var => $value ) {
			$this->$var = $value;
		}
	}

	/**
	 * Write a set of articles specified by start and end page_id
	 * Skip categories and images, they will be done separately
	 */
	function doArticles( $start, $end = false ) {
		$fname = 'DumpHTML::doArticles';

		$this->setupGlobals();

		if ( $end === false ) {
			$dbr =& wfGetDB( DB_SLAVE );
			$end = $dbr->selectField( 'page', 'max(page_id)', false, $fname );
		}

		$mainPageObj = Title::newMainPage();
		$mainPage = $mainPageObj->getPrefixedDBkey();


		for ($id = $start; $id <= $end; $id++) {
			wfWaitForSlaves( 20 );
			if ( !($id % REPORTING_INTERVAL) ) {
				print "Processing ID: $id\r";
			}
			if ( !($id % (REPORTING_INTERVAL*10) ) ) {
				print "\n";
			}
			$title = Title::newFromID( $id );
			if ( $title ) {
				$ns = $title->getNamespace() ;
				if ( $ns != NS_CATEGORY && $title->getPrefixedDBkey() != $mainPage ) {
					$this->doArticle( $title );
				}
			}
		}
		print "\n";
	}

	function doSpecials() {
		$this->doMainPage();

		$this->setupGlobals();
		print "Special:Categories...";
		$this->doArticle( Title::makeTitle( NS_SPECIAL, 'Categories' ) );
		print "\n";
	}

	/** Write the main page as index.html */
	function doMainPage() {

		print "Making index.html  ";

		// Set up globals with no ../../.. in the link URLs
		$this->setupGlobals( 0 );

		$title = Title::newMainPage();
		$text = $this->getArticleHTML( $title );
		$file = fopen( "{$this->dest}/index.html", "w" );
		if ( !$file ) {
			print "\nCan't open index.html for writing\n";
			return false;
		}
		fwrite( $file, $text );
		fclose( $file );
		print "\n";
	}

	function doImageDescriptions() {
		global $wgSharedUploadDirectory;

		$fname = 'DumpHTML::doImageDescriptions';

		$this->setupGlobals();

		/**
		 * Dump image description pages that don't have an associated article, but do
		 * have a local image
		 */
		$dbr =& wfGetDB( DB_SLAVE );
		extract( $dbr->tableNames( 'image', 'page' ) );
		$res = $dbr->select( 'image', array( 'img_name' ), false, $fname );

		$i = 0;
		print "Writing image description pages for local images\n";
		$num = $dbr->numRows( $res );
		while ( $row = $dbr->fetchObject( $res ) ) {
			wfWaitForSlaves( 10 );
			if ( !( ++$i % REPORTING_INTERVAL ) ) {
				print "Done $i of $num\r";
			}
			$title = Title::makeTitle( NS_IMAGE, $row->img_name );
			if ( $title->getArticleID() ) {
				// Already done by dumpHTML
				continue;
			}
			$this->doArticle( $title );
		}
		print "\n";

		/**
		 * Dump images which only have a real description page on commons
		 */
		print "Writing description pages for commons images\n";
		$i = 0;
		for ( $hash = 0; $hash < 256; $hash++ ) {
			$dir = sprintf( "%01x/%02x", intval( $hash / 16 ), $hash );
			$paths = array_merge( glob( "{$this->sharedStaticPath}/$dir/*" ),
				glob( "{$this->sharedStaticPath}/thumb/$dir/*" ) );

			foreach ( $paths as $path ) {
				$file = basename( $path );
				if ( !(++$i % REPORTING_INTERVAL ) ) {
					print "$i\r";
				}

				$title = Title::makeTitle( NS_IMAGE, $file );
				$this->doArticle( $title );
			}
		}
		print "\n";
	}

	function doCategories() {
		$fname = 'DumpHTML::doCategories';
		$this->setupGlobals();

		$dbr =& wfGetDB( DB_SLAVE );
		print "Selecting categories...";
		$sql = 'SELECT DISTINCT cl_to FROM ' . $dbr->tableName( 'categorylinks' );
		$res = $dbr->query( $sql, $fname );

		print "\nWriting " . $dbr->numRows( $res ).  " category pages\n";
		$i = 0;
		while ( $row = $dbr->fetchObject( $res ) ) {
			wfWaitForSlaves( 10 );
			if ( !(++$i % REPORTING_INTERVAL ) ) {
				print "$i\r";
			}
			$title = Title::makeTitle( NS_CATEGORY, $row->cl_to );
			$this->doArticle( $title );
		}
		print "\n";
	}

	function doRedirects() {
		print "Doing redirects...\n";
		$fname = 'DumpHTML::doRedirects';
		$this->setupGlobals();
		$dbr =& wfGetDB( DB_SLAVE );

		$res = $dbr->select( 'page', array( 'page_namespace', 'page_title' ),
			array( 'page_is_redirect' => 1 ), $fname );
		$num = $dbr->numRows( $res );
		print "$num redirects to do...\n";
		$i = 0;
		while ( $row = $dbr->fetchObject( $res ) ) {
			$title = Title::makeTitle( $row->page_namespace, $row->page_title );
                        if ( !(++$i % (REPORTING_INTERVAL*10) ) ) {
                                print "Done $i of $num\n";
                        }
			$this->doArticle( $title );
		}
	}

	/** Write an article specified by title */
	function doArticle( $title ) {
		global $wgTitle, $wgSharedUploadPath, $wgSharedUploadDirectory;
		global $wgUploadDirectory;

		$this->rawPages = array();
		$text = $this->getArticleHTML( $title );

		if ( $text === false ) {
			return;
		}

		# Parse the XHTML to find the images
		$images = $this->findImages( $text );
		$this->copyImages( $images );

		# Write to file
		$this->writeArticle( $title, $text );

		# Do raw pages
		wfMkdirParents( "{$this->dest}/raw", 0755 );
		foreach( $this->rawPages as $record ) {
			list( $file, $title, $params ) = $record;

			$path = "{$this->dest}/raw/$file";
			if ( !file_exists( $path ) ) {
				$article = new Article( $title );
				$request = new FauxRequest( $params );
				$rp = new RawPage( $article, $request );
				$text = $rp->getRawText();

				print "Writing $file\n";
				$file = fopen( $path, 'w' );
				if ( !$file ) {
					print("Can't open file $fullName for writing\n");
					continue;
				}
				fwrite( $file, $text );
				fclose( $file );
			}
		}
	}

	/** Write the given text to the file identified by the given title object */
	function writeArticle( &$title, $text ) {
		$filename = $this->getHashedFilename( $title );
		$fullName = "{$this->dest}/$filename";
		$fullDir = dirname( $fullName );

		wfMkdirParents( $fullDir, 0755 );

		$file = fopen( $fullName, 'w' );
		if ( !$file ) {
			print("Can't open file $fullName for writing\n");
			return;
		}

		fwrite( $file, $text );
		fclose( $file );
	}

	/** Set up globals required for parsing */
	function setupGlobals( $currentDepth = NULL ) {
		global $wgUser, $wgTitle, $wgStylePath, $wgArticlePath, $wgMathPath;
		global $wgUploadPath, $wgLogo, $wgMaxCredits, $wgSharedUploadPath;
		global $wgHideInterlanguageLinks, $wgUploadDirectory, $wgThumbnailScriptPath;
		global $wgSharedThumbnailScriptPath, $wgEnableParserCache, $wgHooks, $wgServer;
		global $wgRightsUrl, $wgRightsText, $wgCopyrightIcon;

		static $oldLogo = NULL;

		if ( !$this->setupDone ) {
			$wgHooks['GetLocalURL'][] =& $this;
			$wgHooks['GetFullURL'][] =& $this;
			$this->oldArticlePath = $wgServer . $wgArticlePath;
		}

		if ( is_null( $currentDepth ) ) {
			$currentDepth = $this->depth;
		}

		if ( $this->alternateScriptPath ) {
			if ( $currentDepth == 0 ) {
				$wgScriptPath = '.';
			} else {
				$wgScriptPath = '..' . str_repeat( '/..', $currentDepth - 1 );
			}
		} else {
			$wgScriptPath = '..' . str_repeat( '/..', $currentDepth );
		}

		$wgArticlePath = str_repeat( '../', $currentDepth ) . '$1';

		# Logo image
		# Allow for repeated setup
		if ( !is_null( $oldLogo ) ) {
			$wgLogo = $oldLogo;
		} else {
			$oldLogo = $wgLogo;
		}

		if ( strpos( $wgLogo, $wgUploadPath ) === 0 ) {
			# If it's in the upload directory, rewrite it to the new upload directory
			$wgLogo = "$wgScriptPath/{$this->imageRel}/" . substr( $wgLogo, strlen( $wgUploadPath ) + 1 );
		} elseif ( $wgLogo{0} == '/' ) {
			# This is basically heuristic
			# Rewrite an absolute logo path to one relative to the the script path
			$wgLogo = $wgScriptPath . $wgLogo;
		}

		# Another ugly hack
		if ( !$this->setupDone ) {
			$this->oldCopyrightIcon = $wgCopyrightIcon;
		}
		$wgCopyrightIcon = str_replace( 'src="/images',
			'src="' . htmlspecialchars( $wgScriptPath ) . '/images', $this->oldCopyrightIcon );



		$wgStylePath = "$wgScriptPath/skins";
		$wgUploadPath = "$wgScriptPath/{$this->imageRel}";
		$wgSharedUploadPath = "$wgUploadPath/shared";
		$wgMaxCredits = -1;
		$wgHideInterlanguageLinks = !$this->interwiki;
		$wgThumbnailScriptPath = $wgSharedThumbnailScriptPath = false;
		$wgEnableParserCache = false;
		$wgMathPath = "$wgScriptPath/math";

		if ( !empty( $wgRightsText ) ) {
			$wgRightsUrl = "$wgScriptPath/COPYING.html";
		}

		$wgUser = new User;
		$wgUser->setOption( 'skin', $this->skin );
		$wgUser->setOption( 'editsection', 0 );

		$this->sharedStaticPath = "$wgUploadDirectory/shared";

		$this->setupDone = true;
	}

	/** Reads the content of a title object, executes the skin and captures the result */
	function getArticleHTML( &$title ) {
		global $wgOut, $wgTitle, $wgArticle, $wgUser;

		$linkCache =& LinkCache::singleton();
		$linkCache->clear();
		$wgTitle = $title;
		if ( is_null( $wgTitle ) ) {
			return false;
		}

		$ns = $wgTitle->getNamespace();
		if ( $ns == NS_SPECIAL ) {
			$wgOut = new OutputPage;
			$wgOut->setParserOptions( new ParserOptions );
			SpecialPage::executePath( $wgTitle );
		} else {
			/** @todo merge with Wiki.php code */
			if ( $ns == NS_IMAGE ) {
				$wgArticle = new ImagePage( $wgTitle );
			} elseif ( $ns == NS_CATEGORY ) {
				$wgArticle = new CategoryPage( $wgTitle );
			} else {
				$wgArticle = new Article( $wgTitle );
			}
			$rt = Title::newFromRedirect( $wgArticle->fetchContent() );
			if ( $rt != NULL ) {
				return $this->getRedirect( $rt );
			} else {
				$wgOut = new OutputPage;
				$wgOut->setParserOptions( new ParserOptions );

				$wgArticle->view();
			}
		}

		$sk =& $wgUser->getSkin();
		ob_start();
		$sk->outputPage( $wgOut );
		$text = ob_get_contents();
		ob_end_clean();

		return $text;
	}

	function getRedirect( $rt ) {
		$url = $rt->escapeLocalURL();
		$text = $rt->getPrefixedText();
		return <<<ENDTEXT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta http-equiv="Refresh" content="0;url=$url" />
</head>
<body>
  <p>Redirecting to <a href="$url">$text</a></p>
</body>
</html>
ENDTEXT;
	}

	/** Returns image paths used in an XHTML document */
	function findImages( $text ) {
		global $wgOutputEncoding, $wgDumpImages;
		$parser = xml_parser_create( $wgOutputEncoding );
		xml_set_element_handler( $parser, 'wfDumpStartTagHandler', 'wfDumpEndTagHandler' );

		$wgDumpImages = array();
		xml_parse( $parser, $text );
		xml_parser_free( $parser );

		return $wgDumpImages;
	}

	/**
	 * Copy images (or create symlinks) from commons to a static directory.
	 * This is necessary even if you intend to distribute all of commons, because
	 * the directory contents is used to work out which image description pages
	 * are needed.
	 *
	 * Also copies math images
	 *
	 */
	function copyImages( $images ) {
		global $wgSharedUploadPath, $wgSharedUploadDirectory, $wgMathPath, $wgMathDirectory;
		# Find shared uploads and copy them into the static directory
		$sharedPathLength = strlen( $wgSharedUploadPath );
		$mathPathLength = strlen( $wgMathPath );
		foreach ( $images as $escapedImage => $dummy ) {
			$image = urldecode( $escapedImage );

			# Is it shared?
			if ( substr( $image, 0, $sharedPathLength ) == $wgSharedUploadPath ) {
				# Reconstruct full filename
				$rel = substr( $image, $sharedPathLength + 1 ); // +1 for slash
				$sourceLoc = "$wgSharedUploadDirectory/$rel";
				$staticLoc = "{$this->sharedStaticPath}/$rel";
				#print "Copying $sourceLoc to $staticLoc\n";
				# Copy to static directory
				if ( !file_exists( $staticLoc ) ) {
					wfMkdirParents( dirname( $staticLoc ), 0755 );
					if ( function_exists( 'symlink' ) && !$this->forceCopy ) {
						symlink( $sourceLoc, $staticLoc );
					} else {
						copy( $sourceLoc, $staticLoc );
					}
				}

				if ( substr( $rel, 0, 6 ) == 'thumb/' ) {
					# That was a thumbnail
					# We will also copy the real image
					$parts = explode( '/', $rel );
					$rel = "{$parts[1]}/{$parts[2]}/{$parts[3]}";
					$sourceLoc = "$wgSharedUploadDirectory/$rel";
					$staticLoc = "{$this->sharedStaticPath}/$rel";
					#print "Copying $sourceLoc to $staticLoc\n";
					if ( !file_exists( $staticLoc ) ) {
						wfMkdirParents( dirname( $staticLoc ), 0755 );
						if ( function_exists( 'symlink' ) && !$this->forceCopy ) {
							symlink( $sourceLoc, $staticLoc );
						} else {
							copy( $sourceLoc, $staticLoc );
						}
					}
				}
			} else
			# Is it math?
			if ( substr( $image, 0, $mathPathLength ) == $wgMathPath ) {
				$rel = substr( $image, $mathPathLength + 1 ); // +1 for slash
				$source = "$wgMathDirectory/$rel";
				$dest = "{$this->dest}/math/$rel";
				@mkdir( "{$this->dest}/math", 0755 );
				if ( !file_exists( $dest ) ) {
					copy( $source, $dest );
				}
			}
		}
	}

	function onGetFullURL( &$title, &$url, $query ) {
		global $wgContLang, $wgArticlePath;

		$iw = $title->getInterwiki();
		if ( $title->isExternal() && $wgContLang->getLanguageName( $iw ) ) {
			if ( $title->getDBkey() == '' ) {
				$url = str_replace( '$1', "../$iw/index.html", $wgArticlePath );
			} else {
				$url = str_replace( '$1', "../$iw/" . wfUrlencode( $this->getHashedFilename( $title ) ),
					$wgArticlePath );
			}
			return false;
		} else {
			return true;
		}
	}

	function onGetLocalURL( &$title, &$url, $query ) {
		global $wgArticlePath;

		if ( $title->isExternal() ) {
			# Default is fine for interwiki
			return true;
		}

		$url = false;
		if ( $query != '' ) {
			parse_str( $query, $params );
			if ( isset($params['action']) && $params['action'] == 'raw' ) {
				if ( $params['gen'] == 'css' || $params['gen'] == 'js' ) {
					$file = 'gen.' . $params['gen'];
				} else {
					$file = $this->getFriendlyName( $title->getPrefixedDBkey() );
					// Clean up Monobook.css etc.
					if ( preg_match( '/^(.*)\.(css|js)_[0-9a-f]{4}$/', $file, $matches ) ) {
						$file = $matches[1] . '.' . $matches[2];
					}
				}
				$this->rawPages[$file] = array( $file, $title, $params );
				$url = str_replace( '$1', "raw/" . wfUrlencode( $file ), $wgArticlePath );
			}
		}
		if ( $url === false ) {
			$url = str_replace( '$1', wfUrlencode( $this->getHashedFilename( $title ) ), $wgArticlePath );
		}

		return false;
	}

	function getHashedFilename( &$title ) {
		if ( '' != $title->mInterwiki ) {
			$dbkey = $title->getDBkey();
		} else {
			$dbkey = $title->getPrefixedDBkey();
		}

		$mainPage = Title::newMainPage();
		if ( $mainPage->getPrefixedDBkey() == $dbkey ) {
			return 'index.html';
		}

		return $this->getHashedDirectory( $title ) . '/' .
			$this->getFriendlyName( $dbkey ) . '.html';
	}

	function getFriendlyName( $name ) {
		global $wgLang;
		# Replace illegal characters for Windows paths with underscores
		$friendlyName = strtr( $name, '/\\*?"<>|~', '_________' );

		# Work out lower case form. We assume we're on a system with case-insensitive
		# filenames, so unless the case is of a special form, we have to disambiguate
		if ( function_exists( 'mb_strtolower' ) ) {
			$lowerCase = $wgLang->ucfirst( mb_strtolower( $name ) );
		} else {
			$lowerCase = ucfirst( strtolower( $name ) );
		}

		# Make it mostly unique
		if ( $lowerCase != $friendlyName  ) {
			$friendlyName .= '_' . substr(md5( $name ), 0, 4);
		}
		# Handle colon specially by replacing it with tilde
		# Thus we reduce the number of paths with hashes appended
		$friendlyName = str_replace( ':', '~', $friendlyName );

		return $friendlyName;
	}

	/**
	 * Get a relative directory for putting a title into
	 */
	function getHashedDirectory( &$title ) {
		if ( '' != $title->getInterwiki() ) {
			$pdbk = $title->getDBkey();
		} else {
			$pdbk = $title->getPrefixedDBkey();
		}

		# Find the first colon if there is one, use characters after it
		$p = strpos( $pdbk, ':' );
		if ( $p !== false ) {
			$dbk = substr( $pdbk, $p + 1 );
			$dbk = substr( $dbk, strspn( $dbk, '_' ) );
		} else {
			$dbk = $pdbk;
		}

		# Split into characters
		preg_match_all( '/./us', $dbk, $m );

		$chars = $m[0];
		$length = count( $chars );
		$dir = '';

		for ( $i = 0; $i < $this->depth; ++$i ) {
			if ( $i ) {
				$dir .= '/';
			}
			if ( $i >= $length ) {
				$dir .= '_';
			} else {
				$c = $chars[$i];
				if ( ord( $c ) >= 128 || preg_match( '/[a-zA-Z0-9!#$%&()+,[\]^_`{}-]/', $c ) ) {
					if ( function_exists( 'mb_strtolower' ) ) {
						$dir .= mb_strtolower( $c );
					} else {
						$dir .= strtolower( $c );
					}
				} else {
					$dir .= sprintf( "%02X", ord( $c ) );
				}
			}
		}
		return $dir;
	}

}

/** XML parser callback */
function wfDumpStartTagHandler( $parser, $name, $attribs ) {
	global $wgDumpImages;

	if ( $name == 'IMG' && isset( $attribs['SRC'] ) ) {
		$wgDumpImages[$attribs['SRC']] = true;
	}
}

/** XML parser callback */
function wfDumpEndTagHandler( $parser, $name ) {}

# vim: syn=php
?>
