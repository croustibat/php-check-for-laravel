# PHP Check for Laravel

> Interactive & CI-friendly tool to check and update outdated Composer dependencies

[![Latest Version on Packagist](https://img.shields.io/packagist/v/croustibat/php-check-for-laravel.svg?style=flat-square)](https://packagist.org/packages/croustibat/php-check-for-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/croustibat/php-check-for-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/croustibat/php-check-for-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/croustibat/php-check-for-laravel.svg?style=flat-square)](https://packagist.org/packages/croustibat/php-check-for-laravel)

A Laravel package that provides both an **interactive CLI** for updating dependencies and a **CI-ready mode** for automated pipelines.

![Interactive Mode Demo](https://github.com/croustibat/php-check-for-laravel/assets/1169456/9b97579e-6829-4729-8611-3428aae5c60d)

## Features

- **Interactive Mode**: Beautiful CLI with Laravel Prompts for manual updates
- **CI Mode**: Non-interactive output with configurable exit codes
- **Multiple Formats**: Table, JSON, or Markdown output
- **Security Checks**: Integrates with `composer audit`
- **Flexible Filters**: Filter by major, minor, or patch updates
- **Ignore List**: Exclude specific packages from checks
- **Configurable**: All options can be set via config file

## Installation

```bash
composer require croustibat/php-check-for-laravel
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="php-check-for-laravel-config"
```

## Usage

### Interactive Mode (default)

```bash
php artisan package:check
```

This opens an interactive prompt where you can select which packages to update.

### CI Mode

For use in CI/CD pipelines:

```bash
php artisan package:check --ci
```

#### Exit Codes

| Code | Meaning |
|------|---------|
| 0 | All packages up to date (or check passed) |
| 1 | Outdated packages found (with `--fail-on-*` options) |
| 2 | Error (e.g., JSON parse failure) |

## Options

| Option | Description |
|--------|-------------|
| `--ci` | Run in non-interactive mode |
| `--dev` | Include dev dependencies |
| `--all` | Check all dependencies (not just direct) |
| `--major-only` | Only show major updates |
| `--minor-only` | Only show minor updates |
| `--patch-only` | Only show patch updates |
| `--format=<format>` | Output format: `table`, `json`, or `markdown` |
| `--security` | Also check for security vulnerabilities |
| `--fail-on-outdated` | Exit with code 1 if any packages are outdated |
| `--fail-on-major` | Exit with code 1 if major updates exist |

## CI/CD Examples

### GitHub Actions

```yaml
name: Dependency Check

on:
  schedule:
    - cron: '0 9 * * 1'  # Every Monday at 9am
  workflow_dispatch:

jobs:
  check-dependencies:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install --no-interaction
      
      - name: Check for outdated packages
        run: php artisan package:check --ci --format=markdown >> $GITHUB_STEP_SUMMARY
      
      - name: Fail on major updates
        run: php artisan package:check --ci --fail-on-major
```

### GitLab CI

```yaml
dependency-check:
  stage: test
  script:
    - composer install --no-interaction
    - php artisan package:check --ci --fail-on-major --security
  only:
    - schedules
```

### JSON Output for Custom Processing

```bash
php artisan package:check --ci --format=json
```

Output:
```json
{
  "outdated": [
    {
      "name": "laravel/framework",
      "current": "10.0.0",
      "latest": "11.0.0",
      "semver": "major"
    }
  ],
  "summary": {
    "total": 1,
    "major": 1,
    "minor": 0,
    "patch": 0
  }
}
```

## Configuration

```php
// config/php-check-for-laravel.php

return [
    'include_dev' => false,
    'direct_only' => true,
    'check_security' => false,

    'ci' => [
        'format' => 'table',
        'fail_on_outdated' => false,
        'fail_on_major' => false,
    ],

    // Packages to exclude from checks
    'ignore' => [
        // 'vendor/package-name',
    ],
];
```

## Programmatic Usage

You can also use the package programmatically:

```php
use Croustibat\PhpCheckForLaravel\PhpCheckForLaravel;

$checker = app(PhpCheckForLaravel::class);

// Get all outdated packages
$outdated = $checker->getOutdatedPackages();

// Check if there are any outdated packages
if ($checker->hasOutdatedPackages()) {
    // ...
}

// Check for major updates specifically
if ($checker->hasMajorUpdates()) {
    // ...
}

// Check for security vulnerabilities
if ($checker->hasSecurityIssues()) {
    // ...
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Croustibat](https://github.com/croustibat)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
