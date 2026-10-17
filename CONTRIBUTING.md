# Contributing to NepaliDate (PHP) 🇳🇵

Thank you for your interest in contributing to the **NepaliDate PHP Library**! We welcome bug fixes, documentation improvements, performance optimizations, and feature enhancements.

---

## 🛠️ Development Setup

### Prerequisites

- PHP `7.1` through `8.5`
- [Composer](https://getcomposer.org/)

### 1. Clone & Install Dependencies

```bash
git clone https://github.com/SandipGhimire/Nepali-Date-Library-PHP.git
cd Nepali-Date-Library-PHP

# Install dependencies including PHPUnit
composer install
```

### 2. Running Tests

We run the full PHPUnit test suite including round-trip verification across all supported years (1976 - 2100 BS):

```bash
composer test
```

### 3. Code Standards & Architecture Notes

- **PSR-12**: Ensure all PHP code adheres to modern PSR-12 standards.
- **Timezone Isolation**: All weekday, time-of-day getters, and start/end-of-day calculations must remain UTC-based to ensure deterministic results regardless of the host server's local timezone.
- **Static vs Instance**: Note that `NepaliDate::isValid()` is static to match the TypeScript library API; the instance validator is named `isValidInstance()`.

---

## 📚 Documentation

Official API references and interactive conversion tools:

- **Documentation**: [https://nepali-pkg.date/docs/php](https://nepali-pkg.date/docs/php)
- **Interactive Converter**: [https://nepali-pkg.date/converter](https://nepali-pkg.date/converter)

---

## 🚀 Submitting a Pull Request

1. Fork the repository and create a branch from `master` (`git checkout -b fix/your-issue`).
2. Add comprehensive tests for any new methods or bug fixes under `tests/`.
3. Verify that `composer test` runs cleanly across all test cases.
4. Commit with clear, descriptive commit messages.
5. Submit a Pull Request on GitHub.

---

## 📄 License

Contributions are licensed under the [MIT License](./LICENSE).  
Made with ❤️ by [Sandip Ghimire](https://github.com/SandipGhimire).

