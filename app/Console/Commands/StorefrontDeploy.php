<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Builds the SvelteKit storefront and publishes the static output into the
 * Laravel public/ directory. After this Laravel serves the storefront itself —
 * no nginx changes are needed on deploy.
 */
class StorefrontDeploy extends Command
{
    protected $signature = 'storefront:deploy {--skip-build : Use the existing build/ output}';

    protected $description = 'Build the Svelte storefront and publish it to public/storefront';

    public function handle(): int
    {
        $frontend = base_path('resources/frontend');
        $build = $frontend.'/build';

        if (! $this->option('skip-build')) {
            if (! is_dir($frontend)) {
                $this->error('resources/frontend not found.');

                return self::FAILURE;
            }

            $this->info('Building storefront…');
            $result = Process::path($frontend)->timeout(600)->run('npm run build');

            if (! $result->successful()) {
                $this->error($result->errorOutput() ?: $result->output());

                return self::FAILURE;
            }
        }

        if (! is_file($build.'/index.html')) {
            $this->error('build/index.html not found.');

            return self::FAILURE;
        }

        // _app + assets are referenced with absolute paths → serve from public root.
        foreach (['_app', 'assets'] as $dir) {
            File::deleteDirectory(public_path($dir));
            if (is_dir($build.'/'.$dir)) {
                File::copyDirectory($build.'/'.$dir, public_path($dir));
            }
        }

        File::ensureDirectoryExists(public_path('storefront'));
        File::copy($build.'/index.html', public_path('storefront/index.html'));

        // Small root files the SPA ships (robots.txt, favicon, …) that do not
        // collide with Laravel's own public/ files.
        foreach (File::files($build) as $file) {
            $name = $file->getFilename();
            if (in_array($name, ['index.html', 'index.htm'], true)) {
                continue;
            }
            if (! File::exists(public_path($name))) {
                File::copy($file->getPathname(), public_path($name));
            }
        }

        $this->info('Storefront published to public/storefront.');

        return self::SUCCESS;
    }
}
