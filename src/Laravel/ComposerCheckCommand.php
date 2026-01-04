<?php

declare(strict_types=1);

namespace Croustibat\ComposerCheck\Laravel;

use Croustibat\ComposerCheck\CheckCommand;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Laravel Artisan wrapper for the standalone CheckCommand.
 */
class ComposerCheckCommand extends Command
{
    protected $signature = 'composer:check
        {--dev : Include dev dependencies}
        {--all : Check all dependencies (not just direct)}
        {--major-only : Only show major updates}
        {--minor-only : Only show minor updates}
        {--patch-only : Only show patch updates}
        {--ci : Run in CI mode (non-interactive, output only)}
        {--format=table : Output format for CI mode (table, json, markdown)}
        {--fail-on-outdated : Exit with code 1 if any packages are outdated}
        {--fail-on-major : Exit with code 1 if any major updates are available}
        {--security : Also check for security vulnerabilities (composer audit)}
        {--ignore=* : Packages to ignore}
        {--working-dir= : Working directory for composer commands}';

    protected $description = 'Interactive check for outdated composer packages';

    public function handle(): int
    {
        $checkCommand = new CheckCommand;

        if (function_exists('config')) {
            $checkCommand->setConfig(config('composer-check', []));
        }

        $input = new ArrayInput($this->buildInputArray(), $checkCommand->getDefinition());
        $input->setInteractive(! $this->option('ci') && ! $this->option('no-interaction'));

        return $checkCommand->run($input, $this->output->getOutput());
    }

    private function buildInputArray(): array
    {
        $input = [];

        if ($this->option('dev')) {
            $input['--dev'] = true;
        }

        if ($this->option('all')) {
            $input['--all'] = true;
        }

        if ($this->option('major-only')) {
            $input['--major-only'] = true;
        }

        if ($this->option('minor-only')) {
            $input['--minor-only'] = true;
        }

        if ($this->option('patch-only')) {
            $input['--patch-only'] = true;
        }

        if ($this->option('ci')) {
            $input['--ci'] = true;
        }

        if ($this->option('format') !== 'table') {
            $input['--format'] = $this->option('format');
        }

        if ($this->option('fail-on-outdated')) {
            $input['--fail-on-outdated'] = true;
        }

        if ($this->option('fail-on-major')) {
            $input['--fail-on-major'] = true;
        }

        if ($this->option('security')) {
            $input['--security'] = true;
        }

        $ignore = $this->option('ignore');
        if (! empty($ignore)) {
            $input['--ignore'] = $ignore;
        }

        if ($this->option('working-dir')) {
            $input['--working-dir'] = $this->option('working-dir');
        }

        return $input;
    }
}
