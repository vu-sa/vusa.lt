<?php

namespace Database\Factories;

use App\Models\FileableFile;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FileableFile>
 */
class FileableFileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FileableFile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fileable_type' => Institution::class,
            'fileable_id' => Institution::factory(),
            'sharepoint_id' => $this->faker->uuid(),
            'sharepoint_path' => '_fileables/Institution/'.Str::ulid().'/'.$this->faker->word().'.pdf',
            'name' => $this->faker->word().'.pdf',
            'file_type' => $this->faker->randomElement([
                FileableFile::TYPE_PROTOCOL,
                FileableFile::TYPE_REPORT,
                FileableFile::TYPE_AGENDA,
                FileableFile::TYPE_OTHER,
            ]),
            'mime_type' => 'application/pdf',
            'size_bytes' => $this->faker->numberBetween(1000, 10000000),
            'file_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'description' => $this->faker->optional()->sentence(),
            'last_synced_at' => now(),
        ];
    }

    /**
     * Indicate that the file is a protocol.
     */
    public function protocol(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => FileableFile::TYPE_PROTOCOL,
        ]);
    }

    /**
     * Indicate that the file is a report.
     */
    public function report(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => FileableFile::TYPE_REPORT,
        ]);
    }

    /**
     * Indicate that the file was deleted externally in SharePoint.
     */
    public function deletedExternally(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_externally_at' => now(),
        ]);
    }

    /**
     * Indicate that the file has a public sharing link.
     */
    public function withPublicLink(): static
    {
        return $this->state(fn (array $attributes) => [
            'public_link' => 'https://example.sharepoint.com/share/'.$this->faker->uuid(),
            'public_link_expires_at' => now()->addDays(365),
        ]);
    }

    /**
     * Indicate that the public link has expired.
     */
    public function withExpiredPublicLink(): static
    {
        return $this->state(fn (array $attributes) => [
            'public_link' => 'https://example.sharepoint.com/share/'.$this->faker->uuid(),
            'public_link_expires_at' => now()->subDays(1),
        ]);
    }
}
