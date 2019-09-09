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
 *     tier1  : heading level corresponding to the 1st tier
 *     format : numbering format (used in vsprintf) of each tier, JSON array string
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within DokuWiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_numberedheadings extends DokuWiki_Syntax_Plugin
{
    function getType()
    {
        return 'substition';
    }

    /**
     * Connect pattern to lexer
     */
    protected $mode, $pattern;

    function preConnect()
    {
        // syntax mode, drop 'syntax_' from class name
        $this->mode = substr(get_class($this), 7);

        // syntax pattern
        $this->pattern[0] = '~~HEADLINE NUMBERING FIRST LEVEL = \d~~';
        $this->pattern[5] = '^[ \t]*={2,} ?-+(?:[#"][^\n]*)? [^\n]*={2,}[ \t]*(?=\n)';
    }

    function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern($this->pattern[0], $mode, $this->mode);
        $this->Lexer->addSpecialPattern($this->pattern[5], $mode, $this->mode);

        // backward compatibility, to be obsoleted in future ...
        $this->Lexer->addSpecialPattern(
                        '{{header>[1-5]}}', $mode, $this->mode);
        $this->Lexer->addSpecialPattern(
                        '{{startlevel>[1-5]}}', $mode, $this->mode);
    }

    function getSort()
    {
        return 45;
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {
        // obtain the first tier (Tier1) level from the page if defined
        $match = trim($match);
        if ($match[0] !== '=') {
            // Note: The Tier1 Level may become 0 (auto-detect) in the page
            $level = (int) substr($match, -3, 1);
            return $data = compact('level');
        }

        // obtain the level of the heading
        $level = 7 - min(strspn($match, '='), 6);

        // separate parameter and title
        // == -#n title  == ; "#" is a parameter indicates number
        // == - #n title == ; "#" is a placeholder of numbering label

        $text = trim(trim($match), '='); // drop heading markup
        $text = ltrim($text);
        $dash = strspn($text, '-');      // count dash marker to check '-' or '--'
        $text = substr($text, $dash);

        switch ($text[0]) {
            case ' ':
                list($number, $title) = array('', trim($text));
                if ($title[0] == '#') {
                    // extra check of title
                    // == - # title ==     ; "#" is NOT numbering label
                    // == - #12 title ==   ; "#" is numbering label with number
                    // == - #12.3 title == ; "#" is NOT numbering label
                    $part = explode(' ', substr($title, 1), 2);
                    if (ctype_digit($part[0])) {
                        $number = $part[0] +0;
                        $title  = trim($part[1]);
                    }
                }
                break;
            case '#': // numeric numbering, (integer) $number
                list($number, $title) = explode(' ', substr($text, 1), 2);
                $number = ctype_digit($number) ? $number +0 : '';
                $title  = trim($title);
                break;
            case '"': // alpha-numeric numbering, (string) $number
                $closed = strpos($text, '"', 1); // search closing "
                if ($closed !== false) {
                    $number = substr($text, 1, $closed -1);
                    $title  = trim(substr($text, $closed + 1));
                } else {
                    list($number, $title) = explode(' ', substr($text, 1), 2);
                    $title  = trim($title);
                }
                break;
        }

        // non-visible numbered headings, marked with '--'
        if ($dash > 1 && $title[0] == '[' && substr($title, -1) == ']') {
            $format = $title;
            unset($title);
        }

        $data = compact('dash', 'level', 'number', 'title', 'format');

        if ($dash == 1) {
            // do same as parser::handler->header()
            if ($handler->status['section']) {
                $handler->_addCall('section_close', [], $pos);
            }
            // plugin instruction to be rewrited later
            $plugin = substr(get_class($this), 14);
            $handler->addPluginCall($plugin, $data, $state, $pos, $match);

            $handler->_addCall('section_open', [$level], $pos);
            $this->status['section'] = true;
        } else {
            return $data;
        }
        return false;
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data)
    {
        // nothing to do, should never be called because plugin instructions
        // are converted to normal headers in PARSER_HANDLER_DONE event handler
    }
}
