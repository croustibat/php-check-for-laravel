<aside class="w-16 flex flex-col items-center py-6 gap-2 relative z-10">
    {{-- App Icon --}}
    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-fuchsia-500 to-purple-600
                flex items-center justify-center text-white text-lg font-bold
                shadow-lg mb-6" style="box-shadow: 0 8px 24px rgba(192, 38, 211, 0.4);">
        M
    </div>

    {{-- Nav Items --}}
    <a href="{{ route('mission-control') }}"
        class="w-10 h-10 rounded-xl flex items-center justify-center text-xl {{ $activeItem === 'dashboard' ? 'bg-white/15 shadow-lg' : 'hover:bg-white/10 opacity-60 hover:opacity-100 transition-all' }}"
        title="Dashboard"
    >
        🎛️
    </a>
    <button
        class="w-10 h-10 rounded-xl flex items-center justify-center text-xl {{ $activeItem === 'projects' ? 'bg-white/15 shadow-lg' : 'hover:bg-white/10 opacity-60 hover:opacity-100 transition-all' }}"
        title="Projects"
    >
        📁
    </button>
    <button
        class="w-10 h-10 rounded-xl flex items-center justify-center text-xl {{ $activeItem === 'active' ? 'bg-white/15 shadow-lg' : 'hover:bg-white/10 opacity-60 hover:opacity-100 transition-all' }}"
        title="Active"
    >
        ⚡
    </button>
    <button
        class="w-10 h-10 rounded-xl flex items-center justify-center text-xl {{ $activeItem === 'waiting' ? 'bg-white/15 shadow-lg' : 'hover:bg-white/10 opacity-60 hover:opacity-100 transition-all' }}"
        title="Waiting"
    >
        ⏳
    </button>
    <a href="{{ route('mission-control.statistics') }}"
        class="w-10 h-10 rounded-xl flex items-center justify-center text-xl {{ $activeItem === 'stats' ? 'bg-white/15 shadow-lg' : 'hover:bg-white/10 opacity-60 hover:opacity-100 transition-all' }}"
        title="Stats"
    >
        📊
    </a>
    <button
        class="w-10 h-10 rounded-xl flex items-center justify-center text-xl {{ $activeItem === 'settings' ? 'bg-white/15 shadow-lg' : 'hover:bg-white/10 opacity-60 hover:opacity-100 transition-all' }}"
        title="Settings"
    >
        ⚙️
    </button>

    <div class="flex-1"></div>

    {{-- Blocked Count Badge --}}
    @if ($blockedCount > 0)
        <a href="{{ route('mission-control') }}"
           class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-orange-500
                    flex items-center justify-center text-xs font-bold text-white animate-glow"
           title="{{ $blockedCount }} sessions waiting">
            {{ $blockedCount }}
        </a>
    @endif
</aside>
