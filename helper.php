<?php
/**
 * DokuWiki Plugin Numbered Headings: add tiered numbers for hierarchical headings
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * Config settings
 *     tier1  : heading level corresponding to the 1st tier
 *     format : numbering format (used in vsprintf) of each tier, JSON array string
 */
if (!defined('DOKU_INC')) die();

class helper_plugin_numberedheadings extends DokuWiki_Plugin
{
    protected $Tier1Level;   // (int)   heading level corresponding to the 1st tier
    protected $TierFormat;   // (array) numbering format of each tier
    protected $HeadingCount; // (array) heading counter

    protected function initHeadingCounter()
    {
        $this->HeadingCount = [ 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 ];
    }

    /**
     * Set the first tier level
     */
    public function setTier1($level=null)
    {
        $this->Tier1Level = $level;
        return;
    }

    /**
     * Get the first tier level
     */
    public function getTier1()
    {
        return $this->Tier1Level;
    }

    /**
     * Set or initialise the numbering format of each tier
     */
    public function setTierFormat($format=null)
    {
        // initialise numbering format (tier 1 to 5) using config parameter
        $format = $format ?? $this->getConf('format');  // JSON array string
        $TierFormat = json_decode($format, true) ?? [];
        // re-index array from 1, instead of 0
        array_unshift($TierFormat, '');
        unset($TierFormat[0]);
        $this->TierFormat = $TierFormat;
        return;
    }

    /**
     * Set or initialise the internal heading counter
     */
    public function setHeadingCounter($level=null, $number=null)
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
    public function getTieredNumbers($level, $offset=null)
    {
        if (!$this->TierFormat) {
            $this->setTierFormat($this->getConf('format'));
        }

        $offset = $offset ?? max(0, $this->Tier1Level -1);
        if (isset($level) && $offset < $level) {
            $tier = $level - $offset;
            $numbers = array_slice($this->HeadingCount, $offset, $tier);
            if (isset($this->TierFormat[$tier])) {
                $tieredNumbers = vsprintf($this->TierFormat[$tier], $numbers);
            } else {
                $tieredNumbers = implode('.', $numbers);
            }
        } else {
            $tieredNumbers = '';
        }
        return $tieredNumbers;
    }

}
