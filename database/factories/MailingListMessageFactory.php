<?php

namespace Database\Factories;

use App\Models\MailingList;
use App\Models\MailingListMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailingListMessage>
 */
class MailingListMessageFactory extends Factory
{
    protected $model = MailingListMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mailing_list_id' => MailingList::factory(),
            'message_id_hash' => strtoupper($this->faker->unique()->bothify('????????????????????????????????')),
            'subject' => $this->faker->sentence(),
            'sender_name' => $this->faker->name(),
            'sender_email' => $this->faker->safeEmail(),
            'sent_at' => $this->faker->dateTimeBetween('-1 year'),
            'body_text' => $this->faker->paragraph(),
            'attachments' => [],
        ];
    }
}
