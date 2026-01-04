<?php

namespace Croustibat\ComposerCheck;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class CheckCommand extends Command
{
    private const EXIT_SUCCESS = 0;

    private const EXIT_OUTDATED = 1;

    private const EXIT_ERROR = 2;

    private bool $ciMode = false;

    private OutputInterface $output;

    private array $config = [];

    protected function configure(): void
    {
        $this
            ->setName('check')
            ->setDescription('Interactive check for outdated composer packages')
            ->addOption('dev', 'd', InputOption::VALUE_NONE, 'Include dev dependencies')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Check all dependencies (not just direct)')
            ->addOption('major-only', null, InputOption::VALUE_NONE, 'Only show major updates')
            ->addOption('minor-only', null, InputOption::VALUE_NONE, 'Only show minor updates')
            ->addOption('patch-only', null, InputOption::VALUE_NONE, 'Only show patch updates')
            ->addOption('ci', null, InputOption::VALUE_NONE, 'Run in CI mode (non-interactive, output only)')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format for CI mode (table, json, markdown)', 'table')
            ->addOption('fail-on-outdated', null, InputOption::VALUE_NONE, 'Exit with code 1 if any packages are outdated')
            ->addOption('fail-on-major', null, InputOption::VALUE_NONE, 'Exit with code 1 if any major updates are available')
            ->addOption('security', 's', InputOption::VALUE_NONE, 'Also check for security vulnerabilities (composer audit)')
            ->addOption('ignore', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Packages to ignore', [])
            ->addOption('working-dir', 'w', InputOption::VALUE_REQUIRED, 'Working directory for composer commands');
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->ciMode = $input->getOption('ci') || ! $input->isInteractive();

        $includeDev = $input->getOption('dev') ?: ($this->config['include_dev'] ?? false);
        $directOnly = $input->getOption('all') ? false : ($this->config['direct_only'] ?? true);
        $workingDir = $input->getOption('working-dir');

        $cmd = $this->buildComposerCommand($includeDev, $directOnly, $workingDir);
        $outdated = $this->checkOutdatedPackages($cmd, $workingDir);

        if ($outdated === null) {
            return self::EXIT_ERROR;
        }

        $outdated = $this->applyFilters($outdated, $input);
        $outdated = $this->applyIgnoreList($outdated, $input);

        if ($outdated->isEmpty()) {
            $this->outputInfo('✅ All packages are up to date!');

            return self::EXIT_SUCCESS;
        }

        $checkSecurity = $input->getOption('security') ?: ($this->config['check_security'] ?? false);
        $vulnerabilities = $checkSecurity ? $this->checkSecurityVulnerabilities($workingDir) : [];

        if ($this->ciMode) {
            return $this->handleCiMode($outdated, $vulnerabilities, $input);
        }

        return $this->handleInteractiveMode($outdated, $workingDir);
    }

    private function buildComposerCommand(bool $includeDev, bool $directOnly, ?string $workingDir): string
    {
        $cmd = 'composer outdated --format=json';

        if (! $includeDev) {
            $cmd .= ' --no-dev';
        }

        if ($directOnly) {
            $cmd .= ' --direct';
        }

        if ($workingDir) {
            $cmd .= ' --working-dir='.escapeshellarg($workingDir);
        }

        return $cmd;
    }

    private function checkOutdatedPackages(string $cmd, ?string $workingDir): ?Collection
    {
        try {
            $output = $this->runWithSpinner(
                fn () => $this->runCmd($cmd, $workingDir),
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

    private function applyFilters(Collection $packages, InputInterface $input): Collection
    {
        if ($input->getOption('major-only')) {
            return $packages->filter(fn ($p) => $p['semver'] === 'major');
        }

        if ($input->getOption('minor-only')) {
            return $packages->filter(fn ($p) => $p['semver'] === 'minor');
        }

        if ($input->getOption('patch-only')) {
            return $packages->filter(fn ($p) => $p['semver'] === 'patch');
        }

        return $packages;
    }

    private function applyIgnoreList(Collection $packages, InputInterface $input): Collection
    {
        $ignoreList = $input->getOption('ignore') ?: ($this->config['ignore'] ?? []);

        if (empty($ignoreList)) {
            return $packages;
        }

        return $packages->reject(fn ($p) => in_array($p['name'], $ignoreList));
    }

    private function checkSecurityVulnerabilities(?string $workingDir): array
    {
        try {
            $cmd = 'composer audit --format=json';
            if ($workingDir) {
                $cmd .= ' --working-dir='.escapeshellarg($workingDir);
            }

            $output = $this->runWithSpinner(
                fn () => $this->runCmd($cmd, $workingDir),
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

    private function handleCiMode(Collection $outdated, array $vulnerabilities, InputInterface $input): int
    {
        $format = $input->getOption('format') !== 'table'
            ? $input->getOption('format')
            : ($this->config['ci']['format'] ?? 'table');

        match ($format) {
            'json' => $this->outputJson($outdated, $vulnerabilities),
            'markdown' => $this->outputMarkdown($outdated, $vulnerabilities),
            default => $this->outputTable($outdated, $vulnerabilities),
        };

        $failOnMajor = $input->getOption('fail-on-major') ?: ($this->config['ci']['fail_on_major'] ?? false);
        $failOnOutdated = $input->getOption('fail-on-outdated') ?: ($this->config['ci']['fail_on_outdated'] ?? false);

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

        $this->output->writeln(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function outputMarkdown(Collection $outdated, array $vulnerabilities): void
    {
        $this->output->writeln('# Composer Dependencies Report');
        $this->output->writeln('');
        $this->output->writeln('## Outdated Packages');
        $this->output->writeln('');
        $this->output->writeln('| Package | Current | Latest | Type |');
        $this->output->writeln('|---------|---------|--------|------|');

        foreach ($outdated as $package) {
            $type = strtoupper($package['semver']);
            $this->output->writeln("| {$package['name']} | {$package['current']} | {$package['latest']} | {$type} |");
        }

        $this->output->writeln('');
        $this->output->writeln('## Summary');
        $this->output->writeln('');
        $this->output->writeln('- **Total outdated**: '.$outdated->count());
        $this->output->writeln('- **Major updates**: '.$outdated->filter(fn ($p) => $p['semver'] === 'major')->count());
        $this->output->writeln('- **Minor updates**: '.$outdated->filter(fn ($p) => $p['semver'] === 'minor')->count());
        $this->output->writeln('- **Patch updates**: '.$outdated->filter(fn ($p) => $p['semver'] === 'patch')->count());

        if (! empty($vulnerabilities)) {
            $this->output->writeln('');
            $this->output->writeln('## ⚠️ Security Vulnerabilities');
            $this->output->writeln('');
            $this->output->writeln('Found '.count($vulnerabilities).' security advisories. Run `composer audit` for details.');
        }
    }

    private function outputTable(Collection $outdated, array $vulnerabilities): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Package', 'Current', 'Latest', 'Type']);
        $table->setRows($outdated->map(fn ($p) => [
            $p['name'],
            $p['current'],
            $p['latest'],
            strtoupper($p['semver']),
        ])->toArray());
        $table->render();

        $this->output->writeln('');
        $this->output->writeln('📊 Summary: '.$outdated->count().' outdated packages');
        $this->output->writeln('   Major: '.$outdated->filter(fn ($p) => $p['semver'] === 'major')->count());
        $this->output->writeln('   Minor: '.$outdated->filter(fn ($p) => $p['semver'] === 'minor')->count());
        $this->output->writeln('   Patch: '.$outdated->filter(fn ($p) => $p['semver'] === 'patch')->count());

        if (! empty($vulnerabilities)) {
            $this->output->writeln('');
            $this->output->writeln('<comment>⚠️  Found '.count($vulnerabilities).' security advisories!</comment>');
        }
    }

    private function handleInteractiveMode(Collection $outdated, ?string $workingDir): int
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
        if ($workingDir) {
            $cmd .= ' --working-dir='.escapeshellarg($workingDir);
        }

        info("Running: $cmd");

        $this->runWithSpinner(
            fn () => $this->runCmd($cmd, $workingDir),
            '📦 Updating packages...'
        );

        info('✅ Done!');

        return self::EXIT_SUCCESS;
    }

    private function cleanVersion(string $version): string
    {
        return ltrim($version, 'v');
    }

    private function runCmd(string $cmd, ?string $workingDir = null): string
    {
        $process = Process::fromShellCommandline($cmd, $workingDir);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
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
            $this->output->writeln("<info>$message</info>");
        } else {
            info($message);
        }
    }

    private function outputWarning(string $message): void
    {
        if ($this->ciMode) {
            $this->output->writeln("<comment>$message</comment>");
        } else {
            warning($message);
        }
    }
}
