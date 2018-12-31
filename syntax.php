<?php
/**
 * Plugin Numbered Headings: Plugin to add numbered headings to DokuWiki-Syntax
 *
 * Usage:   ====== - Heading Level 1======
 *          ===== - Heading Level 2 =====
 *          ===== - Heading Level 2 =====
 *                   ...
 *
 * =>       1 Heading Level 1
 *              1.1 Heading Level 2
 *              1.2 Heading Level 2
 *          ...
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 */

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

class syntax_plugin_numberedheadings extends DokuWiki_Syntax_Plugin {

    var $headingCount =
                 array(  1=>0,
                         2=>0,
                         3=>0,
                         4=>0,
                         5=>0);

    protected $startlevel, $tailingdot;

    function __construct() {
        // retrieve once config settings
        //   startlevel: upper headline level for hierarchical numbering (default = 2)
        //   tailingdot: show a tailing dot after numbers (default = 0)
        $this->startlevel = $this->getConf('startlevel');
        $this->tailingdot = $this->getConf('tailingdot');
    }

    function getType(){
        return 'substition';
    }

    /**
     * Connect pattern to lexer
     */
    protected $mode, $pattern;

    function preConnect() {
        // syntax mode, drop 'syntax_' from class name
        $this->mode = substr(get_class($this), 7);

        // syntax pattern
        $this->pattern[5] = '^[ \t]*={2,6} ?-(?: ?#[0-9]+)? [^\n]+={2,6}[ \t]*(?=\n)';
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern[5], $mode, $this->mode);

        $this->Lexer->addSpecialPattern(
                        '{{header>[1-5]}}', $mode, $this->mode);

        // added new parameter (matches the parameter name for better recognition)
        $this->Lexer->addSpecialPattern(
                        '{{startlevel>[1-5]}}', $mode, $this->mode);
    }

    function getSort() {
        return 45;
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {

        // obtain the startlevel from the page if defined
        if ($match[0] != '=') {
            $this->startlevel = substr($match, -3, 1);
            return true;
        }

        // obtain the level of the heading
        $title = trim($match);
        $level = 7 - strspn($title, '=');

        // obtain the startnumber if defined
        $title = trim($title, '= ');  // drop heading markup
        $title = ltrim($title, '- '); // not drop tailing -
        if ($title[0] == '#') {
            $title = substr($title, 1); // drop #
            $i = strspn($title, '0123456789');
            $number = substr($title, 0, $i) + 0;
            $title  = ltrim(substr($title, $i));
            // set the number of the heading
            $this->headingCount[$level] = $number;
        } else {
            // increment the number of the heading
            $this->headingCount[$level]++;
        }

        // reset the number of the subheadings
        for ($i = $level +1; $i <= 5; $i++) {
            $this->headingCount[$i] = 0;
        }


        // build the actual number
        $headingNumber = '';
        for ($i=$this->startlevel;$i<=5;$i++) {
            // build the number of the heading
            $headingNumber .= ($this->headingCount[$i]!=0) ? $this->headingCount[$i].'.' : '';
        }

        // delete the tailing dot if wished (default)
        $headingNumber = ($this->tailingdot) ? $headingNumber : substr($headingNumber,0,-1);

        // insert the number...
        $match = preg_replace('/(={2,}\s?)\-/', '${1}'.$headingNumber, $match);

        // ... and return to original behavior
        $handler->header($match, $state, $pos);

        return true;
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        //do nothing (already done by original render-method)
    }
}
