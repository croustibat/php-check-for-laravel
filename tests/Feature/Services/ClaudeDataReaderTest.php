<?php

declare(strict_types=1);

use App\Services\ActiveSessionDetector;
use App\Services\ClaudeDataReader;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->testClaudePath = storage_path('testing/claude');

    File::deleteDirectory($this->testClaudePath);
    mkdir($this->testClaudePath.'/projects', 0755, true);

    $projectPath = $this->testClaudePath.'/projects/-Users-test-Projects-myapp';
    mkdir($projectPath, 0755, true);

    $sessionData = [
        'cwd' => '/Users/test/Projects/myapp',
        'sessionId' => 'test-session-123',
        'gitBranch' => 'main',
        'timestamp' => '2024-12-21T10:00:00.000Z',
        'type' => 'assistant',
        'message' => ['content' => 'Test message'],
    ];

    file_put_contents(
        $projectPath.'/session-abc123.jsonl',
        json_encode($sessionData)
    );

    config(['services.claude.data_path' => $this->testClaudePath]);

    $this->mockSessionDetector = Mockery::mock(ActiveSessionDetector::class);
    $this->mockSessionDetector->shouldReceive('getActiveSessions')->andReturn(collect());
});

afterEach(function () {
    File::deleteDirectory($this->testClaudePath);
});

test('can discover all projects from claude data directory', function () {
    $reader = new ClaudeDataReader($this->testClaudePath, $this->mockSessionDetector);
    $projects = $reader->discoverProjects();

    expect($projects)->toBeInstanceOf(Collection::class);
    expect($projects)->toHaveCount(1);
    expect($projects->first()->path)->toBe('/Users/test/Projects/myapp');
});

test('can get project name from path', function () {
    $reader = new ClaudeDataReader($this->testClaudePath, $this->mockSessionDetector);
    $projects = $reader->discoverProjects();

    expect($projects->first()->name)->toBe('myapp');
});

test('can get last activity timestamp', function () {
    $reader = new ClaudeDataReader($this->testClaudePath, $this->mockSessionDetector);
    $projects = $reader->discoverProjects();

    expect($projects->first()->lastActivity)->toBeInstanceOf(Carbon::class);
});

test('can extract git branch from session data', function () {
    $reader = new ClaudeDataReader($this->testClaudePath, $this->mockSessionDetector);
    $projects = $reader->discoverProjects();

    expect($projects->first()->gitBranch)->toBe('main');
});

test('can extract last message from session data', function () {
    $reader = new ClaudeDataReader($this->testClaudePath, $this->mockSessionDetector);
    $projects = $reader->discoverProjects();

    expect($projects->first()->lastMessage)->toBe('Test message');
});

test('returns empty collection when projects directory does not exist', function () {
    $reader = new ClaudeDataReader('/nonexistent/path', $this->mockSessionDetector);
    $projects = $reader->discoverProjects();

    expect($projects)->toBeInstanceOf(Collection::class);
    expect($projects)->toHaveCount(0);
});
