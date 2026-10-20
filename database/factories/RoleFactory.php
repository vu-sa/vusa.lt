<?php

namespace Database\Factories;

use App\Enums\ResponsibilityPurpose;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst($this->faker->unique()->words(2, true));

        return [
            'name' => str($title)->slug()->toString(),
            'title' => ['lt' => $title, 'en' => $title],
            'guard_name' => 'web',
        ];
    }

    public function purpose(ResponsibilityPurpose $purpose): static
    {
        return $this->state(['purpose' => $purpose]);
    }

    /**
     * The one responsibility carrying the purpose, created on first use.
     */
    public static function forPurpose(ResponsibilityPurpose $purpose): Role
    {
        return Role::forPurpose($purpose) ?? Role::factory()->purpose($purpose)->create([
            'name' => $purpose->value,
            'title' => ['lt' => __($purpose->labelKey(), locale: 'lt'), 'en' => __($purpose->labelKey(), locale: 'en')],
        ]);
    }
}
