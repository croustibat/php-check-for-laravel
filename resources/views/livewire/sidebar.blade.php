<aside class="glass fixed left-4 top-1/2 -translate-y-1/2 p-3 flex flex-col items-center gap-4 z-50">
    {{-- App Icon --}}
    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-fuchsia-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg shadow-lg mb-2">
        M
    </div>

    {{-- Nav Items --}}
    <nav class="flex flex-col gap-2">
        <button
            class="sidebar-item {{ $activeItem === 'dashboard' ? 'sidebar-item-active' : '' }}"
            title="Dashboard"
        >
            <span>{{ config('mission-control.icons.dashboard', '🎛️') }}</span>
        </button>

        <button
            class="sidebar-item {{ $activeItem === 'projects' ? 'sidebar-item-active' : '' }}"
            title="Projects"
        >
            <span>{{ config('mission-control.icons.projects', '📁') }}</span>
        </button>

        <button
            class="sidebar-item {{ $activeItem === 'active' ? 'sidebar-item-active' : '' }}"
            title="Active Sessions"
        >
            <span>{{ config('mission-control.icons.active', '⚡') }}</span>
        </button>

        <button
            class="sidebar-item {{ $activeItem === 'history' ? 'sidebar-item-active' : '' }}"
            title="History"
        >
            <span>{{ config('mission-control.icons.history', '⏳') }}</span>
        </button>

        <button
            class="sidebar-item {{ $activeItem === 'stats' ? 'sidebar-item-active' : '' }}"
            title="Statistics"
        >
            <span>{{ config('mission-control.icons.stats', '📊') }}</span>
        </button>

        <button
            class="sidebar-item {{ $activeItem === 'settings' ? 'sidebar-item-active' : '' }}"
            title="Settings"
        >
            <span>{{ config('mission-control.icons.settings', '⚙️') }}</span>
        </button>
    </nav>

    {{-- Blocked Count Badge --}}
    @if ($blockedCount > 0)
        <div class="mt-auto pt-4">
            <div class="notification-badge" title="{{ $blockedCount }} sessions waiting">
                {{ $blockedCount }}
            </div>
        </div>
    @endif
</aside>
