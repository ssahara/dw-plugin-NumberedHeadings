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
     *
     * usage: setTierFormat('["Chapter %d."]', 1);
     */
    public function setTierFormat($format=null, $tier=null)
    {
        if (empty($format)) {
            $format = $this->getConf('format');  // JSON array string
        }
        $TierFormat = json_decode($format, true);
        if ($TierFormat === null) $TierFormat = [];
        if ($tier === null) {
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
    public function setHeadingCounter($level=null, $number=null)
    {
        if (isset($level)) {
            // prepare the internal heading counter
            if (!$this->HeadingCount) {
                $this->initHeadingCounter();
            }
            if ($number === '') $number = null;
            $this->HeadingCount[$level] = isset($number)
                ? $number
                : ++$this->HeadingCount[$level];
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

        if (!isset($offset)) {
            $offset = max(0, $this->Tier1Level -1);
        }
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
