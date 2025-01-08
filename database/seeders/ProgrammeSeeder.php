<?php

namespace Database\Seeders;

use App\Models\Programme;
use App\Models\ProgrammeBlock;
use App\Models\ProgrammeDay;
use App\Models\ProgrammePart;
use App\Models\ProgrammeSection;
use App\Models\Tenant;
use App\Models\Training;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProgrammeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programme = Programme::factory()->create();

        $tenant = Tenant::query()->first();

        // assign programme to training
        $training = Training::factory()->recycle($tenant)->create();
        $training->programmes()->attach($programme->id);

        $day = ProgrammeDay::factory()->create([
            'programme_id' => $programme->id,
            'start_time' => (new Carbon($training->start_date))->setTime(9, 0, 0),
            'order' => rand(0, 10),
        ]);

        $part = ProgrammePart::factory()->hasAttached($day, ['order' => 0])->create(['duration' => 45]);

        $section = ProgrammeSection::factory()->hasAttached($day, ['order' => 1])->create(['duration' => 90]);

        $block = ProgrammeBlock::factory()->create([
            'programme_section_id' => $section->id,
        ]);

        $blockPart = ProgrammePart::factory()->hasAttached($block, ['order' => 0])->create(['duration' => 45]);
    }
}
