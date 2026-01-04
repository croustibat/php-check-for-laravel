<?php

use Croustibat\PhpCheckForLaravel\PhpCheckForLaravel;
use Illuminate\Support\Facades\Process;

it('returns empty collection when all packages are up to date', function () {
    Process::fake([
        '*' => Process::result(output: json_encode(['installed' => []])),
    ]);

    $checker = new PhpCheckForLaravel;
    $outdated = $checker->getOutdatedPackages();

    expect($outdated)->toBeEmpty();
});

it('returns collection of outdated packages', function () {
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

    $checker = new PhpCheckForLaravel;
    $outdated = $checker->getOutdatedPackages();

    expect($outdated)->toHaveCount(1);
    expect($outdated->first()['name'])->toBe('vendor/package');
    expect($outdated->first()['semver'])->toBe('major');
});

it('correctly identifies semver types', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/major',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => '',
                    ],
                    [
                        'name' => 'vendor/minor',
                        'version' => '1.0.0',
                        'latest' => '1.1.0',
                        'latest-status' => 'semver-safe-update',
                        'description' => '',
                    ],
                    [
                        'name' => 'vendor/patch',
                        'version' => '1.0.0',
                        'latest' => '1.0.1',
                        'latest-status' => 'up-to-date',
                        'description' => '',
                    ],
                ],
            ])
        ),
    ]);

    $checker = new PhpCheckForLaravel;
    $outdated = $checker->getOutdatedPackages();

    expect($outdated->firstWhere('name', 'vendor/major')['semver'])->toBe('major');
    expect($outdated->firstWhere('name', 'vendor/minor')['semver'])->toBe('minor');
    expect($outdated->firstWhere('name', 'vendor/patch')['semver'])->toBe('patch');
});

it('returns true for hasOutdatedPackages when packages are outdated', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => '',
                    ],
                ],
            ])
        ),
    ]);

    $checker = new PhpCheckForLaravel;

    expect($checker->hasOutdatedPackages())->toBeTrue();
});

it('returns false for hasOutdatedPackages when no packages are outdated', function () {
    Process::fake([
        '*' => Process::result(output: json_encode(['installed' => []])),
    ]);

    $checker = new PhpCheckForLaravel;

    expect($checker->hasOutdatedPackages())->toBeFalse();
});

it('returns true for hasMajorUpdates when major updates exist', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/package',
                        'version' => '1.0.0',
                        'latest' => '2.0.0',
                        'latest-status' => 'update-possible',
                        'description' => '',
                    ],
                ],
            ])
        ),
    ]);

    $checker = new PhpCheckForLaravel;

    expect($checker->hasMajorUpdates())->toBeTrue();
});

it('returns false for hasMajorUpdates when only minor updates exist', function () {
    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'installed' => [
                    [
                        'name' => 'vendor/package',
                        'version' => '1.0.0',
                        'latest' => '1.1.0',
                        'latest-status' => 'semver-safe-update',
                        'description' => '',
                    ],
                ],
            ])
        ),
    ]);

    $checker = new PhpCheckForLaravel;

    expect($checker->hasMajorUpdates())->toBeFalse();
});

it('returns empty array for security advisories when none exist', function () {
    Process::fake([
        '*' => Process::result(output: json_encode(['advisories' => []])),
    ]);

    $checker = new PhpCheckForLaravel;
    $advisories = $checker->getSecurityAdvisories();

    expect($advisories)->toBeEmpty();
});

it('handles process failures gracefully', function () {
    Process::fake([
        '*' => Process::result(output: '', exitCode: 1),
    ]);

    $checker = new PhpCheckForLaravel;
    $outdated = $checker->getOutdatedPackages();

    expect($outdated)->toBeEmpty();
});

it('handles invalid json gracefully', function () {
    Process::fake([
        '*' => Process::result(output: 'not json'),
    ]);

    $checker = new PhpCheckForLaravel;
    $outdated = $checker->getOutdatedPackages();

    expect($outdated)->toBeEmpty();
});
