<?php

declare(strict_types=1);

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open()
            ->route('mission-control')
            ->width(1000)
            ->height(750)
            ->minWidth(800)
            ->minHeight(600)
            ->resizable()
            ->title('Mission Control')
            ->titleBarHidden()
            ->transparent()
            ->vibrancy('dark');
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [];
    }
}
