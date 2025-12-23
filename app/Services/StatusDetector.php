<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Project;

class StatusDetector
{
    /**
     * @var array<int, string>
     */
    private array $blockedPatterns = [
        '/would you like/i',
        '/should I proceed/i',
        '/should I continue/i',
        '/what do you think/i',
        '/please confirm/i',
        '/do you want me to/i',
        '/let me know if/i',
        '/shall I/i',
        '/want me to/i',
        '/\?$/m',
    ];

    /**
     * @var array<int, string>
     */
    private array $activeProcessPaths = [];

    public function detect(Project $project): string
    {
        if ($this->isProcessActive($project->path)) {
            return 'active';
        }

        if ($this->isBlockedMessage($project->lastMessage, $project->lastMessageType)) {
            return 'blocked';
        }

        return 'idle';
    }

    public function isProcessActive(string $projectPath): bool
    {
        if (! empty($this->activeProcessPaths)) {
            return in_array($projectPath, $this->activeProcessPaths);
        }

        $output = shell_exec('ps aux | grep claude | grep -v grep 2>/dev/null');
        if (! $output) {
            return false;
        }

        return str_contains($output, $projectPath);
    }

    public function isBlockedMessage(?string $message, ?string $type): bool
    {
        if (! $message || $type !== 'assistant') {
            return false;
        }

        foreach ($this->blockedPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function setActiveProcessPaths(array $paths): void
    {
        $this->activeProcessPaths = $paths;
    }
}
