<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Project;

class StatusDetector
{
    /**
     * Patterns that indicate Claude is asking for user input/permission.
     *
     * @var array<int, string>
     */
    private array $askingPermissionPatterns = [
        '/would you like/i',
        '/should I proceed/i',
        '/should I continue/i',
        '/what do you think/i',
        '/please confirm/i',
        '/do you want me to/i',
        '/let me know if/i',
        '/shall I/i',
        '/want me to/i',
        '/can I proceed/i',
        '/may I/i',
        '/approve/i',
        '/permission/i',
    ];

    private ActiveSessionDetector $sessionDetector;

    public function __construct(?ActiveSessionDetector $sessionDetector = null)
    {
        $this->sessionDetector = $sessionDetector ?? new ActiveSessionDetector;
    }

    /**
     * Detect the status of a Claude session.
     *
     * @return string One of: 'running', 'asking_permission', 'idle', 'no_session'
     */
    public function detect(Project $project): string
    {
        // Check if there's an active process for this project
        $activeSession = $this->sessionDetector->getSessionForProject($project->path);

        if ($activeSession) {
            // Session is running, check its specific status
            if ($activeSession['status'] === 'asking_permission') {
                return 'asking_permission';
            }

            if ($activeSession['status'] === 'running') {
                return 'running';
            }

            // Process exists but is idle - check if waiting for input
            if ($this->isAskingPermission($project->lastMessage, $project->lastMessageType)) {
                return 'asking_permission';
            }

            return 'active';
        }

        // No active process - check if last message was asking for permission
        if ($this->isAskingPermission($project->lastMessage, $project->lastMessageType)) {
            return 'blocked';
        }

        return 'idle';
    }

    /**
     * Check if the project has an active Claude process.
     */
    public function isProcessActive(string $projectPath): bool
    {
        return $this->sessionDetector->isProjectActive($projectPath);
    }

    /**
     * Check if the last message indicates Claude is asking for permission/input.
     */
    public function isAskingPermission(?string $message, ?string $type): bool
    {
        if (! $message || $type !== 'assistant') {
            return false;
        }

        foreach ($this->askingPermissionPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        // Also check if message ends with a question mark
        if (preg_match('/\?\s*$/m', $message)) {
            return true;
        }

        return false;
    }

    /**
     * Get a human-readable status label.
     */
    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'running' => 'Running',
            'asking_permission' => 'Asking Permission',
            'active' => 'Active',
            'blocked' => 'Waiting for Response',
            'idle' => 'Idle',
            'no_session' => 'No Session',
            default => ucfirst($status),
        };
    }

    /**
     * Get status color class.
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'running' => 'emerald',
            'asking_permission', 'blocked' => 'amber',
            'active' => 'blue',
            'idle' => 'slate',
            default => 'gray',
        };
    }
}
