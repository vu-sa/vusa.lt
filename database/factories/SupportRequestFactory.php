<?php

namespace Database\Factories;

use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Models\SupportRequest;
use App\Models\SupportRequestArea;
use App\Models\SupportRequestType;
use App\Models\SupportService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportRequest>
 */
class SupportRequestFactory extends Factory
{
    protected $model = SupportRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'assigned_to' => null,
            'support_service_id' => SupportService::factory(),
            'support_request_type_id' => SupportRequestType::factory(),
            'support_request_area_id' => function (array $attributes) {
                return SupportRequestArea::factory()->create([
                    'support_service_id' => $attributes['support_service_id'],
                ])->id;
            },
            'visibility' => SupportRequestVisibility::Private,
            'status' => SupportRequestStatus::New,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(2, true),
            'context_url' => fake()->boolean(60) ? fake()->url() : null,
            'selected_text' => null,
            'locale' => 'lt',
            'resolved_at' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportRequestStatus::Done,
            'resolved_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportRequestStatus::InProgress,
        ]);
    }

    public function rolesVisibility(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => SupportRequestVisibility::Roles,
        ]);
    }

    public function publicVisibility(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => SupportRequestVisibility::Public,
        ]);
    }
}
