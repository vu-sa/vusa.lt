<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    public $inc = 0;

    private function incrementAndReturn() {
        global $inc;

        $inc = $inc + 1;
        return strval($inc);
    }

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'duties' => $this->faker->jobTitle(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'groupname' => 'centrinis-biuras',
            'grouptitle' => 0,
            'infoText' => $this->faker->paragraph(2),
            'name_short' => NULL,
            'name_full' => NULL,
            'contactOrder' => $this->incrementAndReturn(),
        ];
    }
}
