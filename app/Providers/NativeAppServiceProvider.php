<?php

declare(strict_types=1);

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\MenuBar;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        MenuBar::create()
            ->route('mission-control')
            ->width(900)
            ->height(700)
            ->resizable(true)
            ->alwaysOnTop(false)
            ->vibrancy('dark')
            ->tooltip('Mission Control - Claude Code Sessions')
            ->withContextMenu(
                Menu::make(
                    Menu::label('Mission Control'),
                    Menu::separator(),
                    Menu::link('https://github.com/croustibat/mission-control', 'GitHub'),
                    Menu::separator(),
                    Menu::quit(),
                )
            );
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [];
    }
}
