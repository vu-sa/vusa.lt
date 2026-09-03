<?php

namespace App\Console\Commands;

use App\Services\StagingIsolationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Verify staging cannot write to production resources')]
#[Signature('staging:verify-isolation')]
class VerifyStagingIsolation extends Command
{
    public function handle(StagingIsolationService $isolation): int
    {
        if (config('app.env') !== 'staging') {
            $this->info('Not a staging environment; no staging isolation checks are required.');

            return self::SUCCESS;
        }

        $errors = $isolation->errors();

        if ($errors === []) {
            $this->info('Staging isolation configuration is safe.');

            return self::SUCCESS;
        }

        $this->error('Unsafe staging configuration:');

        foreach ($errors as $error) {
            $this->line("  - {$error}");
        }

        return self::FAILURE;
    }
}
