<?php

namespace Croustibat\PhpCheckForLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class PhpCheckCommand extends Command
{
    public $signature = 'package:check
        {--dev : Include dev dependencies}
        {--all : Check all dependencies (not just direct)}
        {--major-only : Only show major updates}
        {--minor-only : Only show minor updates}
        {--patch-only : Only show patch updates}
        {--ci : Run in CI mode (non-interactive, output only)}
        {--format=table : Output format for CI mode (table, json, markdown)}
        {--fail-on-outdated : Exit with code 1 if any packages are outdated (CI mode)}
        {--fail-on-major : Exit with code 1 if any major updates are available (CI mode)}
        {--security : Also check for security vulnerabilities (composer audit)}';

    public $description = 'Interactive check for outdated composer packages';

    private const EXIT_SUCCESS = 0;

    private const EXIT_OUTDATED = 1;

    private const EXIT_ERROR = 2;

    private bool $ciMode = false;

    public function handle(): int
    {
        $includeDev = $this->option('dev') ?: config('php-check-for-laravel.include_dev', false);
        $directOnly = $this->option('all') ? false : config('php-check-for-laravel.direct_only', true);
        $this->ciMode = $this->option('ci') || ! $this->input->isInteractive();

        $cmd = $this->buildComposerCommand($includeDev, $directOnly);
        $outdated = $this->checkOutdatedPackages($cmd);

        if ($outdated === null) {
            return self::EXIT_ERROR;
        }

        $outdated = $this->applyFilters($outdated);
        $outdated = $this->applyIgnoreList($outdated);

        if ($outdated->isEmpty()) {
            $this->outputInfo('✅ All packages are up to date!');

            return self::EXIT_SUCCESS;
        }

        $checkSecurity = $this->option('security') ?: config('php-check-for-laravel.check_security', false);
        $vulnerabilities = $checkSecurity ? $this->checkSecurityVulnerabilities() : [];

        if ($this->ciMode) {
            return $this->handleCiMode($outdated, $vulnerabilities);
        }

        return $this->handleInteractiveMode($outdated);
    }

    private function buildComposerCommand(bool $includeDev, bool $directOnly): string
    {
        $cmd = 'composer outdated --format=json';

        if (! $includeDev) {
            $cmd .= ' --no-dev';
        }

        if ($directOnly) {
            $cmd .= ' --direct';
        }

        return $cmd;
    }

    private function checkOutdatedPackages(string $cmd): ?Collection
    {
        try {
            $output = $this->runWithSpinner(
                fn () => $this->runCmd($cmd),
                '🚀 Checking for outdated packages...'
            );

            $packagesList = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->outputWarning('❌ Failed to parse composer output: '.json_last_error_msg());

                return null;
            }

            if (! isset($packagesList['installed']) || empty($packagesList['installed'])) {
                return collect();
            }

            return collect($packagesList['installed'])->map(function ($package) {
                $semver = match ($package['latest-status'] ?? 'unknown') {
                    'update-possible' => 'major',
                    'semver-safe-update' => 'minor',
                    default => 'patch'
                };

                return [
                    'name' => $package['name'],
                    'current' => $package['version'],
                    'latest' => $package['latest'],
                    'semver' => $semver,
                    'description' => $package['description'] ?? '',
                ];
            });
        } catch (\Exception $e) {
            $this->outputWarning('❌ Error checking packages: '.$e->getMessage());

            return null;
        }
    }

    private function applyFilters(Collection $packages): Collection
    {
        if ($this->option('major-only')) {
            return $packages->filter(fn ($p) => $p['semver'] === 'major');
        }

        if ($this->option('minor-only')) {
            return $packages->filter(fn ($p) => $p['semver'] === 'minor');
        }

        if ($this->option('patch-only')) {
            return $packages->filter(fn ($p) => $p['semver'] === 'patch');
        }

        return $packages;
    }

    private function applyIgnoreList(Collection $packages): Collection
    {
        $ignoreList = config('php-check-for-laravel.ignore', []);

        if (empty($ignoreList)) {
            return $packages;
        }

        return $packages->reject(fn ($p) => in_array($p['name'], $ignoreList));
    }

    private function checkSecurityVulnerabilities(): array
    {
        try {
            $output = $this->runWithSpinner(
                fn () => $this->runCmd('composer audit --format=json'),
                '🔒 Checking for security vulnerabilities...'
            );

            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }

            return $result['advisories'] ?? [];
        } catch (\Exception $e) {
            $this->outputWarning('⚠️ Could not check security vulnerabilities: '.$e->getMessage());

            return [];
        }
    }

    private function handleCiMode(Collection $outdated, array $vulnerabilities): int
    {
        $format = $this->option('format') !== 'table'
            ? $this->option('format')
            : config('php-check-for-laravel.ci.format', 'table');

        match ($format) {
            'json' => $this->outputJson($outdated, $vulnerabilities),
            'markdown' => $this->outputMarkdown($outdated, $vulnerabilities),
            default => $this->outputTable($outdated, $vulnerabilities),
        };

        $failOnMajor = $this->option('fail-on-major') ?: config('php-check-for-laravel.ci.fail_on_major', false);
        $failOnOutdated = $this->option('fail-on-outdated') ?: config('php-check-for-laravel.ci.fail_on_outdated', false);

        if ($failOnMajor && $outdated->contains(fn ($p) => $p['semver'] === 'major')) {
            return self::EXIT_OUTDATED;
        }

        if ($failOnOutdated && $outdated->isNotEmpty()) {
            return self::EXIT_OUTDATED;
        }

        if (! empty($vulnerabilities)) {
            return self::EXIT_OUTDATED;
        }

        return self::EXIT_SUCCESS;
    }

    private function outputJson(Collection $outdated, array $vulnerabilities): void
    {
        $data = [
            'outdated' => $outdated->values()->toArray(),
            'summary' => [
                'total' => $outdated->count(),
                'major' => $outdated->filter(fn ($p) => $p['semver'] === 'major')->count(),
                'minor' => $outdated->filter(fn ($p) => $p['semver'] === 'minor')->count(),
                'patch' => $outdated->filter(fn ($p) => $p['semver'] === 'patch')->count(),
            ],
        ];

        if (! empty($vulnerabilities)) {
            $data['vulnerabilities'] = $vulnerabilities;
        }

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function outputMarkdown(Collection $outdated, array $vulnerabilities): void
    {
        $this->line('# Composer Dependencies Report');
        $this->line('');
        $this->line('## Outdated Packages');
        $this->line('');
        $this->line('| Package | Current | Latest | Type |');
        $this->line('|---------|---------|--------|------|');

        foreach ($outdated as $package) {
            $type = strtoupper($package['semver']);
            $this->line("| {$package['name']} | {$package['current']} | {$package['latest']} | {$type} |");
        }

        $this->line('');
        $this->line('## Summary');
        $this->line('');
        $this->line('- **Total outdated**: '.$outdated->count());
        $this->line('- **Major updates**: '.$outdated->filter(fn ($p) => $p['semver'] === 'major')->count());
        $this->line('- **Minor updates**: '.$outdated->filter(fn ($p) => $p['semver'] === 'minor')->count());
        $this->line('- **Patch updates**: '.$outdated->filter(fn ($p) => $p['semver'] === 'patch')->count());

        if (! empty($vulnerabilities)) {
            $this->line('');
            $this->line('## ⚠️ Security Vulnerabilities');
            $this->line('');
            $this->line('Found '.count($vulnerabilities).' security advisories. Run `composer audit` for details.');
        }
    }

    private function outputTable(Collection $outdated, array $vulnerabilities): void
    {
        $headers = ['Package', 'Current', 'Latest', 'Type'];
        $rows = $outdated->map(fn ($p) => [
            $p['name'],
            $p['current'],
            $p['latest'],
            strtoupper($p['semver']),
        ])->toArray();

        $this->table($headers, $rows);

        $this->newLine();
        $this->line('📊 Summary: '.$outdated->count().' outdated packages');
        $this->line('   Major: '.$outdated->filter(fn ($p) => $p['semver'] === 'major')->count());
        $this->line('   Minor: '.$outdated->filter(fn ($p) => $p['semver'] === 'minor')->count());
        $this->line('   Patch: '.$outdated->filter(fn ($p) => $p['semver'] === 'patch')->count());

        if (! empty($vulnerabilities)) {
            $this->newLine();
            $this->warn('⚠️  Found '.count($vulnerabilities).' security advisories!');
        }
    }

    private function handleInteractiveMode(Collection $outdated): int
    {
        $options = $outdated->mapWithKeys(function ($package) {
            $semver = strtoupper($package['semver']);
            $label = "{$package['name']} {$package['current']} → {$package['latest']} ({$semver})";

            return [$package['name'].':'.$this->cleanVersion($package['latest']) => $label];
        })->toArray();

        $selected = collect(multiselect(
            label: 'Which packages to update?',
            options: $options,
            scroll: 10,
            hint: 'Use space to select, enter to confirm'
        ));

        if ($selected->isEmpty()) {
            info('No packages selected.');

            return self::EXIT_SUCCESS;
        }

        info('You are going to update: '.$selected->implode(', '));
        $confirmed = confirm('Are you sure?');

        if (! $confirmed) {
            info('❌ Aborting... nothing has been updated');

            return self::EXIT_SUCCESS;
        }

        $cmd = 'composer require -W '.$selected->implode(' ');
        info("Running: $cmd");

        $this->runWithSpinner(
            fn () => $this->runCmd($cmd),
            '📦 Updating packages...'
        );

        info('✅ Done!');

        return self::EXIT_SUCCESS;
    }

    private function cleanVersion(string $version): string
    {
        return ltrim($version, 'v');
    }

    private function runCmd(string $cmd): string
    {
        $result = Process::run($cmd);

        if ($result->failed()) {
            throw new \RuntimeException($result->errorOutput());
        }

        return $result->output();
    }

    private function runWithSpinner(callable $callback, string $message): mixed
    {
        if ($this->ciMode) {
            return $callback();
        }

        return spin($callback, $message);
    }

    private function outputInfo(string $message): void
    {
        if ($this->ciMode) {
            $this->info($message);
        } else {
            info($message);
        }
    }

    private function outputWarning(string $message): void
    {
        if ($this->ciMode) {
            $this->warn($message);
        } else {
            warning($message);
        }
    }
}
