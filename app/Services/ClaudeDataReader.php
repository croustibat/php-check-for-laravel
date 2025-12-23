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
    ];

    public function __construct(?string $basePath = null)
    {
        $defaultPath = ($_SERVER['HOME'] ?? '/tmp') . '/.claude';
        $this->basePath = $basePath ?? config('services.claude.data_path', $defaultPath);
    }

    /**
     * @return Collection<int, Project>
     */
    public function discoverProjects(): Collection
    {
        $projectsPath = $this->basePath . '/projects';

        if (! File::isDirectory($projectsPath)) {
            return collect();
        }

        return collect(File::directories($projectsPath))
            ->filter(fn ($dir) => ! Str::startsWith(basename($dir), '.'))
            ->map(fn ($dir) => $this->parseProjectDirectory($dir))
            ->filter()
            ->sortByDesc(fn ($project) => $project->lastActivity)
            ->values();
    }

    protected function parseProjectDirectory(string $dir): ?Project
    {
        $dirName = basename($dir);

        // Decode: -Users-croustibat-Herd-gesta -> /Users/croustibat/Herd/gesta
        $realPath = str_replace('-', '/', $dirName);
        if (! Str::startsWith($realPath, '/')) {
            $realPath = '/' . $realPath;
        }

        $name = basename($realPath);

        $sessionFiles = File::glob($dir . '/*.jsonl');
        if (empty($sessionFiles)) {
            return null;
        }

        $latestSession = $this->getLatestSessionData($sessionFiles);

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
            icon: $this->projectIcons[$name] ?? '📁',
        );
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
