# DokuWiki Plugin Numbered Headings

Add tiered numbers for hierarchical headings to DokuWiki without changing 
the actual behavior of the standard headings.

## Configuration

| Parameter  | Description                                                   |
| ---------- | ------------------------------------------------------------- |
| startlevel | upper heading level for hierarchical numbering (default = 2)  |
| prefix     | prefix to first number for different levels, comma separated  |
| suffix     | suffix to last number for different levels, comma separated   |

## Usage

    ====== Lv1 Headline ======
    ===== - Lv2 Headline 1 =====
    ==== - Lv3 Headline 1 ====
    ==== - Lv3 Headline 2 ====
    ===== - Lv2 Headline 2 =====

    Lv1 Headline
    1 Lv2 Headline 1
    1.1 Lv3 Headline 1
    1.2 Lv3 Headline 2
    2 Lv2 Headline 2

