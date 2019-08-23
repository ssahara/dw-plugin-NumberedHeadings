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
if(!defined('DOKU_INC')) die();

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
        $this->pattern[5] = '^[ \t]*={2,} ?-(?: ?#[0-9]+)? [^\n]+={2,}[ \t]*(?=\n)';
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
            // Note: StartLevel may become 0 (auto-detect?) in the page
            $level = (int) substr($match, -3, 1);
            return $data = [$level, null, null];
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
        } else {
            $number = '';
        }

        return $data = [$level, $number, $title];
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
