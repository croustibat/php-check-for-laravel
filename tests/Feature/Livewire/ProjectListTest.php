<?php

declare(strict_types=1);

use App\Livewire\ProjectList;
use App\Services\ClaudeDataReader;
use Illuminate\Support\Collection;
use Livewire\Livewire;

test('project list component renders', function () {
    $this->mock(ClaudeDataReader::class, function ($mock) {
        $mock->shouldReceive('discoverProjects')->andReturn(collect());
    });

    Livewire::test(ProjectList::class)
        ->assertStatus(200)
        ->assertSee('Mission Control');
});

test('project list shows empty state when no projects', function () {
    $this->mock(ClaudeDataReader::class, function ($mock) {
        $mock->shouldReceive('discoverProjects')->andReturn(collect());
    });

    Livewire::test(ProjectList::class)
        ->assertSee('No Claude Code projects found');
});

test('project list dispatches blocked count on load', function () {
    $this->mock(ClaudeDataReader::class, function ($mock) {
        $mock->shouldReceive('discoverProjects')->andReturn(collect());
    });

    Livewire::test(ProjectList::class)
        ->assertDispatched('blockedCountUpdated', count: 0);
});

test('project list can refresh projects', function () {
    $this->mock(ClaudeDataReader::class, function ($mock) {
        $mock->shouldReceive('discoverProjects')->twice()->andReturn(collect());
    });

    Livewire::test(ProjectList::class)
        ->call('refresh')
        ->assertStatus(200);
});

test('project list handles project selection', function () {
    $this->mock(ClaudeDataReader::class, function ($mock) {
        $mock->shouldReceive('discoverProjects')->andReturn(collect());
    });

    Livewire::test(ProjectList::class)
        ->assertSet('selectedProjectId', null)
        ->dispatch('project-selected', projectId: 'test-123')
        ->assertSet('selectedProjectId', 'test-123');
});
