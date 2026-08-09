<?php

namespace App\Console\Commands;

use App\Models\Survey;
use App\Services\LimeSurveyLssBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Dump the .lss document that would be sent to LimeSurvey for a given survey.
 *
 * This separates the two things that can go wrong when a publish fails. Import the dumped
 * file through the LimeSurvey web UI: if it imports cleanly the XML is right and the
 * problem is in the API call; if it does not, the problem is in the generated document and
 * LimeSurvey will say exactly which part it disliked.
 */
#[Description('Write the generated LimeSurvey .lss document for a survey to a file')]
#[Signature('limesurvey:build-lss
                            {survey : Survey ULID}
                            {--out= : Where to write the file (defaults to storage/app/limesurvey/{id}.lss)}')]
class LimeSurveyBuildLssCommand extends Command
{
    public function handle(LimeSurveyLssBuilder $builder): int
    {
        $survey = Survey::with('questions')->find($this->argument('survey'));

        if ($survey === null) {
            $this->error('No survey with that id.');

            return self::FAILURE;
        }

        if ($survey->questions->isEmpty()) {
            $this->warn('This survey has no questions — the document will contain no question rows.');
        }

        $path = $this->option('out') ?: storage_path('app/limesurvey/'.$survey->id.'.lss');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $builder->build($survey));

        $this->info('Wrote '.$path);
        $this->line(sprintf('%d question(s). Import it via LimeSurvey -> Surveys -> Import to verify.', $survey->questions->count()));

        return self::SUCCESS;
    }
}
