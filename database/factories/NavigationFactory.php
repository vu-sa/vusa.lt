<?php

namespace Database\Factories;

use App\Models\Navigation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

class NavigationFactory extends Factory
{
    protected $model = Navigation::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true),
            'lang' => 'lt',
            'url' => '#',
            'parent_id' => 0,
            'order' => new Sequence(fn () => $this->faker->unique()->numberBetween(1, 100000)),
            'is_active' => true,
            'extra_attributes' => [],
        ];
    }

    /**
     * A root-level menu item (the default `parent_id`, made explicit for readability).
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => ['parent_id' => 0]);
    }

    /**
     * A child of the given parent, inheriting its language like `store()` does.
     */
    public function child(Navigation $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'lang' => $parent->lang,
        ]);
    }

    /**
     * A non-clickable divider — `name` is blanked to match how the form nulls it.
     */
    public function divider(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '',
            'extra_attributes' => ['type' => 'divider'],
        ]);
    }

    /**
     * The English counterpart of a row, for language-drift and switcher tests.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => ['lang' => 'en']);
    }
}
