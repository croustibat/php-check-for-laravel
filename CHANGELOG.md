# Changelog

1.0.1

- Fix Phpstan error
- Fix Styling (Pint)

1.0.0

- Initial release

## v1.2.0 - CI Mode & Security Integration - 2026-01-04

### 🚀 New Features

#### CI/CD Integration (Killer Feature!)

- **`--ci` flag**: Non-interactive mode with proper exit codes for pipeline integration
- **`--format` option**: Output as `table`, `json`, or `markdown`
- **Pipeline gates**: `--fail-on-outdated` and `--fail-on-major` to fail CI on outdated packages
- **Security checks**: `--security` flag integrates `composer audit`

#### Flexible Filtering

- `--dev`: Include dev dependencies
- `--all`: Check all dependencies (not just direct)
- `--major-only`, `--minor-only`, `--patch-only`: Filter by update type
- Configurable **ignore list** for packages you want to exclude

#### Programmatic API

- New `PhpCheckForLaravel` class for using the package in your code
- Methods: `getOutdatedPackages()`, `hasOutdatedPackages()`, `hasMajorUpdates()`, `hasSecurityIssues()`

### 🐛 Bug Fixes

- Fixed crash when no packages are outdated

### 📚 Documentation

- Complete README rewrite with CI/CD examples (GitHub Actions, GitLab CI)
- 25 unit tests added

### 📦 Installation

```bash
composer require croustibat/php-check-for-laravel

```
### 🔧 Quick Start

```bash
# Interactive mode
php artisan package:check

# CI mode with JSON output
php artisan package:check --ci --format=json

# Fail CI if major updates exist
php artisan package:check --ci --fail-on-major --security

```
## 1.1.0 - 2024-04-12

### What's Changed

* Bump aglipanci/laravel-pint-action from 2.3.0 to 2.3.1 by @dependabot in https://github.com/croustibat/php-check-for-laravel/pull/1
* Bump ramsey/composer-install from 2 to 3 by @dependabot in https://github.com/croustibat/php-check-for-laravel/pull/2
* Bump dependabot/fetch-metadata from 1.6.0 to 2.0.0 by @dependabot in https://github.com/croustibat/php-check-for-laravel/pull/3

### New Contributors

* @dependabot made their first contribution in https://github.com/croustibat/php-check-for-laravel/pull/1

**Full Changelog**: https://github.com/croustibat/php-check-for-laravel/compare/1.0.2...1.1.0

## 1.0.2 - 2023-12-06

**Full Changelog**: https://github.com/croustibat/php-check-for-laravel/compare/1.0.1...1.0.2

## 1.0.1 - 2023-11-13

**Full Changelog**: https://github.com/croustibat/php-check-for-laravel/compare/1.0.0...1.0.1
