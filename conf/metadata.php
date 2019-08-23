<?php
/**
 * Metadata for the Numbered Headings Plugin
 *
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

$meta['tier1']  = array('multichoice', '_choices' => array(0, 1, 2 ,3, 4, 5));
$meta['format'] = array('string', '_pattern' => '/^\[(?:(?: *\, *| *)(?:"([^"]*)"))*(?: *)\]$/');
$meta['fancy']  = array('onoff');
