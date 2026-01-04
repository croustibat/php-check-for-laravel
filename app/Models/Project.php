<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'path',
        'icon',
        'default_tool_id',
        'default_ai_cli_id',
        'source',
        'is_favorite',
        'is_archived',
        'git_remote_url',
        'last_opened_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'is_archived' => 'boolean',
            'last_opened_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tool, $this>
     */
    public function defaultTool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'default_tool_id');
    }

    /**
     * @return BelongsTo<Tool, $this>
     */
    public function defaultAiCli(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'default_ai_cli_id');
    }

    /**
     * @return HasMany<WorkSession, $this>
     */
    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeFavorites(Builder $query): Builder
    {
        return $query->where('is_favorite', true);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_opened_at', '>=', now()->subDays($days));
    }

    public function getTotalTimeToday(): int
    {
        return $this->workSessions()
            ->whereDate('started_at', today())
            ->sum('duration_seconds');
    }

    public function getTotalTimeThisWeek(): int
    {
        return $this->workSessions()
            ->where('started_at', '>=', now()->startOfWeek())
            ->sum('duration_seconds');
    }

    public function getFormattedTimeToday(): string
    {
        return $this->formatDuration($this->getTotalTimeToday());
    }

    public function getFormattedTimeThisWeek(): string
    {
        return $this->formatDuration($this->getTotalTimeThisWeek());
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return $hours.'h '.($minutes > 0 ? $minutes.'m' : '');
        }

        return $minutes.'m';
    }

    public function markAsOpened(): void
    {
        $this->update(['last_opened_at' => now()]);
    }

    public function toggleFavorite(): void
    {
        $this->update(['is_favorite' => ! $this->is_favorite]);
    }

    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    public function getGitBranch(): ?string
    {
        if (! is_dir($this->path.'/.git')) {
            return null;
        }

        $branch = trim(shell_exec("git -C {$this->path} branch --show-current 2>/dev/null") ?? '');

        return $branch ?: null;
    }

    public function hasGit(): bool
    {
        return is_dir($this->path.'/.git');
    }
}
