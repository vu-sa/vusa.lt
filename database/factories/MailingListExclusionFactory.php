<?php

namespace Database\Factories;

use App\Models\MailingList;
use App\Models\MailingListExclusion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailingListExclusion>
 */
class MailingListExclusionFactory extends Factory
{
    protected $model = MailingListExclusion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mailing_list_id' => MailingList::factory(),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
