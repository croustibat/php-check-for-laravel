<?php

declare(strict_types=1);

use App\DTOs\Project;
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

    $detector = new StatusDetector;

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

    $detector = new StatusDetector;

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

    $detector = new StatusDetector;
    $detector->setActiveProcessPaths(['/Users/test/myapp']);

    expect($detector->detect($project))->toBe('active');
});

test('detects blocked patterns in messages', function () {
    $detector = new StatusDetector;

    $blockedMessages = [
        'Would you like me to continue?',
        'Should I proceed with this?',
        'What do you think?',
        'Please confirm before I make changes.',
    ];

    foreach ($blockedMessages as $message) {
        expect($detector->isBlockedMessage($message, 'assistant'))->toBeTrue();
    }
});

test('does not detect blocked for user messages', function () {
    $detector = new StatusDetector;

    expect($detector->isBlockedMessage('Would you like me to continue?', 'user'))->toBeFalse();
});

test('does not detect blocked for null message', function () {
    $detector = new StatusDetector;

    expect($detector->isBlockedMessage(null, 'assistant'))->toBeFalse();
});
