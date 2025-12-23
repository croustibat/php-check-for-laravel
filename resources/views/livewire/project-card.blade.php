<div
    class="project-card"
    wire:click="select"
    wire:key="project-{{ $project->id }}"
    wire:loading.class="opacity-50"
>
    {{-- Status Gradient Overlay --}}
    <div class="status-gradient-{{ $project->status }} rounded-2xl"></div>

    {{-- Glass Highlight --}}
    <div class="absolute inset-0 bg-gradient-to-b from-white/10 to-transparent opacity-50 pointer-events-none rounded-2xl"></div>

    {{-- Content --}}
    <div class="relative z-10">
        {{-- Header --}}
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0 pr-12">
                <h3 class="text-lg font-semibold text-white truncate">{{ $project->name }}</h3>
                <span class="status-badge status-badge-{{ $project->status }} inline-flex items-center gap-1.5 mt-1">
                    @if ($project->status === 'active')
                        <span class="status-dot status-dot-active"></span>
                    @endif
                    {{ $project->status }}
                </span>
            </div>

            {{-- Icon --}}
            <div class="card-icon">
                {{ $project->icon }}
            </div>
        </div>

        {{-- Metric --}}
        <div class="mb-4">
            <div class="text-2xl font-bold text-white">{{ $project->getMetric() }}</div>
            <div class="text-sm text-white/60">{{ $project->getMetricLabel() }}</div>
        </div>

        {{-- Git Info --}}
        <div class="flex items-center gap-3 mb-4 text-sm flex-wrap">
            @if ($project->gitBranch)
                <span class="git-branch">
                    <span class="opacity-60">⎇</span> {{ $project->gitBranch }}
                </span>
            @endif
            <span class="text-white/40">{{ $project->getRelativeTime() }}</span>
        </div>

        {{-- Action Button --}}
        <button
            wire:click.stop="open"
            wire:loading.attr="disabled"
            class="btn-card w-full flex items-center justify-center gap-2"
        >
            <span wire:loading.remove wire:target="open">
                {{ match ($project->status) {
                    'blocked' => 'Resume',
                    'active' => 'View',
                    default => 'Open',
                } }}
            </span>
            <span wire:loading wire:target="open">Opening...</span>
        </button>
    </div>
</div>
