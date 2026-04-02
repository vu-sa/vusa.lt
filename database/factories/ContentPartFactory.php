<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tiptap\Editor;

class ContentPartFactory extends Factory
{
    public function definition()
    {
        return [
            'type' => 'tiptap',
            'json_content' => (new Editor)->setContent('<p>'.$this->faker->paragraph(3).'</p><p>'.$this->faker->paragraph(3).'</p>')->getDocument(),
        ];
    }
}
