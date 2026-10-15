<?php

namespace Database\Factories;

use App\Enums\InstitutionActivityAnswer;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InstitutionActivityRequest>
 */
class InstitutionActivityRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'send_id' => (string) Str::ulid(),
            'institution_id' => Institution::factory(),
            'recipient_id' => User::factory(),
            'period_start' => today()->subMonth(),
            'expires_at' => now()->addDays(InstitutionActivityRequest::LINK_LIFETIME_DAYS),
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }

    public function answered(InstitutionActivityAnswer $answer = InstitutionActivityAnswer::NotMet): static
    {
        return $this->state(['answer' => $answer, 'answered_at' => now()]);
    }
}
