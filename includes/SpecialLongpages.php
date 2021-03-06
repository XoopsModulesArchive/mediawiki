<?php
/**
 *
 * @package MediaWiki
 * @subpackage SpecialPage
 */

/**
 *
 */
require_once 'SpecialShortpages.php';

/**
 *
 * @package MediaWiki
 * @subpackage SpecialPage
 */
class LongPagesPage extends ShortPagesPage
{
    function getName()
    {
        return "Longpages";
    }

    function sortDescending()
    {
        return true;
    }
}

/**
 * constructor
 */
function wfSpecialLongpages()
{
    list( $limit, $offset ) = wfCheckLimits();

    $lpp = new LongPagesPage();

    $lpp->doQuery( $offset, $limit );
}
