<?php

namespace Database\Factories;

use App\Models\ContentPart;
use Illuminate\Database\Eloquent\Factories\Factory;

class TextBoxSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content_part_id' => ContentPart::factory(),
            'text' => $this->faker->paragraph(),
            'user_id' => null,
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
