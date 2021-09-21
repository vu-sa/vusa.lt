<?php

namespace Database\Factories;

use App\Models\mainPage;
use Illuminate\Database\Eloquent\Factories\Factory;

class MainPageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = mainPage::class;

    /**
     * Get random icon from images/icons/custom directory.
     */
    
    public function random_custom_icon() {
        $files = glob(realpath('public/images/icons/custom') . '/*.*');
        $file = array_rand($files);
        $path = explode('/public', $files[$file]);
        return $path[1];
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'link' => $this->faker->url(),
            'text' => $this->faker->word(),
            'image' => $this->random_custom_icon(),
            'position' => 'infoPage',
            'type' => 'infoPage',
            'moduleName' => 'links',
            'groupID' => 1
        ];
    }
}
