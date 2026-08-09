<?php

namespace Database\Factories;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use App\Models\Tenant;
use Database\Factories\Concerns\HasTranslatableFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
{
    use HasTranslatableFactory;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->translatableWords(3),
            'description' => $this->translatable(),
            'welcome_text' => $this->translatable(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addWeeks(2),
            'is_anonymous' => true,
            'status' => SurveyStatus::Draft,
        ];
    }

    public function pendingApproval(): static
    {
        return $this->state(['status' => SurveyStatus::PendingApproval]);
    }

    /**
     * A survey that already exists in LimeSurvey, and is therefore locked.
     */
    public function published(int $limeSurveyId = 123456): static
    {
        return $this->state([
            'status' => SurveyStatus::Active,
            'limesurvey_survey_id' => $limeSurveyId,
            'limesurvey_url' => 'https://apklausos.test/index.php/'.$limeSurveyId,
            'sync_status' => 'synced',
        ]);
    }
}
