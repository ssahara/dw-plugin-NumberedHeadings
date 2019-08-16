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
 *     startlevel: heading level corresponding to the 1st tier (default = 2)
 *     format    : numbering format (used in vsprintf) of each tier, JSON array string
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

        // obtain the level of the heading
        $level = 7 - min(strspn($match, '='), 6);

        // obtain number of the heading if defined
        $title = trim($match, '= ');  // drop heading markup
        $title = ltrim($title, '- '); // not drop tailing -
        if ($title[0] === '#') {
            $title = substr($title, 1); // drop #
            $i = strspn($title, '0123456789');
            $number = substr($title, 0, $i) + 0;
            $title  = ltrim(substr($title, $i));
        }

        // set the internal heading counter
        $this->setHeadingCounter($level, $number);

        // build tiered numbers for hierarchical headings
        $tieredNumbers = $this->getTieredNumbers($level);
        if ($tieredNumbers) {
            // append figure space after tiered number to distinguish title
            $tieredNumbers .= ' '; // U+2007 figure space
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
    protected $TierFormat   = [];   // numbering format of each tier
    protected $HeadingCount = [];   // heading counter

    protected function initHeadingCounter()
    {
        $this->HeadingCount = [ 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 ];
    }

    protected function initTierFormat($format=null)
    {
        $format = $format ?? $this->getConf('format');  // JSON array string
        $this->TierFormat = json_decode($format, true) ?? [];
        // re-index array from 1, instead of 0
        array_unshift($this->TierFormat, '');
        unset($this->TierFormat[0]);
    }

    /**
     * Set or initialise the internal heading counter
     */
    protected function setHeadingCounter($level=null, $number=null)
    {
        if (isset($level)) {
            // prepare the internal heading counter
            if (!$this->HeadingCount) {
                $this->initHeadingCounter();
            }
            $this->HeadingCount[$level] = $number ?? ++$this->HeadingCount[$level];
            // reset the number of the subheadings
            for ($i = $level +1; $i <= 5; $i++) {
                $this->HeadingCount[$i] = 0;
            }
        } else {
            $this->initHeadingCounter();
        }
    }

    /**
     * Build tiered numbers
     */
    protected function getTieredNumbers($level, $offset=null)
    {
        if (!$this->TierFormat) {
            $this->initTierFormat();
        }

        $offset = $offset ?? max(0, $this->StartLevel -1);

        if (isset($level) && $offset < $level) {
            $tier = $level - $offset;
            $numbers = array_slice($this->HeadingCount, $offset, $tier);
            if (isset($this->TierFormat[$tier])) {
                $tieredNumbers = vsprintf($this->TierFormat[$tier], $numbers);
            } else {
                $tieredNumbers = implode('.', $numbers);
            }
            if ($tier > 1 && $this->getConf('tailingdot')) {
                $tieredNumbers .= '.';
            }
        } else {
            $tieredNumbers = '';
        }
        return $tieredNumbers;
    }

}
