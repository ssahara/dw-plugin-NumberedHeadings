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
        $this->pattern[5] = '^[ \t]*={2,} ?-(?: ?#[0-9]+)? [^\n]*={2,}[ \t]*(?=\n)';
        $this->pattern[5] = '^[ \t]*={2,} ?-[ #"-][^\n]*={2,}[ \t]*(?=\n)';
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
            // Note: StartLevel may become 0 (auto-detect?) in the page
            $this->StartLevel = (int) substr($match, -3, 1);
            return $data = false;
        }

        // obtain the level of the heading
        $level = 7 - min(strspn($match, '='), 6);

        if (!$this->StartLevel) {
            $this->StartLevel = $this->getConf('startlevel') ?: $level;
        }
        $tier = $level - $this->StartLevel +1;

        $text = trim(trim($match), '='); // drop heading markup
        $text = ltrim($text);
        $dash = strspn($text, '-');      // count dash marker to check '-' or '--'
        $text = substr($text, $dash);

        // separate param and title
        switch ($text[0]) {
            case ' ':
                [$number, $title] = ['', trim($text)];
                break;
            case '#':
                [$number, $title] = explode(' ', substr($text, 1), 2);
                $number = $this->is_digits($number) ? $number +0 : 0;
                $title = trim($title);
                break;
            case '"':
                if (($i = strpos($text, '"', 1)) !== false) {
                    $number = substr($text, 1, $i-1);
                    $title = trim(substr($text, $i+1));
                } else {
                    [$number, $title] = explode(' ', substr($text, 1), 2);
                    $title = trim($title);
                }
                break;
            case '[': // numbering format : eg. == --["[%s]"] ==
                [$number, $title] = [null, null];
                $format = rtrim($text);  // should be JSON array string
                break;
        }

        // extra check of title
        if (empty($number) && $title[0] === '#') {
            $part = explode(' ', substr($title, 1), 2);
            if ($this->is_digits($part[0])) {
                $number = $part[0] +0;
                $title = trim($part[1]);
            }
        }

        // set numbering format of current tier (and sub-tiers)
        if (isset($format)) {
            $this->setTierFormat($format, $tier);
            return $data = false;
        }

        // set the internal heading counter
        $this->setHeadingCounter($level, $number);

        if ($dash > 1) {  // eg. == -- ==
            // do not call header() instruction when marked with '--'
            if (empty($number) && $tier == 1) {
                // reset the first tier level, which should be decided in next match
                $this->StartLevel = null;
                $this->setHeadingCounter();   // init counter
            }
            return $data = false;
        }

        // build tiered numbers for hierarchical headings
        $tieredNumbers = $this->getTieredNumbers($level);
        if ($tieredNumbers) {
            // append figure space after tiered number to distinguish title
            $tieredNumbers .= ' '; // U+2007 figure space
        }

        // revise the match
        $markup = str_repeat('=', 7 - $level);
        $match = $markup.$tieredNumbers.$title.$markup;

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

    /**
     * Set or initialise the numbering format of each tier
     */
    protected function setTierFormat($format=null, $tier=null)
    {
        // setTierFormat($this->getConf('format'));
        // setTierFormat('["Chapter %d."]', 1);

        $format = $format ?? $this->getConf('format');  // JSON array string
        $TierFormat = json_decode($format, true) ?? [];

        if ($tier == null) {
            // initialise numbering format (tier 1 to 5) using config parameter
            // re-index array from 1, instead of 0
            array_unshift($TierFormat, '');
            unset($TierFormat[0]);
            $this->TierFormat = $TierFormat;
        } else {
            // set numbering format of the specified tier and sub-tires
            foreach ($TierFormat as $k => $value) {
                $this->TierFormat[$tier + $k] = $value;
            }
        }
        return;
    }

    /**
     * Set or initialise the internal heading counter
     */
    protected function setHeadingCounter($level=null, $number='')
    {
        if (isset($level)) {
            // prepare the internal heading counter
            if (!$this->HeadingCount) {
                $this->initHeadingCounter();
            }
            $this->HeadingCount[$level] = $number ?: ++$this->HeadingCount[$level];
            // reset the number of the subheadings
            for ($i = $level +1; $i <= 5; $i++) {
                $this->HeadingCount[$i] = 0;
            }
        } else {
            $this->initHeadingCounter();
        }
        return;
    }

    /**
     * Build tiered numbers
     */
    protected function getTieredNumbers($level, $offset=null)
    {
        if (!$this->TierFormat) {
            $this->setTierFormat($this->getConf('format'));
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

    protected function is_digits($v) {
        return ($v && strlen($v) === strspn($v,'0123456789'));
    }

}
