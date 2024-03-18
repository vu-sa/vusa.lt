<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContentPartFactory extends Factory
{
    public function definition()
    {
        return [
            'type' => 'tiptap',
            'json_content' => (new \Tiptap\Editor)->setContent('<p>'.$this->faker->paragraph(3).'</p><p>'.$this->faker->paragraph(3).'</p>')->getDocument(),
        ];
    }
}
