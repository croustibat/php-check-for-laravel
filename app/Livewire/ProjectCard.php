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
        $terminal = $this->project->terminal ?? 'warp';

        match ($terminal) {
            'warp' => $this->openInWarp($path),
            'zed' => $this->openInZed($path),
            'vscode' => $this->openInVSCode($path),
            'cursor' => $this->openInCursor($path),
            'iterm' => $this->openInITerm($path),
            default => $this->openInWarp($path),
        };

        $this->dispatch('project-opened', projectId: $this->project->id);
    }

    protected function openInWarp(string $path): void
    {
        $encodedPath = urlencode($path);
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

    protected function openInZed(string $path): void
    {
        exec("open -a 'Zed' ".escapeshellarg($path).' > /dev/null 2>&1 &');
    }

    protected function openInVSCode(string $path): void
    {
        exec('code '.escapeshellarg($path).' > /dev/null 2>&1 &');
    }

    protected function openInCursor(string $path): void
    {
        exec('cursor '.escapeshellarg($path).' > /dev/null 2>&1 &');
    }

    protected function openInITerm(string $path): void
    {
        $script = <<<APPLESCRIPT
        tell application "iTerm"
            activate
            create window with default profile
            tell current session of current window
                write text "cd {$path} && claude"
            end tell
        end tell
        APPLESCRIPT;

        exec('osascript -e '.escapeshellarg($script).' > /dev/null 2>&1 &');
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
