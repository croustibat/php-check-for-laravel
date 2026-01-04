<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            [
                'name' => 'Cursor',
                'slug' => 'cursor',
                'type' => 'ide',
                'app_path' => '/Applications/Cursor.app',
                'cli_command' => 'cursor',
                'icon' => '🖱️',
            ],
            [
                'name' => 'VS Code',
                'slug' => 'vscode',
                'type' => 'ide',
                'app_path' => '/Applications/Visual Studio Code.app',
                'cli_command' => 'code',
                'icon' => '💻',
            ],
            [
                'name' => 'Zed',
                'slug' => 'zed',
                'type' => 'ide',
                'app_path' => '/Applications/Zed.app',
                'cli_command' => 'zed',
                'icon' => '⚡',
            ],
            [
                'name' => 'PhpStorm',
                'slug' => 'phpstorm',
                'type' => 'ide',
                'app_path' => '/Applications/PhpStorm.app',
                'cli_command' => 'phpstorm',
                'icon' => '🐘',
            ],
            [
                'name' => 'Sublime Text',
                'slug' => 'sublime',
                'type' => 'ide',
                'app_path' => '/Applications/Sublime Text.app',
                'cli_command' => 'subl',
                'icon' => '📝',
            ],

            [
                'name' => 'Warp',
                'slug' => 'warp',
                'type' => 'terminal',
                'app_path' => '/Applications/Warp.app',
                'cli_command' => null,
                'icon' => '⚡',
            ],
            [
                'name' => 'iTerm',
                'slug' => 'iterm',
                'type' => 'terminal',
                'app_path' => '/Applications/iTerm.app',
                'cli_command' => null,
                'icon' => '🖥️',
            ],
            [
                'name' => 'Terminal',
                'slug' => 'terminal',
                'type' => 'terminal',
                'app_path' => '/System/Applications/Utilities/Terminal.app',
                'cli_command' => null,
                'icon' => '⬛',
            ],
            [
                'name' => 'Ghostty',
                'slug' => 'ghostty',
                'type' => 'terminal',
                'app_path' => '/Applications/Ghostty.app',
                'cli_command' => null,
                'icon' => '👻',
            ],

            [
                'name' => 'Claude',
                'slug' => 'claude',
                'type' => 'ai_cli',
                'app_path' => null,
                'cli_command' => 'claude',
                'icon' => '◆',
            ],
            [
                'name' => 'OpenCode',
                'slug' => 'opencode',
                'type' => 'ai_cli',
                'app_path' => null,
                'cli_command' => 'opencode',
                'icon' => '⚡',
            ],
            [
                'name' => 'Codex',
                'slug' => 'codex',
                'type' => 'ai_cli',
                'app_path' => null,
                'cli_command' => 'codex',
                'icon' => '🔮',
            ],
            [
                'name' => 'Gemini CLI',
                'slug' => 'gemini',
                'type' => 'ai_cli',
                'app_path' => null,
                'cli_command' => 'gemini',
                'icon' => '✨',
            ],
            [
                'name' => 'Aider',
                'slug' => 'aider',
                'type' => 'ai_cli',
                'app_path' => null,
                'cli_command' => 'aider',
                'icon' => '🤖',
            ],
        ];

        foreach ($tools as $toolData) {
            $isInstalled = $this->checkIfInstalled($toolData);

            Tool::updateOrCreate(
                ['slug' => $toolData['slug']],
                array_merge($toolData, ['is_installed' => $isInstalled])
            );
        }
    }

    /**
     * @param  array<string, mixed>  $toolData
     */
    private function checkIfInstalled(array $toolData): bool
    {
        if ($toolData['app_path'] && is_dir($toolData['app_path'])) {
            return true;
        }

        if ($toolData['cli_command']) {
            $path = trim(shell_exec("which {$toolData['cli_command']} 2>/dev/null") ?? '');

            return ! empty($path);
        }

        return false;
    }
}
