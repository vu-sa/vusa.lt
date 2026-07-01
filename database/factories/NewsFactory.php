<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Content;
use App\Models\News;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
            'permalink' => fn () => 'news-'.Str::uuid()->toString(),
            'category_id' => Category::inRandomOrder()->select('id')->first()->id,
            'short' => $this->faker->paragraph(1),
            'content_id' => Content::factory()->hasParts(1),
            'image' => '/images/placeholders/foto'.rand(1, 5).'.jpg',
            'important' => rand(0, 1),
            'draft' => false,
            'publish_time' => $this->faker->dateTimeBetween('-10 weeks'),
            'tenant_id' => Tenant::factory(),
            'lang' => Arr::random(['lt', 'en']),
        ];
    }
}
