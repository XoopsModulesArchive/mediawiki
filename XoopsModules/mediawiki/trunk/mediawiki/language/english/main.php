<?php
define("_MD_MEDIAWIKI_XOOPSMODE", "Switch to XOOPS mode");
define("_MD_MEDIAWIKI_MEDIAWIKIMODE", "Switch to MediaWiki mode");

/*
 * A function used by UtfNormal.php to clean up invalid UTF-8 sequences
 *
 * If the function is not defined, UtfNormal::quickIsNFCVerify will be called, which causes cleanup failure in some languages
 * So, if you have problem with mediawiki input, set $skipUtf8Cleanup = 1;
 *
 */

$skipUtf8Cleanup = 1; // Change to 1 if you have problem with mediawiki input.

if($skipUtf8Cleanup):
function utf8_normalize($string = null)
{
    return $string;
}
endif;
