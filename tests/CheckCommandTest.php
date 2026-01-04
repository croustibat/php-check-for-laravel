<?php

declare(strict_types=1);

use Croustibat\ComposerCheck\CheckCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function createCommandTester(): CommandTester
{
    $application = new Application;
    $application->add(new CheckCommand);

    return new CommandTester($application->find('check'));
}

it('shows success message when all packages are up to date', function () {
    $tester = createCommandTester();
    $tester->execute(['--ci' => true, '--working-dir' => __DIR__.'/fixtures/up-to-date']);
})->skip('Requires fixtures setup');

it('can be instantiated', function () {
    $command = new CheckCommand;

    expect($command->getName())->toBe('check');
    expect($command->getDescription())->toBe('Interactive check for outdated composer packages');
});

it('has all expected options', function () {
    $command = new CheckCommand;
    $definition = $command->getDefinition();

    expect($definition->hasOption('dev'))->toBeTrue();
    expect($definition->hasOption('all'))->toBeTrue();
    expect($definition->hasOption('major-only'))->toBeTrue();
    expect($definition->hasOption('minor-only'))->toBeTrue();
    expect($definition->hasOption('patch-only'))->toBeTrue();
    expect($definition->hasOption('ci'))->toBeTrue();
    expect($definition->hasOption('format'))->toBeTrue();
    expect($definition->hasOption('fail-on-outdated'))->toBeTrue();
    expect($definition->hasOption('fail-on-major'))->toBeTrue();
    expect($definition->hasOption('security'))->toBeTrue();
    expect($definition->hasOption('ignore'))->toBeTrue();
    expect($definition->hasOption('working-dir'))->toBeTrue();
});

it('accepts config array', function () {
    $command = new CheckCommand;

    $result = $command->setConfig([
        'include_dev' => true,
        'direct_only' => false,
        'ignore' => ['vendor/package'],
    ]);

    expect($result)->toBeInstanceOf(CheckCommand::class);
});

it('format option defaults to table', function () {
    $command = new CheckCommand;
    $definition = $command->getDefinition();

    expect($definition->getOption('format')->getDefault())->toBe('table');
});

it('ignore option accepts multiple values', function () {
    $command = new CheckCommand;
    $definition = $command->getDefinition();

    expect($definition->getOption('ignore')->isArray())->toBeTrue();
});
