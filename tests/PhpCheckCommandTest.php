<?php

use Croustibat\PhpCheckForLaravel\Commands\PhpCheckCommand;
use Illuminate\Support\Facades\Process;

it('shows success message when all packages are up to date', function () {
    Process::fake([
        '*' => Process::result(output: json_encode(['installed' => []])),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true])
        ->assertSuccessful();
});

it('detects outdated packages in ci mode', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => 'A test package',
                    ],
                ],
            ])
        ),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true])
        ->assertSuccessful();
});

it('outputs json format when requested', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => 'A test package',
                    ],
                ],
            ])
        ),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true, '--format' => 'json'])
        ->assertSuccessful();
});

it('outputs markdown format when requested', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => 'A test package',
                    ],
                ],
            ])
        ),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true, '--format' => 'markdown'])
        ->assertSuccessful();
});

it('exits with code 1 when fail-on-outdated is set and packages are outdated', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/package',
                        'version' => '1.0.0',
                        'latest' => '1.0.1',
                        'latest-status' => 'semver-safe-update',
                        'description' => 'A test package',
                    ],
                ],
            ])
        ),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true, '--fail-on-outdated' => true])
        ->assertExitCode(1);
});

it('exits with code 1 when fail-on-major is set and major updates exist', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => 'A test package',
                    ],
                ],
            ])
        ),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true, '--fail-on-major' => true])
        ->assertExitCode(1);
});

it('exits with code 0 when fail-on-major is set but only minor updates exist', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/package',
                        'version' => '1.0.0',
                        'latest' => '1.1.0',
                        'latest-status' => 'semver-safe-update',
                        'description' => 'A test package',
                    ],
                ],
            ])
        ),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true, '--fail-on-major' => true])
        ->assertSuccessful();
});

it('filters major updates when major-only option is set', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/major-package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => 'Major update',
                    ],
                    [
                        'name' => 'vendor/minor-package',
                        'version' => '1.0.0',
                        'latest' => '1.1.0',
                        'latest-status' => 'semver-safe-update',
                        'description' => 'Minor update',
                    ],
                ],
            ])
        ),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true, '--major-only' => true])
        ->assertSuccessful();
});

it('includes dev dependencies when dev option is set', function () {
    Process::fake([
        '*' => Process::result(output: json_encode(['installed' => []])),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true, '--dev' => true]);

    Process::assertRan(fn ($process) => str_contains($process->command, 'composer outdated')
        && ! str_contains($process->command, '--no-dev'));
});

it('checks all dependencies when all option is set', function () {
    Process::fake([
        '*' => Process::result(output: json_encode(['installed' => []])),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true, '--all' => true]);

    Process::assertRan(fn ($process) => str_contains($process->command, 'composer outdated')
        && ! str_contains($process->command, '--direct'));
});

it('handles json parse errors gracefully', function () {
    Process::fake([
        '*' => Process::result(output: 'invalid json'),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true])
        ->assertExitCode(2);
});

it('respects ignore list from config', function () {
    config(['php-check-for-laravel.ignore' => ['vendor/ignored-package']]);

    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/ignored-package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => 'Should be ignored',
                    ],
                    [
                        'name' => 'vendor/visible-package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => 'Should be visible',
                    ],
                ],
            ])
        ),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true])
        ->assertSuccessful();
});

it('shows all up to date when only ignored packages are outdated', function () {
    config(['php-check-for-laravel.ignore' => ['vendor/ignored-package']]);

    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/ignored-package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => 'Should be ignored',
                    ],
                ],
            ])
        ),
    ]);

    $this->artisan(PhpCheckCommand::class, ['--ci' => true])
        ->assertSuccessful();
});
