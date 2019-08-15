<?php
/**
 * DokuWiki Plugin Numbered Headings: add tiered numbers for hierarchical headings
 *
 * Usage:   ===== - Heading Level 2 =====
 *          ==== - Heading Level 3 ====
 *          ==== - Heading Level 3 ====
 *          ...
 *
 * =>       1. Heading Level 2
 *              1.1 Heading Level 3
 *              1.2 Heading Level 3
 *          ...
 *
 * Config settings
 *     startlevel: upper headline level for hierarchical numbering (default = 2)
 *     tailingdot: add a tailing dot after sub-tier numbers (default = off)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

class syntax_plugin_numberedheadings extends DokuWiki_Syntax_Plugin
{
    function getType() {
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
        $match = trim($match);
        if ($match[0] !== '=') {
            $this->StartLevel = (int) substr($match, -3, 1);
            return $data = false;
        } elseif (!$this->StartLevel) {
            $this->StartLevel = $this->getConf('startlevel');
        }

        // prepare the internal heading counter
        if (!$this->HeadingCount) {
            $this->initHeadingCounter();
        }

        // obtain the level of the heading
        $level = 7 - min(strspn($match, '='), 6);

        // obtain the startnumber if defined
        $title = trim($match, '= ');  // drop heading markup
        $title = ltrim($title, '- '); // not drop tailing -
        if ($title[0] === '#') {
            $title = substr($title, 1); // drop #
            $i = strspn($title, '0123456789');
            $number = substr($title, 0, $i) + 0;
            $title  = ltrim(substr($title, $i));
            // set the number of the heading
            $this->HeadingCount[$level] = $number;
        } else {
            // increment the number of the heading
            $this->HeadingCount[$level]++;
        }

        // reset the number of the subheadings
        for ($i = $level +1; $i <= 5; $i++) {
            $this->HeadingCount[$i] = 0;
        }

        // build tiered numbers for hierarchical headings
        if ($this->StartLevel <= $level) {
            $numbers = array_slice($this->HeadingCount, $this->StartLevel -1, $level - $this->StartLevel +1);
            $tieredNumber = implode('.', $numbers);
            if (count($numbers) == 1) {
                // append always tailing dot for single tiered number
                $tieredNumber .= '.';
            } elseif ($this->getConf('tailingdot')) {
                // append tailing dot if wished
                $tieredNumber .= '.';
            }
            // append figure space after tiered number to distinguish title
            $tieredNumber .= ' '; // U+2007 figure space
        } else {
            $tieredNumber = '';
        }

        // revise the match
        $markup = str_repeat('=', 7 - $level);
        $match = $markup.$tieredNumber.$title.$markup;

        // ... and return to original behavior
        $handler->header($match, $state, $pos);

        return $data = false;
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        // nothing to do, should never be called
    }

    /*----------------------------------------------------------------*
     * Numbering feature
     *----------------------------------------------------------------*/

    protected $StartLevel;          // heading level corresponding to the 1st tier
    protected $HeadingCount = [];   // heading counter

    protected function initHeadingCounter() {
        $this->HeadingCount = [ 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 ];
    }

}
