<?php

namespace Database\Factories;

use App\Models\MailingList;
use App\Models\MailingListMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailingListMember>
 */
class MailingListMemberFactory extends Factory
{
    protected $model = MailingListMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mailing_list_id' => MailingList::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'display_name' => $this->faker->name(),
        ];
    }
}
