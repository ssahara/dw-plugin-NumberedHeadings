<?php
/**
 * DokuWiki Plugin Numbered Headings: add tiered numbers for hierarchical headings
 *
 * Usage:   ====== - Heading Level 1======
 *          ===== - Heading Level 2 =====
 *          ===== - Heading Level 2 =====
 *                   ...
 *
 * =>       1. Heading Level 1
 *              1.1 Heading Level 2
 *              1.2 Heading Level 2
 *          ...
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

class syntax_plugin_numberedheadings extends DokuWiki_Syntax_Plugin {

    protected $headingCount = [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
    ];

    protected $startlevel, $tailingdot;

    function __construct() {
        // retrieve once config settings
        //   startlevel: upper headline level for hierarchical numbering (default = 2)
        //   tailingdot: add a tailing dot after sub-tier numbers (default = off)
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
        $this->pattern[0] = '~~HEADLINE NUMBERING FIRST LEVEL = \d~~';
        $this->pattern[5] = '^[ \t]*={2,} ?-(?: ?#[0-9]+)? [^\n]+={2,}[ \t]*(?=\n)';
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern[0], $mode, $this->mode);
        $this->Lexer->addSpecialPattern($this->pattern[5], $mode, $this->mode);

        // backward compatibility, to be obsoleted in future ...
        $this->Lexer->addSpecialPattern(
                        '{{header>[1-5]}}', $mode, $this->mode);
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
            return false;
        }

        // obtain the level of the heading
        $title = trim($match);
        $level = 7 - min(strspn($title, '='), 6);
        $markup = str_repeat('=', 7 - $level);

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

        // build tiered numbers for hierarchical headings
        $numbers = [];
        for ($i = $this->startlevel; $i <= $level; $i++) {
            $numbers[] = $this->headingCount[$i];
        }
        if ($numbers) {
            $tieredNumber = implode('.', $numbers);
            $prefix = array_pad(explode(',',$this->getConf('prefix')), 5, '');
            $suffix = array_pad(explode(',',$this->getConf('suffix')), 5, '');

            $n = count($numbers) -1;
            $tieredNumber = $prefix[$n].$tieredNumber.$suffix[$n];

            // append figure space after tiered number to distinguish title
            $tieredNumber .= ' '; // U+2007 figure space
        } else {
            $tieredNumber = '';
        }

        // revise the match
        $match = $markup.$tieredNumber.$title.$markup;

        // ... and return to original behavior
        $handler->header($match, $state, $pos);

        return false;
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        //do nothing (already done by original render-method)
    }
}
