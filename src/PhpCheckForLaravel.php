<?php

namespace Croustibat\PhpCheckForLaravel;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class PhpCheckForLaravel
{
    public function getOutdatedPackages(bool $includeDev = false, bool $directOnly = true): Collection
    {
        $cmd = 'composer outdated --format=json';

        if (! $includeDev) {
            $cmd .= ' --no-dev';
        }

        if ($directOnly) {
            $cmd .= ' --direct';
        }

        $result = Process::run($cmd);

        if ($result->failed()) {
            return collect();
        }

        $packagesList = json_decode($result->output(), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($packagesList['installed'])) {
            return collect();
        }

        return collect($packagesList['installed'])->map(function ($package) {
            return [
                'name' => $package['name'],
                'current' => $package['version'],
                'latest' => $package['latest'],
                'semver' => $this->getSemverType($package['latest-status'] ?? 'unknown'),
                'description' => $package['description'] ?? '',
            ];
        });
    }

    public function getSecurityAdvisories(): array
    {
        $result = Process::run('composer audit --format=json');

        if ($result->failed()) {
            return [];
        }

        $data = json_decode($result->output(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $data['advisories'] ?? [];
    }

    public function hasOutdatedPackages(bool $includeDev = false): bool
    {
        return $this->getOutdatedPackages($includeDev)->isNotEmpty();
    }

    public function hasMajorUpdates(bool $includeDev = false): bool
    {
        return $this->getOutdatedPackages($includeDev)
            ->contains(fn ($p) => $p['semver'] === 'major');
    }

    public function hasSecurityIssues(): bool
    {
        return ! empty($this->getSecurityAdvisories());
    }

    private function getSemverType(string $status): string
    {
        return match ($status) {
            'update-possible' => 'major',
            'semver-safe-update' => 'minor',
            default => 'patch',
        };
    }
}
