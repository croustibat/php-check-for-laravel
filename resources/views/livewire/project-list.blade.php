<div class="flex-1 p-8 ml-20" wire:poll.30s="refresh">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-white/40 text-sm mb-2">
            <button class="hover:text-white/60 transition-colors">
                &larr; Start Over
            </button>
        </div>

        <h1 class="text-4xl font-bold text-white mb-2">
            Mission Control
        </h1>

        <p class="text-white/60">
            Smart Overview
        </p>
    </div>

    {{-- Status Summary --}}
    <div class="glass p-4 mb-8 inline-block">
        <p class="text-white/80">
            Your sessions are ready. Here's what's happening:
        </p>
    </div>

    {{-- Project Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse ($projects as $project)
            <livewire:project-card :project="$project" :key="'project-' . $project->id" />
        @empty
            <div class="col-span-full text-center py-12">
                <div class="text-4xl mb-4">📁</div>
                <h3 class="text-xl font-semibold text-white mb-2">
                    No Claude Code projects found
                </h3>
                <p class="text-white/60">
                    Start a session with <code class="git-branch">claude</code> in any project
                </p>
            </div>
        @endforelse
    </div>

    {{-- Main Action Button --}}
    @if ($blockedCount > 0)
        <div class="flex justify-center">
            <button class="btn-primary">
                <span>Open All ({{ $blockedCount }} waiting)</span>
                <span>&rarr;</span>
            </button>
        </div>
    @endif
</div>
