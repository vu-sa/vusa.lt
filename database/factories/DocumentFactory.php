<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentTypes = [
            'Document' => ['lt' => 'Dokumentas', 'en' => 'Document'],
            'Policy' => ['lt' => 'Tvarka', 'en' => 'Policy'],
            'Meeting Minutes' => ['lt' => 'Posėdžio protokolas', 'en' => 'Meeting Minutes'],
            'Report' => ['lt' => 'Ataskaita', 'en' => 'Report'],
            'Regulation' => ['lt' => 'Reglamentas', 'en' => 'Regulation'],
            'Statute' => ['lt' => 'Statutas', 'en' => 'Statute'],
            'Agreement' => ['lt' => 'Sutartis', 'en' => 'Agreement'],
            'Form' => ['lt' => 'Blankas', 'en' => 'Form'],
        ];

        $contentTypeKey = $this->faker->randomElement(array_keys($documentTypes));
        $language = $this->faker->randomElement(['lt', 'en']);

        // Generate realistic Lithuanian/English titles based on content type
        $titles = [
            'lt' => [
                'Document' => 'VU SA veiklos dokumentas',
                'Policy' => 'Studentų atstovavimo tvarka',
                'Meeting Minutes' => 'Studentų atstovybės posėdžio protokolas',
                'Report' => 'Metinė veiklos ataskaita',
                'Regulation' => 'Studentų dalyvavimo reglamentas',
                'Statute' => 'Studentų atstovybės statutas',
                'Agreement' => 'Bendradarbiavimo sutartis',
                'Form' => 'Registracijos blankas',
            ],
            'en' => [
                'Document' => 'VU Student Representation activity document',
                'Policy' => 'Student representation policy',
                'Meeting Minutes' => 'Student representation meeting minutes',
                'Report' => 'Annual activity report',
                'Regulation' => 'Student participation regulation',
                'Statute' => 'Student representation statute',
                'Agreement' => 'Cooperation agreement',
                'Form' => 'Registration form',
            ],
        ];

        return [
            'name' => $this->faker->words(3, true).'.pdf',
            'title' => $titles[$language][$contentTypeKey].' '.$this->faker->year(),
            'sharepoint_id' => $this->faker->uuid,
            'eTag' => $this->faker->sha256,
            'document_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'institution_id' => Institution::factory(),
            'content_type' => $contentTypeKey,
            'language' => $language,
            'summary' => $language === 'lt'
                ? 'Dokumentas skirtas '.$this->faker->sentence(8)
                : 'This document is intended for '.$this->faker->sentence(8),
            'anonymous_url' => $this->faker->url,
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
            'sharepoint_site_id' => $this->faker->uuid,
            'sharepoint_list_id' => $this->faker->uuid,
            'effective_date' => $this->faker->optional(0.6)->dateTimeBetween('-1 year', '+3 months')?->format('Y-m-d'),
            'expiration_date' => $this->faker->optional(0.3)->dateTimeBetween('+6 months', '+3 years')?->format('Y-m-d'),
        ];
    }

    /**
     * Create a public document
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive document
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a Lithuanian document with appropriate content
     */
    public function lithuanian(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'lt',
            'title' => 'VU SA '.fake()->randomElement([
                'veiklos strategija',
                'posėdžio protokolas',
                'metinė ataskaita',
                'studentų atstovavimo tvarka',
                'bendradarbiavimo sutartis',
                'reglamentas',
                'statutas',
            ]).' '.fake()->year(),
            'summary' => 'Dokumentas skirtas '.fake()->sentence(8),
        ]);
    }

    /**
     * Create an English document with appropriate content
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'en',
            'title' => 'VU Student Representation '.fake()->randomElement([
                'activity strategy',
                'meeting minutes',
                'annual report',
                'student representation policy',
                'cooperation agreement',
                'regulation',
                'statute',
            ]).' '.fake()->year(),
            'summary' => 'This document is intended for '.fake()->sentence(8),
        ]);
    }

    /**
     * Create a policy document
     */
    public function policy(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'Policy',
            'effective_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'expiration_date' => fake()->optional(0.7)->dateTimeBetween('+1 year', '+3 years')?->format('Y-m-d'),
        ]);
    }

    /**
     * Create a meeting minutes document
     */
    public function meetingMinutes(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'Meeting Minutes',
            'document_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Create an annual report document
     */
    public function annualReport(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'Report',
            'document_date' => fake()->dateTimeBetween('-1 year', '-6 months')->format('Y-m-d'),
        ]);
    }
}
