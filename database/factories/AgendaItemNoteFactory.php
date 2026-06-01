<?php

namespace Database\Factories;

use App\Models\AgendaItemNote;
use App\Models\Pivots\AgendaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaItemNote>
 */
class AgendaItemNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agenda_item_id' => AgendaItem::factory(),
            'yjs_state' => null,
            'notes_html' => '<p>'.$this->faker->sentence().'</p>',
            'updated_by' => null,
        ];
    }
}
