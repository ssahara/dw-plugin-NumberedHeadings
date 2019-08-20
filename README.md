# DokuWiki Plugin Numbered Headings

Add tiered numbers for hierarchical headings to DokuWiki without changing 
the actual behavior of the standard headings.

## Configuration

| Parameter  | Description                                                             |
| ---------- | ----------------------------------------------------------------------- |
| tier1      | heading level corresponding to the 1st tier (default = 0, auto-detect)  |
| format     | numbering format (used in vsprintf) of each tier, JSON array string     |
| tailingdot | add a tailing dot after sub-tier numbers (default = false)              |
| fancy      | styled heading numbers (default = false)                                |


## Usage

    ====== Lv1 Headline ======
    ===== - Lv2 Headline 1 =====
    ==== - Lv3 Headline 1 ====
    ==== - Lv3 Headline 2 ====
    ===== - Lv2 Headline 2 =====

    Lv1 Headline
    1. Lv2 Headline 1
    1.1 Lv3 Headline 1
    1.2 Lv3 Headline 2
    2. Lv2 Headline 2

