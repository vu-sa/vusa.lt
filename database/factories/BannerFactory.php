<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

class BannerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Banner::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $editor = rand(1,4);
        switch ($editor) {
            case 1:
                $editorG = 1;
                break;
            case 2:
                $editorG = 4;
                break;
            default:
                $editorG = 1;
                break;
        }
        
        return [
            'type' => 'image',
            'value' => '/images/placeholders/logo.png',
            'title' => $this->faker->words(3, true),
            'url' => '#',
            'order' => $this->faker->numberBetween(0, 1000),
            'editor' => $editor,
            'editorG' => $editorG,
            'hide' => 0,
        ];
    }
}
