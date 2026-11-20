# NepaliDate Library (PHP) 🇳🇵

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sghimire/nepali-date-library.svg?style=flat-square&color=E1804B)](https://packagist.org/packages/sghimire/nepali-date-library)
[![Total Downloads](https://img.shields.io/packagist/dt/sghimire/nepali-date-library.svg?style=flat-square&color=27845A)](https://packagist.org/packages/sghimire/nepali-date-library)
[![PHP Version](https://img.shields.io/packagist/php-v/sghimire/nepali-date-library.svg?style=flat-square&color=777BB4&logo=php&logoColor=white)](https://packagist.org/packages/sghimire/nepali-date-library)
[![Documentation](https://img.shields.io/badge/Docs-nepali--pkg.date-E1804B?style=flat-square&logo=google-chrome&logoColor=white)](https://nepali-pkg.date/docs/php)
[![Date Converter](https://img.shields.io/badge/Converter-BS%20%E2%86%94%20AD-24292E?style=flat-square)](https://nepali-pkg.date/converter)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](./LICENSE)

A modern, zero-dependency PHP library and **Nepali Date Converter** for working with **Nepali (Bikram Sambat / BS)** dates between **1976/01/01 and 2100/12/30 BS**.

---

### 📖 Full Documentation & Live Tools

All guides, complete API references, Devanagari format tokens, fiscal year helpers, and interactive playground are hosted on our official documentation portal:

👉 **[https://nepali-pkg.date/docs/php](https://nepali-pkg.date/docs/php)**  
🔄 **[Try the Live Date Converter](https://nepali-pkg.date/converter)**

---

## 📦 Installation

```bash
composer require sghimire/nepali-date-library
```

---

## 🚀 Quick Example

```php
use NepaliDateLibrary\NepaliDate;

$today = new NepaliDate();
echo $today->format("YYYY-MM-DD"); // "2081-07-01"
```

> **Note**: For complete API methods, standalone conversion (`ADtoBS` / `BStoAD`), date arithmetic, and fiscal year calculations, visit [**https://nepali-pkg.date/docs/php**](https://nepali-pkg.date/docs/php).

---

## 🛠️ Custom Calendar Data

The calendar tables are replaceable, so you can add new years, fix a month length or change labels without waiting for a release. Extend `NepaliDate`, override the static `constants()` hook and return an object built with `NepaliConstants::define()`:

```php
use NepaliDateLibrary\Helper\Constants;
use NepaliDateLibrary\Helper\NepaliConstants;
use NepaliDateLibrary\NepaliDate;

use function NepaliDateLibrary\ADtoBS;

class MyNepaliDate extends NepaliDate
{
    private static $calendar = null;

    protected static function constants(): NepaliConstants
    {
        if (self::$calendar === null) {
            self::$calendar = NepaliConstants::define([
                // anything you leave out falls back to the bundled data
                'dateMap' => array_merge(Constants::$NEPALI_DATE_MAP, [
                    ['year' => 2101, 'days' => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31]],
                ]),
            ]);
        }

        return self::$calendar; // return the same object every time
    }
}

(new MyNepaliDate(2101, 0, 1))->format('YYYY-MM-DD'); // "2101-01-01"
MyNepaliDate::isValid(2101, 0, 1); // true (static helpers follow the subclass too)
ADtoBS('2044-04-13', MyNepaliDate::class); // converters accept your class
```

Good to know:

- Accepted keys: `dateMap`, `epoch`, `utcOffsetMs`, `weekEn`, `weekShortEn`, `weekNp`, `weekShortNp`, `monthEn`, `monthShortEn`, `monthNp`, `monthShortNp`, `numberNp`. Unknown keys throw.
- Constants are validated once (consecutive years, 12 months of 1-32 days, name tables of the right size, ...) and snapshotted on first use. Later edits to your object are ignored; to change the calendar, return a different object.
- Time-dependent helpers such as `getCurrentFiscalYear()` use today's date, so they throw if today is outside your calendar. Pass an explicit year in that case.

## 🤝 Contributing

Contributions are welcome! Please read our [**Contributing Guide**](./CONTRIBUTING.md) or visit our [**Online Contribution Guide**](https://nepali-pkg.date/docs/contribution).

---

## 📄 License

MIT © [Sandip Ghimire](https://github.com/SandipGhimire)  
Made with ❤️ by [Sandip Ghimire](https://github.com/SandipGhimire).
