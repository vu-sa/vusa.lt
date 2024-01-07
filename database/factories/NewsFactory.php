<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\News;
use App\Models\Padalinys;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class NewsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = News::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public $inc = 0;

    private function incrementAndReturn()
    {
        global $inc;

        $inc = $inc + 1;

        return strval($inc);
    }

    public function definition()
    {
        $editor = rand(1, 4);
        switch ($editor) {
            case 1:
                $publisher = 1;
                break;
            case 2:
                $publisher = 4;
                break;
            default:
                $publisher = 1;
                break;
        }

        return [
            'title' => $this->faker->sentence(),
            'permalink' => 'news'.$this->incrementAndReturn(),
            'category_id' => Category::inRandomOrder()->select('id')->first()->id,
            'short' => $this->faker->paragraph(1),
            'image' => '/images/placeholders/foto'.rand(1, 5).'.jpg',
            'important' => rand(0, 1),
            'publish_time' => $this->faker->dateTimeBetween('-10 weeks'),
            'text' => '<p>'.$this->faker->paragraph(3).'</p><p>'.$this->faker->paragraph(3).'</p>',
            'padalinys_id' => Padalinys::factory(),
            'lang' => Arr::random(['lt', 'en']),
        ];
    }
}
