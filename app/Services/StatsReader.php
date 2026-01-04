<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class StatsReader
{
    private string $basePath;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $cachedStats = null;

    public function __construct(?string $basePath = null)
    {
        $defaultPath = ($_SERVER['HOME'] ?? '/tmp').'/.claude';
        $this->basePath = $basePath ?? config('services.claude.data_path', $defaultPath);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        if ($this->cachedStats !== null) {
            return $this->cachedStats;
        }

        $statsFile = $this->basePath.'/stats-cache.json';

        if (! File::exists($statsFile)) {
            return [];
        }

        $content = File::get($statsFile);
        $this->cachedStats = json_decode($content, true) ?: [];

        return $this->cachedStats;
    }

    /**
     * Get daily activity for the last N days.
     *
     * @return Collection<int, array{date: string, messageCount: int, sessionCount: int, toolCallCount: int}>
     */
    public function getDailyActivity(int $days = 30): Collection
    {
        $stats = $this->getStats();
        $activity = $stats['dailyActivity'] ?? [];

        return collect($activity)
            ->filter(function ($day) use ($days) {
                $date = Carbon::parse($day['date']);

                return $date->diffInDays(now()) <= $days;
            })
            ->sortByDesc('date')
            ->values();
    }

    /**
     * Get token usage by model.
     *
     * @return Collection<string, array{inputTokens: int, outputTokens: int, cacheReadInputTokens: int, cacheCreationInputTokens: int}>
     */
    public function getModelUsage(): Collection
    {
        $stats = $this->getStats();

        return collect($stats['modelUsage'] ?? []);
    }

    /**
     * Get total statistics summary.
     *
     * @return array{totalSessions: int, totalMessages: int, firstSessionDate: string|null, longestSession: array|null}
     */
    public function getSummary(): array
    {
        $stats = $this->getStats();

        return [
            'totalSessions' => $stats['totalSessions'] ?? 0,
            'totalMessages' => $stats['totalMessages'] ?? 0,
            'firstSessionDate' => $stats['firstSessionDate'] ?? null,
            'longestSession' => $stats['longestSession'] ?? null,
        ];
    }

    /**
     * Get activity by hour of day.
     *
     * @return array<int, int>
     */
    public function getHourlyDistribution(): array
    {
        $stats = $this->getStats();

        return $stats['hourCounts'] ?? [];
    }

    /**
     * Calculate total tokens used.
     */
    public function getTotalTokens(): int
    {
        $modelUsage = $this->getModelUsage();
        $total = 0;

        foreach ($modelUsage as $usage) {
            $total += ($usage['inputTokens'] ?? 0);
            $total += ($usage['outputTokens'] ?? 0);
        }

        return $total;
    }

    /**
     * Calculate total tokens including cache.
     */
    public function getTotalTokensWithCache(): int
    {
        $modelUsage = $this->getModelUsage();
        $total = 0;

        foreach ($modelUsage as $usage) {
            $total += ($usage['inputTokens'] ?? 0);
            $total += ($usage['outputTokens'] ?? 0);
            $total += ($usage['cacheReadInputTokens'] ?? 0);
            $total += ($usage['cacheCreationInputTokens'] ?? 0);
        }

        return $total;
    }

    /**
     * Get messages count for today.
     */
    public function getTodayMessages(): int
    {
        $today = now()->format('Y-m-d');
        $activity = $this->getDailyActivity(1);

        $todayActivity = $activity->first(fn ($day) => $day['date'] === $today);

        return $todayActivity['messageCount'] ?? 0;
    }

    /**
     * Get sessions count for today.
     */
    public function getTodaySessions(): int
    {
        $today = now()->format('Y-m-d');
        $activity = $this->getDailyActivity(1);

        $todayActivity = $activity->first(fn ($day) => $day['date'] === $today);

        return $todayActivity['sessionCount'] ?? 0;
    }

    /**
     * Get weekly summary.
     *
     * @return array{messages: int, sessions: int, toolCalls: int}
     */
    public function getWeeklySummary(): array
    {
        $activity = $this->getDailyActivity(7);

        return [
            'messages' => $activity->sum('messageCount'),
            'sessions' => $activity->sum('sessionCount'),
            'toolCalls' => $activity->sum('toolCallCount'),
        ];
    }

    /**
     * Get daily tokens for chart.
     *
     * @return Collection<int, array{date: string, tokens: int}>
     */
    public function getDailyTokens(int $days = 30): Collection
    {
        $stats = $this->getStats();
        $dailyTokens = $stats['dailyModelTokens'] ?? [];

        return collect($dailyTokens)
            ->filter(function ($day) use ($days) {
                $date = Carbon::parse($day['date']);

                return $date->diffInDays(now()) <= $days;
            })
            ->map(function ($day) {
                $total = 0;
                foreach ($day['tokensByModel'] ?? [] as $tokens) {
                    $total += $tokens;
                }

                return [
                    'date' => $day['date'],
                    'tokens' => $total,
                ];
            })
            ->sortBy('date')
            ->values();
    }

    /**
     * Format token count for display.
     */
    public function formatTokens(int $tokens): string
    {
        if ($tokens >= 1000000000) {
            return number_format($tokens / 1000000000, 1).'B';
        }
        if ($tokens >= 1000000) {
            return number_format($tokens / 1000000, 1).'M';
        }
        if ($tokens >= 1000) {
            return number_format($tokens / 1000, 1).'K';
        }

        return (string) $tokens;
    }
}
