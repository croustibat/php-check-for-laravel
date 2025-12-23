<?php

declare(strict_types=1);

use App\Services\ClaudeDataReader;

test('mission control page is accessible', function () {
    $this->mock(ClaudeDataReader::class, function ($mock) {
        $mock->shouldReceive('discoverProjects')->andReturn(collect());
    });

    $response = $this->get(route('mission-control'));

    $response->assertStatus(200);
    $response->assertSee('Mission Control');
});

test('mission control page contains project list component', function () {
    $this->mock(ClaudeDataReader::class, function ($mock) {
        $mock->shouldReceive('discoverProjects')->andReturn(collect());
    });

    $response = $this->get(route('mission-control'));

    $response->assertSeeLivewire('project-list');
});

test('mission control page contains sidebar component', function () {
    $this->mock(ClaudeDataReader::class, function ($mock) {
        $mock->shouldReceive('discoverProjects')->andReturn(collect());
    });

    $response = $this->get(route('mission-control'));

    $response->assertSeeLivewire('sidebar');
});
