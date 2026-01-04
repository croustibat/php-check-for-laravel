<div class="flex-1 p-6 overflow-auto relative z-10" wire:poll.30s="refresh">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <button class="text-white/50 hover:text-white/80 text-sm flex items-center gap-2 mb-2 transition-colors">
                &#8249; Start Over
            </button>
            <h1 class="text-xl font-semibold text-white">Mission Control</h1>
        </div>
        <div class="text-white/60 text-sm">
            Smart Overview
        </div>
    </div>

    {{-- Status Summary --}}
    <div class="text-center mb-6">
        <h2 class="text-lg text-white/90 font-medium">
            Your sessions are ready. Here's what's happening:
        </h2>
    </div>

    {{-- Project Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @forelse ($projects as $project)
            <livewire:project-card :project="$project" :key="'project-' . $project->id" />
        @empty
            <div class="col-span-full text-center py-12">
                <div class="text-4xl mb-4">📁</div>
                <h3 class="text-xl font-semibold text-white mb-2">
                    No Claude Code projects found
                </h3>
                <p class="text-white/60">
                    Start a session with <code class="text-xs font-mono text-white/40 bg-white/5 px-2 py-1 rounded">claude</code> in any project
                </p>
            </div>
        @endforelse
    </div>

    {{-- Main Action Button --}}
    @if ($blockedCount > 0)
        <div class="flex justify-center pb-8">
            <button
                wire:click="openAllBlocked"
                class="px-10 py-4 rounded-full text-lg font-semibold text-white
                       bg-gradient-to-r from-fuchsia-500 via-purple-500 to-fuchsia-500
                       animate-gradient
                       hover:scale-105 transition-all duration-300
                       flex items-center gap-3"
                style="box-shadow: 0 0 40px rgba(217,70,239,0.5);"
            >
                <span class="w-3 h-3 rounded-full bg-white animate-pulse"></span>
                Open All ({{ $blockedCount }} waiting)
            </button>
        </div>
    @endif
</div>
