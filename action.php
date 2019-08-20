<?php
/**
 * DokuWiki Plugin Numbered Headings; action component
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class action_plugin_numberedheadings extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook(
            'PARSER_HANDLER_DONE', 'AFTER', $this, 'init_numbering', []
        );

        if ($this->getConf('fancy')) {
            $controller->register_hook(
                'RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, '_tieredNumber'
            );
        }
    }

    /**
     * PARSER_HANDLER_DONE event handler
     * 
     * initialise numbering properties
     */
    public function init_numbering(Doku_Event $event)
    {
        // load syntax component
        $numbering = plugin_load('syntax', $this->getPluginName());
        if ($numbering->getTier1() !== null) {
            $numbering->setTier1();
            $numbering->setTierFormat();
            $numbering->setHeadingCounter();
        }
    }

    /**
     * RENDERER_CONTENT_POSTPROCESS
     * enclose tiered numbers for hierarchical headings in span tag
     */
    function _tieredNumber(Doku_Event $event) {
        if ($event->data[0] == 'xhtml') {
            $search = '/(<h\d.*?>)([\d.]+)(?: )/u'; // U+2007 figure space
            $replacement = '${1}<span class="plugin_numberedheadings">${2}</span>'."\t";
            $event->data[1] = preg_replace($search, $replacement, $event->data[1]);
        }
    }

}
