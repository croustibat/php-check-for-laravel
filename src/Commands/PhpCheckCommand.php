<?php

namespace Croustibat\PhpCheckForLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use function Laravel\Prompts\{multiselect, spin, info, confirm};

class PhpCheckCommand extends Command
{
    public $signature = 'package:check';

    public $description = 'Interactive check for outdated composer package';

    /**
     * @return int
     */
    public function handle(): int
    {
        // main composer command, maybe we can add more later...
        $cmd = [
            'composer outdated --no-dev --direct --format=json'
        ];

        $outdated = spin(
            fn () => collect($cmd)->flatMap(function ($item) {
                $packagesList = json_decode(self::runCmd($item), true);

                if (isset($packagesList['installed'])) {
                    return collect($packagesList['installed'])->flatMap(function ($package) {
                        $semver = match($package['latest-status']) {
                            'update-possible' => 'Major',
                            'semver-safe-update' => 'Minor',
                            default => 'Patch'
                        };
                        $label = $package['name'] . ' ' . $package['version'] . ' -> ' . $package['latest']. ' ('.$semver.')';
                        return [$package['name'].':'.str($package['latest'])->remove('v') => $label];
                    })->toArray();
                }
                return false;
            }),
            '🚀 Checking for outdated packages...'
        );

        $selected = collect(multiselect(
            label: 'Which packages to update ?',
            options: $outdated,
            scroll: 10
        ));

        info("You are going to update: " . $selected->implode(', '));
        $confirmed = confirm('Are you sure?');

        if (!$confirmed) {
            info('❌ Aborting... nothing has been updated');
            return self::SUCCESS;
        }

        if ($confirmed) {
            $cmd = 'composer require -W ' . $selected->implode(' ');
            info($cmd);
            spin(
                fn () => self::runCmd($cmd),
                "Updating ..."
            );
        }

        info('✅ Done!');

        return self::SUCCESS;
    }

    /**
     * @param string $cmd
     * @return string
     */
    public static function runCmd(string $cmd): string
    {
        $result = Process::run($cmd);

        if ($result->failed())
            return $result->errorOutput();

        return $result->output();

    }

}
