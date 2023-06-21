<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class GitVersionHelper
{
    public static function version()
    {
        if (Cache::has('app-version')) {
            return Cache::get('app-version');
        }

        return self::cacheFreshVersion();
    }

    public static function cacheFreshVersion()
    {
        $version = self::freshVersion();
        Cache::put('app-version', $version);

        return $version;
    }

    public static function freshVersion()
    {
        $path = base_path();

        // Get version string from git
        $command = 'git describe --tags $(git rev-list --tags --max-count=1)';

        if (class_exists('\Symfony\Component\Process\Process')) {
            try {
                if (method_exists(Process::class, 'fromShellCommandline')) {
                    $process = Process::fromShellCommandline($command, $path);
                } else {
                    $process = new Process([$command], $path);
                }

                $process->mustRun();
                $output = $process->getOutput();
            } catch (RuntimeException $e) {
                // Do nothing
                $output = null;
            }
        } else {
            // Remember current directory
            $dir = getcwd();

            // Change to base directory
            chdir($path);

            $output = shell_exec($command);

            // Change back
            chdir($dir);
        }

        if (! $output) {
            return str(config('anonaddy.version'));
        }

        return Str::of($output)->after('v')->trim();
    }
}
