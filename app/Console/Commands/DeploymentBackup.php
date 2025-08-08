<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeploymentBackup extends Command
{
    protected $signature = 'deployment:backup';

    protected $description = 'Create a timestamped MySQL backup before deployment';

    public function handle(): int
    {
        try {
            // Create backups directory if it doesn't exist
            $backupDir = storage_path('backups');
            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
                $this->info('Created backup directory: '.$backupDir);
            }

            // Generate backup filename with timestamp
            $timestamp = now()->format('Ymd_His');
            $backupFile = $backupDir."/backup_{$timestamp}.sql";

            // Get database configuration
            $connection = config('database.default');
            $database = config("database.connections.{$connection}");

            $host = $database['host'] ?? 'localhost';
            $dbName = $database['database'] ?? '';
            $username = $database['username'] ?? '';
            $password = $database['password'] ?? '';
            $port = $database['port'] ?? 3306;

            if (empty($dbName) || empty($username)) {
                $this->error('Database configuration is incomplete');

                return 1;
            }

            // Build mysqldump command
            $command = sprintf(
                'mysqldump -h %s -P %d -u %s %s %s > %s',
                escapeshellarg($host),
                $port,
                escapeshellarg($username),
                $password ? '-p'.escapeshellarg($password) : '',
                escapeshellarg($dbName),
                escapeshellarg($backupFile)
            );

            $this->info('Creating database backup...');

            // Execute backup command
            $output = [];
            $returnCode = 0;
            exec($command.' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                $this->error('Database backup failed: '.implode("\n", $output));

                return 1;
            }

            // Verify backup file was created and has content
            if (! file_exists($backupFile) || filesize($backupFile) === 0) {
                $this->error('Backup file was not created or is empty');

                return 1;
            }

            $fileSize = round(filesize($backupFile) / 1024, 2);
            $this->info("Database backup created successfully: {$backupFile} ({$fileSize} KB)");

            // Clean up old backups (keep last 7 days)
            $this->cleanupOldBackups($backupDir);

            return 0;

        } catch (\Exception $e) {
            $this->error('Backup failed with exception: '.$e->getMessage());

            return 1;
        }
    }

    private function cleanupOldBackups(string $backupDir): void
    {
        try {
            $files = glob($backupDir.'/backup_*.sql');
            $cutoffTime = now()->subDays(7)->timestamp;
            $deletedCount = 0;

            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                    $deletedCount++;
                }
            }

            if ($deletedCount > 0) {
                $this->info("Cleaned up {$deletedCount} old backup file(s)");
            }

        } catch (\Exception $e) {
            $this->warn('Failed to clean up old backups: '.$e->getMessage());
        }
    }
}
