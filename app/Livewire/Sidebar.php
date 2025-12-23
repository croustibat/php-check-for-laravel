<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    public int $blockedCount = 0;

    public string $activeItem = 'dashboard';

    #[On('blockedCountUpdated')]
    public function updateBlockedCount(int $count): void
    {
        $this->blockedCount = $count;
    }

    public function render()
    {
        return view('livewire.sidebar');
    }
}
