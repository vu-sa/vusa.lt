<?php

namespace Database\Factories;

use App\Enums\MailingListAddressMode;
use App\Enums\MailingListSourceType;
use App\Models\MailingList;
use App\Models\MailingListSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailingListSource>
 */
class MailingListSourceFactory extends Factory
{
    protected $model = MailingListSource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mailing_list_id' => MailingList::factory(),
            'type' => MailingListSourceType::Email,
            'email' => $this->faker->unique()->safeEmail(),
            'address_mode' => MailingListAddressMode::Holder,
        ];
    }
}
