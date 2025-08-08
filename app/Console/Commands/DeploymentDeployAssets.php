<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeploymentDeployAssets extends Command
{
    protected $signature = 'deployment:deploy-assets';

    protected $description = 'Deploy build assets and vendor files atomically';

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
            $this->deployVendorFiles($tempDir);
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

    private function deployVendorFiles(string $tempDir): void
    {
        $vendorArchive = base_path('vendor.tar.gz');

        if (! file_exists($vendorArchive)) {
            $this->warn('Vendor archive not found, skipping vendor files');

            return;
        }

        $this->info('Deploying vendor files...');

        // Extract to temporary directory
        $output = [];
        $returnCode = 0;
        exec("tar -xzf {$vendorArchive} -C {$tempDir} 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Failed to extract vendor files: '.implode("\n", $output));
        }

        // Atomic replacement of vendor directory
        $vendorDir = base_path('vendor');
        $tempVendorDir = $tempDir.'/vendor';

        if (is_dir($tempVendorDir)) {
            // Backup existing vendor directory
            if (is_dir($vendorDir)) {
                $vendorOldDir = base_path('vendor.old');
                if (is_dir($vendorOldDir)) {
                    $this->removeDirectory($vendorOldDir);
                }
                rename($vendorDir, $vendorOldDir);
            }

            // Move new vendor directory into place
            if (! rename($tempVendorDir, $vendorDir)) {
                throw new \Exception('Failed to move vendor files into place');
            }

            $this->info('Vendor files deployed successfully');
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

        // Remove uploaded archives
        $archives = ['build.tar.gz', 'vendor.tar.gz', 'docs.tar.gz'];
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
