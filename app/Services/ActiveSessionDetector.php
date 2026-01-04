<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

class ActiveSessionDetector
{
    /**
     * @var array<int, string>
     */
    private array $sessionCommands = ['opencode', 'claude'];

    /**
     * @var array<string, string>
     */
    private array $terminalPatterns = [
        'Warp.app' => 'warp',
        'Zed.app' => 'zed',
        'zed' => 'zed',
        'Cursor.app' => 'cursor',
        'Code.app' => 'vscode',
        'Code Helper' => 'vscode',
        'iTerm' => 'iterm',
        'Terminal.app' => 'terminal',
    ];

    /**
     * @return Collection<int, array{pid: int, tty: string, cwd: string, terminal: string, status: string, command: string}>
     */
    public function getActiveSessions(): Collection
    {
        $pids = $this->findSessionPids();

        if ($pids->isEmpty()) {
            return collect();
        }

        return $pids->map(function ($pid) {
            $details = $this->getProcessDetails($pid);

            if (! $details['cwd']) {
                return null;
            }

            return [
                'pid' => $pid,
                'tty' => $details['tty'],
                'cwd' => $details['cwd'],
                'terminal' => $this->detectTerminalFromTty($details['tty']),
                'status' => $this->detectSessionStatus($pid),
                'command' => $details['command'],
            ];
        })->filter()->values();
    }

    /**
     * @return Collection<int, int>
     */
    protected function findSessionPids(): Collection
    {
        $pids = collect();

        foreach ($this->sessionCommands as $command) {
            $output = shell_exec("pgrep -f '^{$command}' 2>/dev/null");

            if ($output) {
                $foundPids = array_filter(array_map('intval', explode("\n", trim($output))));
                $pids = $pids->merge($foundPids);
            }
        }

        return $pids->unique()->values();
    }

    public function isProjectActive(string $projectPath): bool
    {
        return $this->getActiveSessions()
            ->contains(fn ($session) => $session['cwd'] === $projectPath);
    }

    /**
     * @return array{pid: int, tty: string, cwd: string, terminal: string, status: string, command: string}|null
     */
    public function getSessionForProject(string $projectPath): ?array
    {
        return $this->getActiveSessions()
            ->first(fn ($session) => $session['cwd'] === $projectPath);
    }

    /**
     * @return array{tty: string, cwd: string|null, ppid: int|null, command: string}
     */
    protected function getProcessDetails(int $pid): array
    {
        $psOutput = shell_exec("ps -o tty=,comm= -p {$pid} 2>/dev/null") ?: '';
        $parts = preg_split('/\s+/', trim($psOutput), 2);

        $tty = $parts[0] ?? '';
        $command = $parts[1] ?? 'unknown';

        $cwd = trim(shell_exec("lsof -p {$pid} 2>/dev/null | grep cwd | awk '{print \$9}'") ?: '');
        $ppid = (int) trim(shell_exec("ps -o ppid= -p {$pid} 2>/dev/null") ?: '0');

        return [
            'tty' => $tty,
            'cwd' => $cwd ?: null,
            'ppid' => $ppid ?: null,
            'command' => $command,
        ];
    }

    protected function detectTerminalFromTty(string $tty): string
    {
        if (empty($tty) || $tty === '??') {
            return 'unknown';
        }

        $normalizedTty = ltrim($tty, 'ttys');
        $shellPpid = $this->getShellParentPid($normalizedTty);

        if ($shellPpid) {
            $parentComm = trim(shell_exec("ps -o comm= -p {$shellPpid} 2>/dev/null") ?: '');

            foreach ($this->terminalPatterns as $pattern => $terminal) {
                if (str_contains($parentComm, $pattern)) {
                    return $terminal;
                }
            }
        }

        return $this->detectFromRunningTerminals();
    }

    protected function getShellParentPid(string $ttyNumber): ?int
    {
        $output = shell_exec("ps -t ttys{$ttyNumber} -o pid,ppid,comm 2>/dev/null") ?: '';
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (preg_match('/(zsh|bash|sh|fish)/', $line)) {
                $parts = preg_split('/\s+/', trim($line));
                if (isset($parts[1])) {
                    return (int) $parts[1];
                }
            }
        }

        return null;
    }

    protected function detectFromRunningTerminals(): string
    {
        $terminals = [
            ['pattern' => 'Warp.app', 'name' => 'warp'],
            ['pattern' => 'Zed.app', 'name' => 'zed'],
            ['pattern' => 'Cursor.app', 'name' => 'cursor'],
            ['pattern' => 'Code.app', 'name' => 'vscode'],
            ['pattern' => 'iTerm', 'name' => 'iterm'],
            ['pattern' => 'Terminal.app', 'name' => 'terminal'],
        ];

        foreach ($terminals as $terminal) {
            $check = shell_exec("pgrep -f '{$terminal['pattern']}' 2>/dev/null");
            if (! empty(trim($check ?: ''))) {
                return $terminal['name'];
            }
        }

        return 'terminal';
    }

    protected function detectSessionStatus(int $pid): string
    {
        $cpu = (float) trim(shell_exec("ps -o %cpu= -p {$pid} 2>/dev/null") ?: '0');

        if ($cpu > 5) {
            return 'running';
        }

        $cwd = trim(shell_exec("lsof -p {$pid} 2>/dev/null | grep cwd | awk '{print \$9}'") ?: '');
        if ($cwd) {
            $status = $this->checkSessionFileForStatus($cwd);
            if ($status) {
                return $status;
            }
        }

        return $cpu > 0.5 ? 'running' : 'active';
    }

    protected function checkSessionFileForStatus(string $projectPath): ?string
    {
        $claudePath = ($_SERVER['HOME'] ?? '/tmp').'/.claude/projects';
        $encodedPath = str_replace('/', '-', ltrim($projectPath, '/'));
        $sessionDir = $claudePath.'/'.$encodedPath;

        if (! is_dir($sessionDir)) {
            return null;
        }

        $files = glob($sessionDir.'/*.jsonl');
        if (empty($files)) {
            return null;
        }

        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));
        $latestFile = $files[0];

        $lines = $this->getLastLines($latestFile, 5);

        foreach (array_reverse($lines) as $line) {
            $data = json_decode($line, true);
            if (! $data) {
                continue;
            }

            if (isset($data['type']) && $data['type'] === 'ask') {
                return 'asking_permission';
            }

            if (isset($data['message']['content'])) {
                $content = is_array($data['message']['content'])
                    ? json_encode($data['message']['content'])
                    : $data['message']['content'];

                if (preg_match('/\?\s*$/m', $content)) {
                    return 'asking_permission';
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function getLastLines(string $file, int $count): array
    {
        $lines = [];
        $handle = fopen($file, 'r');
        if (! $handle) {
            return $lines;
        }

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if (! empty($trimmed)) {
                $lines[] = $trimmed;
                if (count($lines) > $count) {
                    array_shift($lines);
                }
            }
        }
        fclose($handle);

        return $lines;
    }
}
