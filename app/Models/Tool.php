<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tool extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'app_path',
        'cli_command',
        'icon',
        'is_installed',
        'is_enabled',
        'launch_template',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_installed' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * @return HasMany<WorkSession, $this>
     */
    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projectsAsDefault(): HasMany
    {
        return $this->hasMany(Project::class, 'default_tool_id');
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projectsAsDefaultAiCli(): HasMany
    {
        return $this->hasMany(Project::class, 'default_ai_cli_id');
    }

    public function isIde(): bool
    {
        return $this->type === 'ide';
    }

    public function isTerminal(): bool
    {
        return $this->type === 'terminal';
    }

    public function isAiCli(): bool
    {
        return $this->type === 'ai_cli';
    }

    public function getDisplayIcon(): string
    {
        return $this->icon ?? match ($this->type) {
            'ide' => '💻',
            'terminal' => '🖥️',
            'ai_cli' => '🤖',
            default => '🔧',
        };
    }
}
