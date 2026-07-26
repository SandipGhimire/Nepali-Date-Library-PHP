# NepaliDate Library (PHP)

[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://github.com/SandipGhimire/Nepali-Date-Library-PHP/blob/master/LICENSE)
[![Docs](https://img.shields.io/badge/Docs-Official%20Wiki-blue)](https://nepalidate.sandip-ghimire.com.np)
[![GitHub](https://img.shields.io/badge/GitHub-Repository-black)](https://github.com/SandipGhimire/Nepali-Date-Library-PHP)

## Overview

The `NepaliDate` PHP library provides a proper way to work with Nepali (Bikram Sambat) dates.
It allows you to create, manipulate, format, and convert between Nepali Bikram Sambat dates (1976/01/01 - 2100/12/30) and Gregorian dates (1919-04-13 - 2044-04-12). It also supports fiscal years, quarters, and extensive date operations.

This library is a PHP port of the original TypeScript/JavaScript `NepaliDate` library, with an identical API surface wherever PHP allows it.

---

## 📚 Documentation

For detailed guides, API references, and examples, please visit the official Wiki:

👉 **[nepalidate.sandip-ghimire.com.np](https://nepalidate.sandip-ghimire.com.np)**

---

## Installation

```bash
composer require sghimire/nepali-date-library
```

Requires PHP 8.1 or newer.

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use NepaliDateLibrary\NepaliDate;
use function NepaliDateLibrary\ADtoBS;
use function NepaliDateLibrary\BStoAD;

$now = new NepaliDate();
echo $now->format('YYYY-MM-DD'), PHP_EOL;

// Parse a Nepali date string
$d = new NepaliDate('2080-01-15');
echo $d->format('DDDD, MMMM D, YYYY'), PHP_EOL; // Friday, Baisakh 15, 2080

// Convert between AD and BS
echo ADtoBS('2023-04-14'), PHP_EOL; // 2080-01-01
echo BStoAD('2080-01-01'), PHP_EOL; // 2023-04-14

// Arithmetic and comparisons
$next = $d->addMonths(1);
echo $d->isBefore($next) ? 'before' : 'after', PHP_EOL;

// Fiscal years and quarters
$quarter = NepaliDate::getQuarter(1, 2080);
echo $quarter['start']->format('YYYY-MM-DD'), ' - ', $quarter['end']->format('YYYY-MM-DD'), PHP_EOL;
```

## Format tokens

| Token | Meaning | Example |
| --- | --- | --- |
| `YYYY` / `YYY` / `YY` | English year (4/3/2 digits) | `2080` / `080` / `80` |
| `yyyy` | Nepali-digit year | `२०८०` |
| `M` / `MM` / `MMM` / `MMMM` | English month (1-indexed) | `1` / `01` / `Bai` / `Baisakh` |
| `m` / `mm` / `mmm` / `mmmm` | Nepali month | `१` / `०१` / `बै` / `बैशाख` |
| `D` / `DD` | Day of month | `5` / `05` |
| `DDD` / `DDDD` | English weekday | `Fri` / `Friday` |
| `d` / `dd` | Nepali-digit day of month | `५` / `०५` |
| `ddd` / `dddd` | Nepali weekday | `शुक्र` / `शुक्रबार` |

Wrap literal text in double quotes to prevent it from being interpreted as a token, e.g. `"M"MM` outputs `M01`.

## Notes on this port

- `NepaliDate::isValid()` is a static method (matches the JS API exactly); the instance check is named `isValidInstance()` since PHP doesn't allow a static and instance method to share one name.
- Weekday and time-of-day getters (`getDay()`, `getHours()`, `getMinutes()`, `getSeconds()`, `getMilliseconds()`) and `startOfDay()`/`endOfDay()` are computed in UTC, so results are independent of the host machine's timezone.

## Testing

```bash
composer install
composer test
```

The test suite includes a full-range verification that every supported day (BS 1976-2100) round-trips correctly between BS and AD.

## License

[MIT](https://github.com/SandipGhimire/Nepali-Date-Library-PHP/blob/master/LICENSE)
