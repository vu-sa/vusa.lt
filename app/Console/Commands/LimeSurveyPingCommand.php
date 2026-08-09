<?php

namespace App\Console\Commands;

use App\Services\LimeSurveyClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Smoke test for the LimeSurvey connection.
 *
 * Answers, in one command, the three things that usually go wrong before any survey code
 * is worth debugging: are the credentials right, is the RPC interface switched on in
 * LimeSurvey's Global settings -> Interfaces, and can this container reach the host.
 */
#[Description('Check that the LimeSurvey RemoteControl API is reachable and the credentials work')]
#[Signature('limesurvey:ping')]
class LimeSurveyPingCommand extends Command
{
    public function handle(LimeSurveyClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('LimeSurvey is not configured.');
            $this->line('Set LIMESURVEY_URL, LIMESURVEY_RPC_USER and LIMESURVEY_RPC_PASSWORD in .env.');

            return self::FAILURE;
        }

        $this->line('Endpoint: '.$client->endpoint());

        $surveys = $client->listSurveys();

        if ($surveys === null) {
            $this->error('Could not list surveys. Check storage/logs/laravel.log for the reason.');
            $this->line('Most common causes: the JSON-RPC interface is disabled in LimeSurvey');
            $this->line('(Global settings -> Interfaces), or the credentials are wrong.');

            return self::FAILURE;
        }

        $this->info(sprintf('Connected. %d survey(s) visible to this user.', count($surveys)));

        if ($surveys !== []) {
            $this->table(
                ['sid', 'title', 'active', 'expires'],
                array_map(fn (array $s): array => [
                    $s['sid'] ?? '?',
                    $s['surveyls_title'] ?? '?',
                    $s['active'] ?? '?',
                    $s['expires'] ?? '-',
                ], array_slice($surveys, 0, 20)),
            );
        }

        return self::SUCCESS;
    }
}
