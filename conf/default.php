<?php
/**
 * Options for the Numbered Headings Plugin
 *
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

$conf['tier1']  = 0;  // heading level corresponding to the 1st tier
$conf['format'] = '["%s.", "%s.%s", "%s.%s.%s", "%s.%s.%s.%s", "%s.%s.%s.%s.%s"]';
$conf['tailingdot'] = 0;  // add a tailing dot after sub-tier numbers (default off)
$conf['fancy']  = 0;  // enclose tiered numbers in span tag (default off)
