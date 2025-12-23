<?php

declare(strict_types=1);

use App\DTOs\Project;
use App\Livewire\ProjectCard;
use Carbon\Carbon;
use Livewire\Livewire;

test('project card renders with project data', function () {
    $project = new Project(
        id: 'test-123',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        gitBranch: 'main',
        lastActivity: Carbon::now()->subMinutes(30),
        status: 'idle',
        icon: '📁',
    );

    Livewire::test(ProjectCard::class, ['project' => $project])
        ->assertStatus(200)
        ->assertSee('myapp')
        ->assertSee('main')
        ->assertSee('Open');
});

test('project card shows blocked status correctly', function () {
    $project = new Project(
        id: 'test-123',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now()->subMinutes(30),
        status: 'blocked',
        icon: '📁',
    );

    Livewire::test(ProjectCard::class, ['project' => $project])
        ->assertSee('blocked')
        ->assertSee('Resume');
});

test('project card shows active status correctly', function () {
    $project = new Project(
        id: 'test-123',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now()->subMinutes(5),
        status: 'active',
        icon: '📁',
    );

    Livewire::test(ProjectCard::class, ['project' => $project])
        ->assertSee('active')
        ->assertSee('View');
});

test('project card dispatches project opened event', function () {
    $project = new Project(
        id: 'test-123',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now(),
        status: 'idle',
        icon: '📁',
    );

    Livewire::test(ProjectCard::class, ['project' => $project])
        ->call('open')
        ->assertDispatched('project-opened', projectId: 'test-123');
});

test('project card dispatches project selected event', function () {
    $project = new Project(
        id: 'test-123',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now(),
        status: 'idle',
        icon: '📁',
    );

    Livewire::test(ProjectCard::class, ['project' => $project])
        ->call('select')
        ->assertDispatched('project-selected', projectId: 'test-123');
});
