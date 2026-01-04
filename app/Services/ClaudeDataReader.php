<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ClaudeDataReader
{
    private string $basePath;

    private ActiveSessionDetector $sessionDetector;

    /**
     * @var array<string, string>
     */
    private array $projectIcons = [
        'gesta' => '💰',
        'trackguessr' => '🎵',
        'paperboy' => '📰',
        'dartmate' => '🎯',
        'boardhub' => '📊',
        'job-scraper' => '🔍',
        'echo-cms' => '📝',
        'malistedenvies' => '🎁',
        'pennywize' => '💳',
        'tolery' => '🔧',
        'mission-control' => '🎛️',
        'filament-jobs-monitor' => '📋',
        'filament-audio-field-column' => '🎵',
        'le-cartable-ludique' => '🎒',
        'workspace-manager' => '🗂️',
    ];

    public function __construct(?string $basePath = null, ?ActiveSessionDetector $sessionDetector = null)
    {
        $defaultPath = ($_SERVER['HOME'] ?? '/tmp').'/.claude';
        $this->basePath = $basePath ?? config('services.claude.data_path', $defaultPath);
        $this->sessionDetector = $sessionDetector ?? new ActiveSessionDetector;
    }

    /**
     * @return Collection<int, Project>
     */
    public function discoverProjects(): Collection
    {
        $activeSessions = $this->sessionDetector->getActiveSessions();
        $projects = collect();

        foreach ($activeSessions as $session) {
            $project = $this->createProjectFromSession($session);
            if ($project) {
                $projects->put($project->path, $project);
            }
        }

        $projectsPath = $this->basePath.'/projects';
        if (File::isDirectory($projectsPath)) {
            foreach (File::directories($projectsPath) as $dir) {
                if (Str::startsWith(basename($dir), '.')) {
                    continue;
                }

                $project = $this->parseProjectDirectory($dir, $activeSessions);
                if ($project && ! $projects->has($project->path)) {
                    $projects->put($project->path, $project);
                }
            }
        }

        return $projects
            ->values()
            ->sortByDesc(fn ($project) => $project->lastActivity)
            ->values();
    }

    /**
     * @param  array{pid: int, tty: string, cwd: string, terminal: string, status: string, command: string}  $session
     */
    protected function createProjectFromSession(array $session): ?Project
    {
        $path = $session['cwd'];
        if (! is_dir($path) || $path === ($_SERVER['HOME'] ?? '')) {
            return null;
        }

        $name = basename($path);
        $claudeDataPath = $this->basePath.'/projects/-'.str_replace('/', '-', ltrim($path, '/'));

        $lastActivity = null;
        $lastMessage = null;
        $lastMessageType = null;
        $gitBranch = null;

        if (is_dir($claudeDataPath)) {
            $sessionFiles = File::glob($claudeDataPath.'/*.jsonl');
            $sessionFiles = array_filter($sessionFiles, fn ($f) => ! str_contains(basename($f), 'agent-'));
            if (! empty($sessionFiles)) {
                $latestSession = $this->getLatestSessionData($sessionFiles);
                $lastActivity = isset($latestSession['timestamp']) ? Carbon::parse($latestSession['timestamp']) : null;
                $lastMessage = $this->extractLastMessage($latestSession);
                $lastMessageType = $latestSession['type'] ?? null;
                $gitBranch = $latestSession['gitBranch'] ?? null;
            }
        }

        if (! $lastActivity) {
            $lastActivity = Carbon::now();
        }

        return new Project(
            id: md5($path),
            name: $name,
            path: $path,
            claudeDataPath: $claudeDataPath,
            gitBranch: $gitBranch,
            lastActivity: $lastActivity,
            lastMessage: $lastMessage,
            lastMessageType: $lastMessageType,
            icon: $this->getProjectIcon($name, $path),
            terminal: $session['terminal'],
            command: $session['command'],
        );
    }

    /**
     * @param  Collection<int, array{pid: int, tty: string, cwd: string, terminal: string, status: string, command: string}>  $activeSessions
     */
    protected function parseProjectDirectory(string $dir, Collection $activeSessions): ?Project
    {
        $dirName = basename($dir);
        $realPath = $this->decodeProjectPath($dirName);

        if (! $realPath) {
            return null;
        }

        $name = basename($realPath);

        $sessionFiles = File::glob($dir.'/*.jsonl');
        $sessionFiles = array_filter($sessionFiles, fn ($f) => ! str_contains(basename($f), 'agent-'));
        if (empty($sessionFiles)) {
            return null;
        }

        $latestSession = $this->getLatestSessionData($sessionFiles);
        $activeSession = $activeSessions->first(fn ($s) => $s['cwd'] === $realPath);
        $terminal = $activeSession ? $activeSession['terminal'] : $this->detectTerminalFromHistory($dir);
        $command = $activeSession ? $activeSession['command'] : null;

        return new Project(
            id: md5($dir),
            name: $name,
            path: $realPath,
            claudeDataPath: $dir,
            gitBranch: $latestSession['gitBranch'] ?? null,
            lastActivity: isset($latestSession['timestamp'])
                ? Carbon::parse($latestSession['timestamp'])
                : null,
            lastMessage: $this->extractLastMessage($latestSession),
            lastMessageType: $latestSession['type'] ?? null,
            icon: $this->getProjectIcon($name, $realPath),
            terminal: $terminal,
            command: $command,
        );
    }

    protected function decodeProjectPath(string $encodedPath): ?string
    {
        $path = ltrim($encodedPath, '-');
        if (empty($path)) {
            return null;
        }

        return '/'.str_replace('-', '/', $path);
    }

    protected function getProjectIcon(string $name, string $path): string
    {
        // Check direct name match
        $nameLower = strtolower($name);
        foreach ($this->projectIcons as $key => $icon) {
            if (str_contains($nameLower, $key)) {
                return $icon;
            }
        }

        // Check path for patterns
        if (str_contains($path, 'LARAVEL-PACKAGES')) {
            return '📦';
        }
        if (str_contains($path, 'CHROME-EXTENSIONS')) {
            return '🌐';
        }

        return '📁';
    }

    protected function detectTerminalFromHistory(string $projectDir): string
    {
        // Try to detect from session files metadata
        $sessionFiles = File::glob($projectDir.'/*.jsonl');

        foreach ($sessionFiles as $file) {
            $firstLine = $this->getFirstLine($file);
            if ($firstLine) {
                $data = json_decode($firstLine, true);
                if (isset($data['terminal'])) {
                    return $this->normalizeTerminal($data['terminal']);
                }
            }
        }

        // Default based on what terminals are commonly running
        return $this->detectDefaultTerminal();
    }

    protected function detectDefaultTerminal(): string
    {
        // Check which terminal apps are running
        $terminals = [
            ['cmd' => "pgrep -f 'Warp.app'", 'name' => 'warp'],
            ['cmd' => "pgrep -f 'Zed.app'", 'name' => 'zed'],
            ['cmd' => "pgrep -f 'Cursor.app'", 'name' => 'cursor'],
            ['cmd' => 'pgrep -x Code', 'name' => 'vscode'],
            ['cmd' => "pgrep -f 'iTerm'", 'name' => 'iterm'],
        ];

        foreach ($terminals as $terminal) {
            $result = shell_exec($terminal['cmd'].' 2>/dev/null');
            if (! empty(trim($result ?: ''))) {
                return $terminal['name'];
            }
        }

        return 'terminal';
    }

    protected function getFirstLine(string $file): ?string
    {
        $handle = fopen($file, 'r');
        if (! $handle) {
            return null;
        }

        $line = fgets($handle);
        fclose($handle);

        return $line ? trim($line) : null;
    }

    protected function normalizeTerminal(string $terminal): string
    {
        $terminal = strtolower($terminal);

        return match (true) {
            str_contains($terminal, 'warp') => 'warp',
            str_contains($terminal, 'zed') => 'zed',
            str_contains($terminal, 'code') || str_contains($terminal, 'vscode') => 'vscode',
            str_contains($terminal, 'cursor') => 'cursor',
            str_contains($terminal, 'iterm') => 'iterm',
            str_contains($terminal, 'terminal') => 'terminal',
            default => 'warp',
        };
    }

    /**
     * @param  array<int, string>  $sessionFiles
     * @return array<string, mixed>
     */
    protected function getLatestSessionData(array $sessionFiles): array
    {
        $latestTimestamp = null;
        $latestData = [];

        foreach ($sessionFiles as $file) {
            $lastLine = $this->getLastLine($file);
            if (! $lastLine) {
                continue;
            }

            $data = json_decode($lastLine, true);
            if (! $data || ! isset($data['timestamp'])) {
                continue;
            }

            $timestamp = Carbon::parse($data['timestamp']);
            if (! $latestTimestamp || $timestamp->gt($latestTimestamp)) {
                $latestTimestamp = $timestamp;
                $latestData = $data;
            }
        }

        return $latestData;
    }

    protected function getLastLine(string $file): ?string
    {
        $handle = fopen($file, 'r');
        if (! $handle) {
            return null;
        }

        $lastLine = null;
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if (! empty($trimmed)) {
                $lastLine = $trimmed;
            }
        }
        fclose($handle);

        return $lastLine;
    }

    /**
     * @param  array<string, mixed>  $sessionData
     */
    protected function extractLastMessage(array $sessionData): ?string
    {
        if (! isset($sessionData['message']['content'])) {
            return null;
        }

        $content = $sessionData['message']['content'];

        if (is_array($content)) {
            foreach ($content as $block) {
                if (isset($block['type']) && $block['type'] === 'text') {
                    $content = $block['text'];
                    break;
                }
            }
        }

        if (is_array($content)) {
            return null;
        }

        return Str::limit((string) $content, 150);
    }
}
