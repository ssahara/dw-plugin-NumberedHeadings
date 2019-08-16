<?php
/**
 * Options for the Numbered Headings Plugin
 *
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

$conf['startlevel'] = 2;  // heading level corresponding to the 1st tier
$conf['format'] = '["%d.", "%d.%d", "%d.%d.%d", "%d.%d.%d.%d", "%d.%d.%d.%d.%d"]';
$conf['tailingdot'] = 0;  // add a tailing dot after sub-tier numbers (default off)
$conf['fancy']      = 0;  // enclose tiered numbers in span tag (default off)
