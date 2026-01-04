<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\ClaudeDataReader;
use App\Services\StatusDetector;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectList extends Component
{
    /** @var Collection<int, \App\DTOs\Project> */
    public Collection $projects;

    public ?string $selectedProjectId = null;

    public function mount(): void
    {
        $this->projects = collect();
        $this->loadProjects();
    }

    public function loadProjects(): void
    {
        $reader = app(ClaudeDataReader::class);
        $detector = app(StatusDetector::class);
        $sessionDetector = app(\App\Services\ActiveSessionDetector::class);

        $activeSessions = $sessionDetector->getActiveSessions();

        $this->projects = $reader->discoverProjects()
            ->map(function ($project) use ($detector, $activeSessions) {
                $activeSession = $activeSessions->first(fn ($s) => $s['cwd'] === $project->path);

                if ($activeSession) {
                    $project = $project->withSessionData(
                        $activeSession['terminal'],
                        $activeSession['command']
                    );
                }

                return $project->withStatus($detector->detect($project));
            })
            ->filter(function ($project) {
                // Keep active or blocked projects regardless of age
                if (in_array($project->status, ['active', 'blocked'])) {
                    return true;
                }

                // Keep idle projects only if last activity was within 7 days
                if ($project->lastActivity === null) {
                    return false;
                }

                return $project->lastActivity->diffInDays(now()) < 7;
            })
            ->sortByDesc(function ($project) {
                // Sort by status priority first, then by last activity
                $statusPriority = match ($project->status) {
                    'blocked' => 3,
                    'active' => 2,
                    'idle' => 1,
                    default => 0,
                };

                return [$statusPriority, $project->lastActivity?->timestamp ?? 0];
            })
            ->values();

        $blockedCount = $this->projects->where('status', 'blocked')->count();
        $this->dispatch('blockedCountUpdated', count: $blockedCount);
    }

    public function refresh(): void
    {
        $this->loadProjects();
    }

    #[On('project-selected')]
    public function selectProject(string $projectId): void
    {
        $this->selectedProjectId = $projectId;
    }

    public function getBlockedCount(): int
    {
        return $this->projects->where('status', 'blocked')->count();
    }

    public function openAllBlocked(): void
    {
        $blockedProjects = $this->projects->where('status', 'blocked');

        foreach ($blockedProjects as $project) {
            $this->openProject($project);
        }
    }

    protected function openProject(\App\DTOs\Project $project): void
    {
        $path = $project->path;
        $encodedPath = urlencode($path);

        // Open based on the terminal/IDE used
        $terminal = $project->terminal ?? 'warp';

        match ($terminal) {
            'warp' => $this->openInWarp($path, $encodedPath),
            'zed' => exec("open -a 'Zed' ".escapeshellarg($path).' > /dev/null 2>&1 &'),
            'vscode' => exec('code '.escapeshellarg($path).' > /dev/null 2>&1 &'),
            'cursor' => exec('cursor '.escapeshellarg($path).' > /dev/null 2>&1 &'),
            default => $this->openInWarp($path, $encodedPath),
        };
    }

    protected function openInWarp(string $path, string $encodedPath): void
    {
        exec("open 'warp://action/new_tab?path={$encodedPath}' > /dev/null 2>&1");

        $script = <<<'APPLESCRIPT'
        delay 0.5
        tell application "Warp"
            activate
        end tell
        tell application "System Events"
            tell process "Warp"
                keystroke "claude"
                keystroke return
            end tell
        end tell
        APPLESCRIPT;

        exec('osascript -e '.escapeshellarg($script).' > /dev/null 2>&1 &');
    }

    public function render()
    {
        return view('livewire.project-list', [
            'blockedCount' => $this->getBlockedCount(),
        ]);
    }
}
