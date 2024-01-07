<?php

namespace Database\Seeders;

use App\Models\Doing;
use App\Models\Goal;
use App\Models\Matter;
use App\Models\Type;
use Illuminate\Database\Seeder;

class DoingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // call factory
        Goal::factory()
            ->has(Matter::factory()
                ->has(Doing::factory()->hasAttached(
                    Type::query()->where('model_type', Doing::class)->inRandomOrder()->limit(2)
                )->count(10))
                ->count(10))
            ->count(3)->create();
    }
}
