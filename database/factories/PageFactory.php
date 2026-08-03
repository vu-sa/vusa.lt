<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Page::class;

    /**
     * Default content part created after the Page row exists, and only when
     * the caller didn't already populate the Content (e.g. via an explicit
     * `content_id` override) -- mirrors PageController::store(), and matters
     * for App\Services\ActivityRootResolver: a part created before its Page
     * row is committed can only self-root to its Content, not roll up to the
     * Page.
     */
    #[\Override]
    public function configure(): static
    {
        return $this->afterCreating(function (Page $page): void {
            if (ContentPart::where('content_id', $page->content_id)->doesntExist()) {
                // content_id directly, not $page->content->parts()->create():
                // the latter would cache Content's $with-eager-loaded (and at
                // this point still empty) parts collection on the Page instance.
                ContentPart::factory()->create(['content_id' => $page->content_id]);
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
        return [
            'title' => $this->faker->sentence(),
            'permalink' => fn () => 'page-'.Str::uuid()->toString(),
            'category_id' => $this->faker->numberBetween(1, 3),
            'content_id' => Content::factory(),
            'tenant_id' => Tenant::factory(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the page is active.
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the page is inactive.
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
