<?php

namespace Database\Factories;

use App\Models\PublicUrl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PublicUrlFactory extends Factory
{
    protected $model = PublicUrl::class;

    public function definition(): array
    {
        return [
            'locale' => $this->faker->randomElement(['lt', 'en']),
            'url' => 'https://vusa.lt/'.Str::uuid(),
        ];
    }
}
