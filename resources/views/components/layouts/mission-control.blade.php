<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Mission Control' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="mc-background antialiased text-white">
    {{-- Noise Overlay --}}
    <div class="noise-overlay"></div>

    {{-- Main Layout --}}
    <div class="min-h-screen flex">
        <livewire:sidebar />

        {{ $slot }}
    </div>

    {{-- Bottom Blur Effect --}}
    <div class="bottom-blur"></div>

    @livewireScripts
</body>
</html>
