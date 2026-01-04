<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScanDirectory extends Model
{
    protected $fillable = [
        'path',
        'is_enabled',
        'last_scanned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_scanned_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<ScanDirectory>  $query
     * @return Builder<ScanDirectory>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function exists(): bool
    {
        return is_dir($this->path);
    }

    public function getExpandedPath(): string
    {
        $path = $this->path;

        if (str_starts_with($path, '~')) {
            $path = ($_SERVER['HOME'] ?? '/tmp').substr($path, 1);
        }

        return $path;
    }

    public function markAsScanned(): void
    {
        $this->update(['last_scanned_at' => now()]);
    }

    public function toggle(): void
    {
        $this->update(['is_enabled' => ! $this->is_enabled]);
    }
}
