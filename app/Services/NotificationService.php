<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Project;
use Illuminate\Support\Facades\Log;
use Native\Laravel\Facades\Notification;

class NotificationService
{
    public function notifyBlocked(Project $project): void
    {
        try {
            if (class_exists(Notification::class)) {
                Notification::title('Claude needs your input')
                    ->message("{$project->name}: Claude needs your input")
                    ->show();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send notification', [
                'project' => $project->name,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Project blocked notification', [
            'project' => $project->name,
            'path' => $project->path,
        ]);
    }

    public function notifyActive(Project $project): void
    {
        Log::info('Project became active', [
            'project' => $project->name,
            'path' => $project->path,
        ]);
    }

    public function notifyIdle(Project $project): void
    {
        Log::info('Project became idle', [
            'project' => $project->name,
            'path' => $project->path,
        ]);
    }
}
