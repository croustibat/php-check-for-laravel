<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ClaudeDataReader;
use App\Services\NotificationService;
use App\Services\StatusDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WatchClaudeSessions extends Command
{
    protected $signature = 'claude:watch {--interval=30 : Polling interval in seconds}';

    protected $description = 'Watch Claude Code sessions and send notifications on status changes';

    public function handle(
        ClaudeDataReader $reader,
        StatusDetector $detector,
        NotificationService $notifier,
    ): int {
        $interval = (int) $this->option('interval');

        $this->info('Watching Claude sessions...');
        $this->info("Polling interval: {$interval} seconds");
        $this->newLine();

        while (true) {
            $projects = $reader->discoverProjects();

            foreach ($projects as $project) {
                $currentStatus = $detector->detect($project);
                $cacheKey = "project_status_{$project->id}";
                $previousStatus = Cache::get($cacheKey);

                if ($previousStatus !== $currentStatus) {
                    Cache::put($cacheKey, $currentStatus, now()->addHours(24));

                    if ($previousStatus !== null) {
                        $this->handleStatusChange($project->withStatus($currentStatus), $previousStatus, $currentStatus, $notifier);
                    } else {
                        $this->line("<fg=gray>{$project->name}: Initial status is {$currentStatus}</>");
                    }
                }
            }

            sleep($interval);
        }
    }

    protected function handleStatusChange(
        \App\DTOs\Project $project,
        string $previousStatus,
        string $currentStatus,
        NotificationService $notifier,
    ): void {
        $statusColors = [
            'active' => 'green',
            'blocked' => 'yellow',
            'idle' => 'gray',
        ];

        $color = $statusColors[$currentStatus] ?? 'white';

        $this->line("<fg={$color}>{$project->name}: {$previousStatus} → {$currentStatus}</>");

        match ($currentStatus) {
            'blocked' => $notifier->notifyBlocked($project),
            'active' => $notifier->notifyActive($project),
            'idle' => $notifier->notifyIdle($project),
            default => null,
        };
    }
}
