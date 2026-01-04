<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSession extends Model
{
    protected $fillable = [
        'project_id',
        'tool_id',
        'ai_cli_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'is_manual',
        'notes',
        'git_commits_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
            'is_manual' => 'boolean',
            'git_commits_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Tool, $this>
     */
    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    /**
     * @return BelongsTo<Tool, $this>
     */
    public function aiCli(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'ai_cli_id');
    }

    /**
     * @param  Builder<WorkSession>  $query
     * @return Builder<WorkSession>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('started_at', today());
    }

    /**
     * @param  Builder<WorkSession>  $query
     * @return Builder<WorkSession>
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->where('started_at', '>=', now()->startOfWeek());
    }

    /**
     * @param  Builder<WorkSession>  $query
     * @return Builder<WorkSession>
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /**
     * @param  Builder<WorkSession>  $query
     * @return Builder<WorkSession>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('ended_at');
    }

    public function isRunning(): bool
    {
        return $this->ended_at === null;
    }

    public function stop(): void
    {
        if (! $this->isRunning()) {
            return;
        }

        $this->update([
            'ended_at' => now(),
            'duration_seconds' => now()->diffInSeconds($this->started_at),
        ]);
    }

    public function getFormattedDuration(): string
    {
        $seconds = $this->duration_seconds;

        if ($this->isRunning()) {
            $seconds = now()->diffInSeconds($this->started_at);
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function getCurrentDurationSeconds(): int
    {
        if ($this->isRunning()) {
            return (int) now()->diffInSeconds($this->started_at);
        }

        return $this->duration_seconds;
    }
}
