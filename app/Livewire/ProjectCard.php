<?php

declare(strict_types=1);

namespace App\Livewire;

use App\DTOs\Project;
use Livewire\Component;

class ProjectCard extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function open(): void
    {
        $path = $this->project->path;

        // Open Warp in the project directory and run claude
        // Using Warp's URL scheme: warp://action/new_tab?path=/path
        $encodedPath = urlencode($path);
        exec("open 'warp://action/new_tab?path={$encodedPath}' > /dev/null 2>&1");

        // Small delay then send the claude command via AppleScript
        $script = <<<APPLESCRIPT
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

        exec("osascript -e " . escapeshellarg($script) . " > /dev/null 2>&1 &");

        $this->dispatch('project-opened', projectId: $this->project->id);
    }

    public function select(): void
    {
        $this->dispatch('project-selected', projectId: $this->project->id);
    }

    public function render()
    {
        return view('livewire.project-card');
    }
}
