<div
    class="project-card relative overflow-hidden rounded-2xl p-5 cursor-pointer glass hover:border-white/20"
    wire:click="select"
    wire:key="project-{{ $project->id }}"
    wire:loading.class="opacity-50"
>
    {{-- Status Gradient Overlay --}}
    @if (in_array($project->status, ['blocked', 'asking_permission']))
        <div class="absolute inset-0 bg-gradient-to-br from-amber-600/40 via-orange-600/30 to-rose-600/20 opacity-60"></div>
    @elseif (in_array($project->status, ['active', 'running']))
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-600/40 via-teal-600/30 to-cyan-600/20 opacity-60"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-br from-slate-600/30 via-slate-700/20 to-slate-800/10 opacity-60"></div>
    @endif

    {{-- Glass Highlight --}}
    <div class="absolute inset-0 bg-gradient-to-b from-white/10 to-transparent opacity-50"></div>

    {{-- Content --}}
    <div class="relative z-10">
        {{-- Header with status dot + name on left, badge on right --}}
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-2">
                @if (in_array($project->status, ['active', 'running']))
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse-dot"></span>
                @elseif (in_array($project->status, ['blocked', 'asking_permission']))
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                @else
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                @endif
                <span class="text-sm font-medium text-white/80">{{ $project->name }}</span>
            </div>

            {{-- Status Badge --}}
            @if (in_array($project->status, ['blocked', 'asking_permission']))
                <span class="px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-amber-500/30 text-amber-300 animate-glow">
                    {{ $project->status === 'asking_permission' ? 'asking' : 'blocked' }}
                </span>
            @elseif ($project->status === 'running')
                <span class="px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-emerald-500/30 text-emerald-300" style="box-shadow: 0 0 20px rgba(16,185,129,0.4);">
                    running
                </span>
            @elseif ($project->status === 'active')
                <span class="px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-blue-500/30 text-blue-300">
                    active
                </span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-slate-500/30 text-slate-400">
                    idle
                </span>
            @endif
        </div>

        {{-- Icon (positioned absolutely) --}}
        <div class="card-icon absolute top-4 right-4 text-5xl opacity-80 drop-shadow-2xl">
            {{ $project->icon }}
        </div>

        {{-- Metric --}}
        <div class="mt-8">
            <div class="text-3xl font-bold text-white tracking-tight">{{ $project->getMetric() }}</div>
            <div class="text-sm text-white/50 mt-1">{{ $project->getMetricLabel() }}</div>
        </div>

        {{-- Session Info --}}
        <div class="mt-4 flex items-center gap-2 flex-wrap">
            @if ($project->command)
                <span class="text-xs font-mono text-white/70 bg-white/10 px-2 py-1 rounded flex items-center gap-1">
                    @if ($project->command === 'opencode')
                        <span class="text-emerald-400">⚡</span>
                    @else
                        <span class="text-purple-400">◆</span>
                    @endif
                    {{ $project->getCommandLabel() }}
                </span>
            @endif
            @if ($project->terminal && $project->terminal !== 'unknown')
                <span class="text-xs font-mono text-white/50 bg-white/5 px-2 py-1 rounded flex items-center gap-1">
                    {{ $project->getTerminalIcon() }} {{ $project->getTerminalLabel() }}
                </span>
            @endif
            @if ($project->gitBranch)
                <span class="text-xs font-mono text-white/40 bg-white/5 px-2 py-1 rounded">
                    <span class="opacity-60">&#9015;</span> {{ $project->gitBranch }}
                </span>
            @endif
            <span class="text-xs text-white/30">{{ $project->getRelativeTime() }}</span>
        </div>

        {{-- Action Button --}}
        <button
            wire:click.stop="open"
            wire:loading.attr="disabled"
            class="mt-4 px-4 py-2 rounded-xl text-sm font-medium bg-white/10 hover:bg-white/20 text-white/80 transition-all border border-white/10 hover:border-white/20"
        >
            <span wire:loading.remove wire:target="open">
                {{ match ($project->status) {
                    'blocked', 'asking_permission' => 'Resume',
                    'active', 'running' => 'View',
                    default => 'Open',
                } }}
            </span>
            <span wire:loading wire:target="open">Opening...</span>
        </button>
    </div>
</div>
