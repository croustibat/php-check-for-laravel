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

        $this->projects = $reader->discoverProjects()->map(function ($project) use ($detector) {
            return $project->withStatus($detector->detect($project));
        });

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

    public function render()
    {
        return view('livewire.project-list', [
            'blockedCount' => $this->getBlockedCount(),
        ]);
    }
}
