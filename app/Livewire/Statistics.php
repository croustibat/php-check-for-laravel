<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\StatsReader;
use Livewire\Component;

class Statistics extends Component
{
    public int $totalSessions = 0;

    public int $totalMessages = 0;

    public int $todayMessages = 0;

    public int $todaySessions = 0;

    public int $weeklyMessages = 0;

    public int $weeklySessions = 0;

    public int $weeklyToolCalls = 0;

    public string $totalTokens = '0';

    public ?string $firstSessionDate = null;

    /**
     * @var array<int, array{date: string, messageCount: int, sessionCount: int, toolCallCount: int}>
     */
    public array $dailyActivity = [];

    /**
     * @var array<string, array{inputTokens: int, outputTokens: int}>
     */
    public array $modelUsage = [];

    /**
     * @var array<int, int>
     */
    public array $hourlyDistribution = [];

    /**
     * @var array<int, array{date: string, tokens: int}>
     */
    public array $dailyTokens = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $reader = app(StatsReader::class);

        $summary = $reader->getSummary();
        $this->totalSessions = $summary['totalSessions'];
        $this->totalMessages = $summary['totalMessages'];
        $this->firstSessionDate = $summary['firstSessionDate'];

        $this->todayMessages = $reader->getTodayMessages();
        $this->todaySessions = $reader->getTodaySessions();

        $weekly = $reader->getWeeklySummary();
        $this->weeklyMessages = $weekly['messages'];
        $this->weeklySessions = $weekly['sessions'];
        $this->weeklyToolCalls = $weekly['toolCalls'];

        $this->totalTokens = $reader->formatTokens($reader->getTotalTokens());

        $this->dailyActivity = $reader->getDailyActivity(14)->toArray();
        $this->modelUsage = $reader->getModelUsage()->toArray();
        $this->hourlyDistribution = $reader->getHourlyDistribution();
        $this->dailyTokens = $reader->getDailyTokens(14)->toArray();
    }

    public function refresh(): void
    {
        $this->loadStats();
    }

    public function render()
    {
        return view('livewire.statistics');
    }
}
