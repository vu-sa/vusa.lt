<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Content;
use App\Models\ContentPart;
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
     * Default content part created after the News row exists, and only when
     * the caller didn't already populate the Content (e.g. via an explicit
     * `content_id` override) -- mirrors NewsController::store(), and matters
     * for App\Services\ActivityRootResolver: a part created before its News
     * row is committed can only self-root to its Content, not roll up to the
     * News.
     */
    #[\Override]
    public function configure(): static
    {
        return $this->afterCreating(function (News $news): void {
            if (ContentPart::where('content_id', $news->content_id)->doesntExist()) {
                // content_id directly, not $news->content->parts()->create():
                // the latter would cache Content's $with-eager-loaded (and at
                // this point still empty) parts collection on the News instance.
                ContentPart::factory()->create(['content_id' => $news->content_id]);
            }
        });
    }

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
            'content_id' => Content::factory(),
            'image' => '/images/placeholders/foto'.rand(1, 5).'.jpg',
            'important' => rand(0, 1),
            'draft' => false,
            'publish_time' => $this->faker->dateTimeBetween('-10 weeks'),
            'tenant_id' => Tenant::factory(),
            'lang' => Arr::random(['lt', 'en']),
        ];
    }
}
