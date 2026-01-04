<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;
use Livewire\Wireable;

class Project implements Wireable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $path,
        public string $claudeDataPath,
        public ?string $gitBranch = null,
        public ?Carbon $lastActivity = null,
        public ?string $lastMessage = null,
        public ?string $lastMessageType = null,
        public string $status = 'idle',
        public string $icon = '📁',
        public ?string $terminal = null,
        public ?string $command = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toLivewire(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'claudeDataPath' => $this->claudeDataPath,
            'gitBranch' => $this->gitBranch,
            'lastActivity' => $this->lastActivity?->toIso8601String(),
            'lastMessage' => $this->lastMessage,
            'lastMessageType' => $this->lastMessageType,
            'status' => $this->status,
            'icon' => $this->icon,
            'terminal' => $this->terminal,
            'command' => $this->command,
        ];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function fromLivewire($value): static
    {
        return new static(
            id: $value['id'],
            name: $value['name'],
            path: $value['path'],
            claudeDataPath: $value['claudeDataPath'],
            gitBranch: $value['gitBranch'] ?? null,
            lastActivity: isset($value['lastActivity']) ? Carbon::parse($value['lastActivity']) : null,
            lastMessage: $value['lastMessage'] ?? null,
            lastMessageType: $value['lastMessageType'] ?? null,
            status: $value['status'] ?? 'idle',
            icon: $value['icon'] ?? '📁',
            terminal: $value['terminal'] ?? null,
            command: $value['command'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'gitBranch' => $this->gitBranch,
            'lastActivity' => $this->lastActivity?->toIso8601String(),
            'status' => $this->status,
            'lastMessage' => $this->lastMessage,
            'icon' => $this->icon,
        ];
    }

    public function getRelativeTime(): string
    {
        if (! $this->lastActivity) {
            return 'Never';
        }

        return $this->lastActivity->diffForHumans();
    }

    public function getMetric(): string
    {
        return match ($this->status) {
            'running' => 'Running',
            'asking_permission' => 'Asking',
            'active' => 'Active',
            'blocked' => '1 question',
            'idle' => $this->getIdleTime(),
            default => 'Unknown',
        };
    }

    public function getMetricLabel(): string
    {
        return match ($this->status) {
            'running' => 'in progress',
            'asking_permission' => 'needs permission',
            'active' => 'session open',
            'blocked' => 'waiting for response',
            'idle' => 'paused',
            default => '',
        };
    }

    protected function getIdleTime(): string
    {
        if (! $this->lastActivity) {
            return 'Never used';
        }

        $diffHours = (int) $this->lastActivity->diffInHours(now());
        if ($diffHours < 1) {
            $diffMinutes = (int) $this->lastActivity->diffInMinutes(now());

            return $diffMinutes.'m idle';
        }
        if ($diffHours < 24) {
            return $diffHours.'h idle';
        }

        $diffDays = (int) $this->lastActivity->diffInDays(now());

        return $diffDays.'d idle';
    }

    public function withStatus(string $status): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            path: $this->path,
            claudeDataPath: $this->claudeDataPath,
            gitBranch: $this->gitBranch,
            lastActivity: $this->lastActivity,
            lastMessage: $this->lastMessage,
            lastMessageType: $this->lastMessageType,
            status: $status,
            icon: $this->icon,
            terminal: $this->terminal,
            command: $this->command,
        );
    }

    public function withSessionData(?string $terminal, ?string $command): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            path: $this->path,
            claudeDataPath: $this->claudeDataPath,
            gitBranch: $this->gitBranch,
            lastActivity: $this->lastActivity,
            lastMessage: $this->lastMessage,
            lastMessageType: $this->lastMessageType,
            status: $this->status,
            icon: $this->icon,
            terminal: $terminal ?? $this->terminal,
            command: $command ?? $this->command,
        );
    }

    public function getCommandLabel(): string
    {
        return match ($this->command) {
            'opencode' => 'OpenCode',
            'claude' => 'Claude',
            default => 'CLI',
        };
    }

    public function getTerminalLabel(): string
    {
        return match ($this->terminal) {
            'warp' => 'Warp',
            'zed' => 'Zed',
            'vscode' => 'VS Code',
            'cursor' => 'Cursor',
            'terminal' => 'Terminal',
            'iterm' => 'iTerm',
            default => 'Terminal',
        };
    }

    public function getTerminalIcon(): string
    {
        return match ($this->terminal) {
            'warp' => '⚡',
            'zed' => '⚡',
            'vscode' => '💻',
            'cursor' => '🖱️',
            'terminal' => '🖥️',
            'iterm' => '🖥️',
            default => '🖥️',
        };
    }
}
