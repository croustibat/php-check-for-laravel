<?php

declare(strict_types=1);

use App\Livewire\Sidebar;
use Livewire\Livewire;

test('sidebar component renders', function () {
    Livewire::test(Sidebar::class)
        ->assertStatus(200)
        ->assertSee('M');
});

test('sidebar updates blocked count on event', function () {
    Livewire::test(Sidebar::class)
        ->assertSet('blockedCount', 0)
        ->dispatch('blockedCountUpdated', count: 3)
        ->assertSet('blockedCount', 3);
});

test('sidebar shows notification badge when blocked count is greater than zero', function () {
    Livewire::test(Sidebar::class)
        ->set('blockedCount', 2)
        ->assertSee('2');
});

test('sidebar hides notification badge when blocked count is zero', function () {
    Livewire::test(Sidebar::class)
        ->set('blockedCount', 0)
        ->assertDontSee('notification-badge');
});
