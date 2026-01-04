<?php

declare(strict_types=1);

use App\DTOs\Project;
use App\Services\ActiveSessionDetector;
use App\Services\StatusDetector;
use Carbon\Carbon;

test('detects idle status when no recent activity', function () {
    $project = new Project(
        id: 'test',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now()->subHours(3),
    );

    $mockSessionDetector = Mockery::mock(ActiveSessionDetector::class);
    $mockSessionDetector->shouldReceive('getSessionForProject')
        ->with('/Users/test/myapp')
        ->andReturn(null);

    $detector = new StatusDetector($mockSessionDetector);

    expect($detector->detect($project))->toBe('idle');
});

test('detects blocked status when last message is assistant with question', function () {
    $project = new Project(
        id: 'test',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now()->subMinutes(30),
        lastMessage: 'Would you like me to proceed with this approach?',
        lastMessageType: 'assistant',
    );

    $mockSessionDetector = Mockery::mock(ActiveSessionDetector::class);
    $mockSessionDetector->shouldReceive('getSessionForProject')
        ->with('/Users/test/myapp')
        ->andReturn(null);

    $detector = new StatusDetector($mockSessionDetector);

    expect($detector->detect($project))->toBe('blocked');
});

test('detects active status when claude process is running', function () {
    $project = new Project(
        id: 'test',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now()->subMinutes(5),
    );

    $mockSessionDetector = Mockery::mock(ActiveSessionDetector::class);
    $mockSessionDetector->shouldReceive('getSessionForProject')
        ->with('/Users/test/myapp')
        ->andReturn([
            'pid' => 12345,
            'tty' => 'ttys001',
            'cwd' => '/Users/test/myapp',
            'terminal' => 'warp',
            'status' => 'idle',
            'command' => 'opencode',
        ]);

    $detector = new StatusDetector($mockSessionDetector);

    expect($detector->detect($project))->toBe('active');
});

test('detects running status when process is actively working', function () {
    $project = new Project(
        id: 'test',
        name: 'myapp',
        path: '/Users/test/myapp',
        claudeDataPath: '/tmp/test',
        lastActivity: Carbon::now()->subMinutes(1),
    );

    $mockSessionDetector = Mockery::mock(ActiveSessionDetector::class);
    $mockSessionDetector->shouldReceive('getSessionForProject')
        ->with('/Users/test/myapp')
        ->andReturn([
            'pid' => 12345,
            'tty' => 'ttys001',
            'cwd' => '/Users/test/myapp',
            'terminal' => 'warp',
            'status' => 'running',
            'command' => 'claude',
        ]);

    $detector = new StatusDetector($mockSessionDetector);

    expect($detector->detect($project))->toBe('running');
});

test('detects asking permission patterns in messages', function () {
    $detector = new StatusDetector;

    $askingMessages = [
        'Would you like me to continue?',
        'Should I proceed with this?',
        'What do you think?',
        'Please confirm before I make changes.',
    ];

    foreach ($askingMessages as $message) {
        expect($detector->isAskingPermission($message, 'assistant'))->toBeTrue();
    }
});

test('does not detect asking permission for user messages', function () {
    $detector = new StatusDetector;

    expect($detector->isAskingPermission('Would you like me to continue?', 'user'))->toBeFalse();
});

test('does not detect asking permission for null message', function () {
    $detector = new StatusDetector;

    expect($detector->isAskingPermission(null, 'assistant'))->toBeFalse();
});
