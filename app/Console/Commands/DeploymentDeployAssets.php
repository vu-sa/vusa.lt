<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Deploy build assets atomically')]
#[Signature('deployment:deploy-assets')]
class DeploymentDeployAssets extends Command
{
    /**
     * vendor/ is intentionally NOT swapped here. Every artisan invocation boots the
     * full framework — including service providers that reference app classes — before
     * a command's handle() ever runs. If a deploy adds/changes a Composer dependency,
     * new app code would then boot against the still-old vendor/ and fatal with a
     * missing-class error on the very first artisan call (deployment:run itself),
     * before this step ever got a chance to fix it. So vendor.tar.gz is extracted via
     * plain shell in the deploy workflow, after maintenance mode is entered but BEFORE
     * git pull's new app code is ever booted by artisan — see deploy.yml.
     */
    public function handle(): int
    {
        try {
            $baseDir = base_path();

            // Create temporary deployment directory
            $tempDir = $baseDir.'/deployment_temp_'.time();

            $this->info('Creating temporary deployment directory...');
            if (! mkdir($tempDir, 0755, true)) {
                $this->error('Failed to create temporary directory');

                return 1;
            }

            // Extract build artifacts to temporary directory
            $this->deployBuildAssets($tempDir);
            $this->deployDocumentation($tempDir);

            // Clean up temporary directory and archives
            $this->cleanup($tempDir);

            $this->info('Assets deployed successfully');

            return 0;

        } catch (\Exception $e) {
            $this->error('Asset deployment failed: '.$e->getMessage());

            return 1;
        }
    }

    private function deployBuildAssets(string $tempDir): void
    {
        $buildArchive = base_path('build.tar.gz');

        if (! file_exists($buildArchive)) {
            $this->warn('Build archive not found, skipping build assets');

            return;
        }

        $this->info('Deploying build assets...');

        // Extract to temporary directory
        $output = [];
        $returnCode = 0;
        exec("tar -xzf {$buildArchive} -C {$tempDir} 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Failed to extract build assets: '.implode("\n", $output));
        }

        // Atomic replacement of build directory
        $publicDir = base_path('public');
        $buildDir = $publicDir.'/build';
        $tempBuildDir = $tempDir.'/public/build';

        if (is_dir($tempBuildDir)) {
            // Backup existing build directory
            if (is_dir($buildDir)) {
                $buildOldDir = $publicDir.'/build.old';
                if (is_dir($buildOldDir)) {
                    $this->removeDirectory($buildOldDir);
                }
                rename($buildDir, $buildOldDir);
            }

            // Move new build directory into place
            if (! rename($tempBuildDir, $buildDir)) {
                throw new \Exception('Failed to move build assets into place');
            }

            $this->info('Build assets deployed successfully');
        }
    }

    private function deployDocumentation(string $tempDir): void
    {
        $docsArchive = base_path('docs.tar.gz');

        if (! file_exists($docsArchive)) {
            return; // Docs are optional
        }

        $this->info('Deploying documentation...');

        $docsDir = base_path('public/docs');

        // Clear existing docs
        if (is_dir($docsDir)) {
            $this->removeDirectory($docsDir);
            mkdir($docsDir, 0755, true);
        }

        // Extract docs directly to docs directory
        $output = [];
        $returnCode = 0;
        exec("tar -xzf {$docsArchive} -C {$docsDir} 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Failed to extract documentation: '.implode("\n", $output));
        }

        $this->info('Documentation deployed successfully');
    }

    private function cleanup(string $tempDir): void
    {
        // Remove temporary directory
        if (is_dir($tempDir)) {
            $this->removeDirectory($tempDir);
        }

        // Remove uploaded archives (vendor.tar.gz is already swapped in and deleted
        // by the deploy workflow before artisan ever boots — see deploy.yml)
        $archives = ['build.tar.gz', 'docs.tar.gz'];
        foreach ($archives as $archive) {
            $archivePath = base_path($archive);
            if (file_exists($archivePath)) {
                unlink($archivePath);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
