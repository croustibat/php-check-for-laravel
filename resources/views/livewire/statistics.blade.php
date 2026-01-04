<div class="flex-1 p-6 overflow-auto relative z-10">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('mission-control') }}" class="text-white/50 hover:text-white/80 text-sm flex items-center gap-2 mb-2 transition-colors">
                &#8249; Dashboard
            </a>
            <h1 class="text-xl font-semibold text-white">Statistics</h1>
        </div>
        <div class="text-white/60 text-sm">
            Usage Analytics
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {{-- Today --}}
        <div class="glass p-4 rounded-2xl">
            <div class="text-white/50 text-sm mb-1">Today</div>
            <div class="text-2xl font-bold text-white">{{ number_format($todayMessages) }}</div>
            <div class="text-white/40 text-xs">messages</div>
        </div>

        {{-- This Week --}}
        <div class="glass p-4 rounded-2xl">
            <div class="text-white/50 text-sm mb-1">This Week</div>
            <div class="text-2xl font-bold text-white">{{ number_format($weeklyMessages) }}</div>
            <div class="text-white/40 text-xs">messages</div>
        </div>

        {{-- Total Sessions --}}
        <div class="glass p-4 rounded-2xl">
            <div class="text-white/50 text-sm mb-1">Total Sessions</div>
            <div class="text-2xl font-bold text-white">{{ number_format($totalSessions) }}</div>
            <div class="text-white/40 text-xs">since {{ $firstSessionDate ? \Carbon\Carbon::parse($firstSessionDate)->format('M j') : 'N/A' }}</div>
        </div>

        {{-- Total Tokens --}}
        <div class="glass p-4 rounded-2xl">
            <div class="text-white/50 text-sm mb-1">Total Tokens</div>
            <div class="text-2xl font-bold text-white">{{ $totalTokens }}</div>
            <div class="text-white/40 text-xs">output tokens</div>
        </div>
    </div>

    {{-- Model Usage --}}
    <div class="glass p-6 rounded-2xl mb-8">
        <h2 class="text-lg font-semibold text-white mb-4">Model Usage</h2>
        <div class="space-y-4">
            @foreach ($modelUsage as $model => $usage)
                @php
                    $modelName = match (true) {
                        str_contains($model, 'opus') => 'Claude Opus 4.5',
                        str_contains($model, 'sonnet') => 'Claude Sonnet 4.5',
                        str_contains($model, 'haiku') => 'Claude Haiku 4.5',
                        default => $model,
                    };
                    $modelColor = match (true) {
                        str_contains($model, 'opus') => 'from-purple-500 to-fuchsia-500',
                        str_contains($model, 'sonnet') => 'from-blue-500 to-cyan-500',
                        str_contains($model, 'haiku') => 'from-green-500 to-emerald-500',
                        default => 'from-gray-500 to-slate-500',
                    };
                    $outputTokens = $usage['outputTokens'] ?? 0;
                    $inputTokens = $usage['inputTokens'] ?? 0;
                    $totalTokens = $outputTokens + $inputTokens;
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-white/80 text-sm">{{ $modelName }}</span>
                        <span class="text-white/50 text-xs">{{ number_format($totalTokens) }} tokens</span>
                    </div>
                    <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r {{ $modelColor }} rounded-full"
                             style="width: {{ min(100, ($totalTokens / max(array_sum(array_map(fn($u) => ($u['outputTokens'] ?? 0) + ($u['inputTokens'] ?? 0), $modelUsage)), 1)) * 100) }}%">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Daily Activity Chart --}}
    <div class="glass p-6 rounded-2xl mb-8">
        <h2 class="text-lg font-semibold text-white mb-4">Daily Activity (Last 14 Days)</h2>
        <div class="flex items-end justify-between gap-2 h-32">
            @foreach ($dailyActivity as $day)
                @php
                    $maxMessages = max(array_column($dailyActivity, 'messageCount'));
                    $height = $maxMessages > 0 ? ($day['messageCount'] / $maxMessages) * 100 : 0;
                @endphp
                <div class="flex-1 flex flex-col items-center gap-1 group">
                    <div class="w-full bg-gradient-to-t from-fuchsia-500 to-purple-500 rounded-t opacity-80 group-hover:opacity-100 transition-opacity"
                         style="height: {{ max(4, $height) }}%"
                         title="{{ $day['date'] }}: {{ number_format($day['messageCount']) }} messages">
                    </div>
                    <span class="text-[10px] text-white/30 group-hover:text-white/60 transition-colors">
                        {{ \Carbon\Carbon::parse($day['date'])->format('d') }}
                    </span>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between mt-2 text-xs text-white/40">
            <span>{{ count($dailyActivity) > 0 ? \Carbon\Carbon::parse(end($dailyActivity)['date'])->format('M j') : '' }}</span>
            <span>{{ count($dailyActivity) > 0 ? \Carbon\Carbon::parse($dailyActivity[0]['date'])->format('M j') : '' }}</span>
        </div>
    </div>

    {{-- Hourly Distribution --}}
    <div class="glass p-6 rounded-2xl">
        <h2 class="text-lg font-semibold text-white mb-4">Activity by Hour</h2>
        <div class="flex items-end justify-between gap-1 h-24">
            @for ($hour = 0; $hour < 24; $hour++)
                @php
                    $count = $hourlyDistribution[$hour] ?? 0;
                    $maxCount = max($hourlyDistribution ?: [1]);
                    $height = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                @endphp
                <div class="flex-1 flex flex-col items-center gap-1 group">
                    <div class="w-full bg-gradient-to-t from-emerald-500 to-teal-500 rounded-t opacity-60 group-hover:opacity-100 transition-opacity"
                         style="height: {{ max(2, $height) }}%"
                         title="{{ $hour }}:00 - {{ number_format($count) }} sessions">
                    </div>
                </div>
            @endfor
        </div>
        <div class="flex justify-between mt-2 text-xs text-white/40">
            <span>00:00</span>
            <span>12:00</span>
            <span>23:00</span>
        </div>
    </div>
</div>
