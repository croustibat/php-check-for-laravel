<?php

declare(strict_types=1);

use App\DTOs\Project;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

test('notification service logs blocked notification', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Project blocked notification', \Mockery::any());

    Log::shouldReceive('warning')->never();

    $project = new Project(
        id: 'test-123',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now(),
        status: 'blocked',
    );

    $service = new NotificationService;
    $service->notifyBlocked($project);
});

test('notification service logs active notification', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Project became active', \Mockery::any());

    $project = new Project(
        id: 'test-123',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now(),
        status: 'active',
    );

    $service = new NotificationService;
    $service->notifyActive($project);
});

test('notification service logs idle notification', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Project became idle', \Mockery::any());

    $project = new Project(
        id: 'test-123',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now(),
        status: 'idle',
    );

    $service = new NotificationService;
    $service->notifyIdle($project);
});
