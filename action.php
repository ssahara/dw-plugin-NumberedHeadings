<?php
/**
 * DokuWiki Plugin Numbered Headings; action component
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */
if (!defined('DOKU_INC')) die();

class action_plugin_numberedheadings extends DokuWiki_Action_Plugin
{
    /**
     * Registers a callback function for a given event
     */
    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook(
            'PARSER_HANDLER_DONE', 'BEFORE', $this, '_numbering', []
        );

        if ($this->getConf('fancy')) {
            $controller->register_hook(
                'RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, '_tieredNumber'
            );
        }
    }

    /**
     * PARSER_HANDLER_DONE event handler
     * convert plugin instruction to header
     */
    function _numbering(Doku_Event $event)
    {
        // load helper object
        static $numbering;
        isset($numbering) || $numbering = $this->loadHelper($this->getPluginName());

        $instructions =& $event->data->calls;

        foreach ($instructions as $k => &$ins) {
            if (isset($dash)) {
                // initialise variables to be extracted from data array
                // that was compacted in handle() process
                unset($dash, $level, $number, $title, $format);
            }

            $call = ($ins[0] == 'plugin') ? 'plugin_'.$ins[1][0] : $ins[0];
            switch ($call) {
                case 'header':
                    [$text, $level, $pos] = $ins[1];
                    // chcek whether $text is JSON string?
                    $data = json_decode($text, true);
                    if (json_last_error() == JSON_ERROR_NONE) {
                        extract($data);  // retrieve number, title
                        $dash = 1;
                    } else {
                        continue 2;
                    }
                    break;
                case 'plugin_numberedheadings':
                    $data = $ins[1][1];
                    extract($data);
                    break;
                default:
                    continue 2;
            }

            if (!isset($dash)) { // not numbered headings
                // set tier1 only
                $numbering->setTier1($level);
              //unset($instructions[$k]);
                $dash = 0;
                continue;
            }

            // auto-detect the first tier (Tier1) level
            $tier1 = $numbering->getTier1();
            if (!$tier1) {
                $tier1 = $this->getConf('tier1') ?: $level;
                $numbering->setTier1($tier1);
            }
            $tier = $level - $tier1 +1;

            // non-visible numbered headings, marked with '--'
            if ($dash > 1) {
                // set the heading counter only if number seems meaningful
                if ($number !== '') {
                    $numbering->setHeadingCounter($level, $number);
                }

                if (isset($format)) {
                    // set numbering format of current tier (and subtiers) in the page
                    $numbering->setTierFormat($format, $tier);

                } elseif ($dash > 2 || $number === '' && $title === '' && $tier == 1) {
                    // reset numbering feature
                    // the first tier (Tier1) level should be decided in next match
                    $numbering->setTier1();
                    $numbering->setTierFormat();
                    $numbering->setHeadingCounter();
                }
              //unset($instructions[$k]);
                continue;
            }

            // set the heading counter
            $numbering->setHeadingCounter($level, $number);

            // build tiered numbers for hierarchical headings
            $tieredNumbers = $numbering->getTieredNumbers($level);
            if ($tieredNumbers) {
                // append figure space after tiered number to distinguish title
                $tieredNumbers .= ' '; // U+2007 figure space
            }
            $text = $tieredNumbers.$title;

            // rewrite header instruction
          //$ins[0] = 'header';
            $ins[1] = [$text, $level, $pos];
        }
        unset($ins);
        // reset numbering feature prior to process other pages
        $numbering->setTier1();
        $numbering->setTierFormat();
        $numbering->setHeadingCounter();
    }

    /**
     * RENDERER_CONTENT_POSTPROCESS
     * enclose tiered numbers of hierarchical headings in span tag
     */
    function _tieredNumber(Doku_Event $event)
    {
        if ($event->data[0] == 'xhtml') {
            $search = '#(<h\d.*?>)(.+?)(?: )(?=.*?</h\d>)#u'; // U+2007 figure space
            $replacement = '${1}<span class="plugin_numberedheadings">${2}</span>'."\t";
            $event->data[1] = preg_replace($search, $replacement, $event->data[1]);
        }
    }

}
