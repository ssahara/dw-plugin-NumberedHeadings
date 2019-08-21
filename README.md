# DokuWiki Plugin Numbered Headings

Add tiered numbers for hierarchical headings to DokuWiki without changing 
the actual behavior of the standard headings.

## Configuration

| Parameter  | Description                                                          |
| ---------- | -------------------------------------------------------------------- |
| startlevel | heading level corresponding to the 1st tier (default = 2)            |
| format     | numbering format (used in vsprintf) of each tier, JSON array string  |
| fancy      | styled heading numbers (default = false)                             |

default numbering format: `["%d.", "%d.%d", "%d.%d.%d", "%d.%d.%d.%d", "%d.%d.%d.%d.%d"]`

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
       
numbering format (used in vsprintf) of each tier