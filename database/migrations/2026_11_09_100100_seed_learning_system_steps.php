<?php

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\ResponsibilityPurpose;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * System steps come from the registry; starter steps that reps tick themselves are regular, editable
 * tasks. Both are keyed, so a rerun inserts only what is missing.
 */
return new class extends Migration
{
    private const array REP_PURPOSES = [ResponsibilityPurpose::StudentRepresentation->value, ResponsibilityPurpose::StudentRepCoordination->value];

    public function up(): void
    {
        foreach (SystemStepRegistry::definitions() as $key => $definition) {
            $id = (string) Str::ulid();
            $inserted = DB::table('learning_steps')->insertOrIgnore([
                'id' => $id, 'key' => $key, 'is_system' => true,
                'kind' => 'action',
                'per_term' => $definition['per_term'], 'order' => 0, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($inserted) {
                foreach ($definition['audience'] === LearningAudienceType::Everyone ? [null] : self::REP_PURPOSES as $source) {
                    $this->insertAudience($id, $source, $definition['importance'], $definition['stage']);
                }
            }
        }

        foreach ($this->starterSteps() as $key => $step) {
            $id = (string) Str::ulid();
            $inserted = DB::table('learning_steps')->insertOrIgnore([
                'id' => $id, 'key' => $key, 'is_system' => false, 'kind' => 'action',
                'title' => json_encode($step['title']), 'description' => json_encode(array_map(fn (string $text): string => '<p>'.e($text).'</p>', $step['description'])),
                'action_label' => isset($step['action']) ? json_encode($step['action']) : null,
                'action_url' => isset($step['url']) ? json_encode(['lt' => $step['url']]) : null,
                'per_term' => $step['per_term'] ?? false, 'order' => 0, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($inserted) {
                foreach (($step['reps'] ?? false) ? self::REP_PURPOSES : [null] as $purpose) {
                    $this->insertAudience($id, $purpose, $step['importance'], $step['stage'] ?? LearningStage::BeforeStart);
                }
            }
        }
    }

    public function down(): void
    {
        // Authored progress must survive a rollback of the seed.
    }

    private function insertAudience(string $stepId, ?string $purpose, LearningImportance $importance, LearningStage $stage): void
    {
        DB::table('learning_step_audiences')->insert([
            'id' => (string) Str::ulid(), 'learning_step_id' => $stepId,
            'type' => $purpose === null ? 'everyone' : 'purpose', 'source_id' => $purpose,
            'importance' => $importance->value, 'stage' => $stage->value,
            'order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array<string, array{importance: LearningImportance, stage?: LearningStage, reps?: bool, per_term?: bool, url?: string, title: array{lt: string, en: string}, description: array{lt: string, en: string}, action?: array{lt: string, en: string}}> */
    private function starterSteps(): array
    {
        return [
            'check_access' => [
                'importance' => LearningImportance::Essential, 'url' => '/mano/profile/roles',
                'title' => ['lt' => 'Patikrink savo pareigas ir prieigą', 'en' => 'Check your duties and access'],
                'description' => [
                    'lt' => 'Patikrink institucijas, pareigas, jų datas ir prieinamas skiltis. Jei ko nors trūksta ar matai klaidą, pranešk administratoriui.',
                    'en' => 'Check your institutions, duties, dates and available sections. Report anything missing or incorrect to an administrator.',
                ],
                'action' => ['lt' => 'Peržiūrėti pareigas', 'en' => 'Review duties'],
            ],
            'review_contacts' => [
                'importance' => LearningImportance::Recommended, 'url' => '/mano/profile',
                'title' => ['lt' => 'Peržiūrėk savo kontaktinius duomenis', 'en' => 'Review your contact details'],
                'description' => [
                    'lt' => 'Paskyroje gali pataisyti kontaktus, nuotrauką ir kreipinį. Prisijungimo el. paštas čia nekeičiamas, o vardą gali pataisyti tik vieną kartą. Taip pat patikrink nuotrauką – ją kartais jau būna įkėlęs atstovų koordinatorius. Nuotrauka ir telefono numeris šiam žingsniui neprivalomi.',
                    'en' => 'Account settings let you update your contacts, photo and form of address. Your login email cannot be changed here, and your name can only be corrected once. Also check your photo – your representative coordinator may have already uploaded one. A photo and phone number are optional.',
                ],
                'action' => ['lt' => 'Atverti paskyrą', 'en' => 'Open account'],
            ],
            'notification_settings' => [
                'importance' => LearningImportance::Recommended, 'url' => '/mano/profile/notifications',
                'title' => ['lt' => 'Pasirink, kaip gauti pranešimus', 'en' => 'Choose how to receive notifications'],
                'description' => [
                    'lt' => 'Patikrink, kuriais el. pašto adresais gausi laiškus, pasirink suvestinės dažnumą ir svarbias pranešimų rūšis. Numatytuosius nustatymus gali palikti. Jei nori, nustatymuose išsiųsk bandomąjį laišką.',
                    'en' => 'Check which email addresses receive notifications, the digest frequency and the notification types you need. You can keep the defaults. You can also send a test email from the settings page.',
                ],
                'action' => ['lt' => 'Nustatyti pranešimus', 'en' => 'Set notifications'],
            ],
            'orientation' => [
                'importance' => LearningImportance::Recommended, 'url' => '/mano/tasks',
                'title' => ['lt' => 'Sužinok, nuo ko pradėti', 'en' => 'Learn where to start'],
                'description' => [
                    'lt' => 'Pradžioje pirmiausia matysi darbus, kuriems reikia tavo dėmesio. Darbo sričių meniu rodo tik tau prieinamas skiltis. Pagalba, gidas ir interaktyvus turas pasiekiami paskyros meniu.',
                    'en' => 'Home shows work needing your attention first. The workspace menu lists sections you can access. Help, the guide and the interactive tour are available through your account menu.',
                ],
                'action' => ['lt' => 'Atverti užduotis', 'en' => 'Open tasks'],
            ],
            'write_handover' => [
                'importance' => LearningImportance::Essential, 'stage' => LearningStage::TermEnd, 'reps' => true, 'per_term' => true,
                'title' => ['lt' => 'Perduok patirtį kitam atstovui', 'en' => 'Pass on your experience'],
                'description' => [
                    'lt' => 'Prieš baigdamas kadenciją susitik su įpėdiniu arba jam parašyk: papasakok apie nebaigtus darbus, naudingus kontaktus ir tai, ką verta žinoti.',
                    'en' => 'Before your term ends, meet or write to your successor about unfinished work, useful contacts and anything worth knowing.',
                ],
            ],
        ];
    }
};
