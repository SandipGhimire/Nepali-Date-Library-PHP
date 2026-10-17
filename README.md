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

## 🤝 Contributing

Contributions are welcome! Please read our [**Contributing Guide**](./CONTRIBUTING.md) or visit our [**Online Contribution Guide**](https://nepali-pkg.date/docs/contribution).

---

## 📄 License

MIT © [Sandip Ghimire](https://github.com/SandipGhimire)  
Made with ❤️ by [Sandip Ghimire](https://github.com/SandipGhimire).
