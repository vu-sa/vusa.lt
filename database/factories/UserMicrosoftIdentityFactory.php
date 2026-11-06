<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserMicrosoftIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserMicrosoftIdentity>
 */
class UserMicrosoftIdentityFactory extends Factory
{
    protected $model = UserMicrosoftIdentity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'microsoft_tenant_id' => $this->faker->uuid(),
            'microsoft_object_id' => $this->faker->uuid(),
            'principal_name' => $this->faker->unique()->safeEmail(),
        ];
    }
}
