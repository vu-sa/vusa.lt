<?php

namespace Database\Factories;

use App\Enums\MailingListMode;
use App\Models\MailingList;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailingList>
 */
class MailingListFactory extends Factory
{
    protected $model = MailingList::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->slug(2);
        $address = "{$name}@konf.vusa.lt";

        return [
            'tenant_id' => Tenant::factory(),
            'address' => $address,
            'mailman_list_id' => MailingList::listIdFor($address),
            'display_name' => ucfirst($name),
            'description' => $this->faker->sentence(),
            'mode' => MailingListMode::Unmanaged,
            'archive_policy' => 'private',
        ];
    }

    public function managed(): static
    {
        return $this->state(['mode' => MailingListMode::Managed]);
    }
}
