<?php

use App\Services\Media\ImageMigration;
use App\Services\Media\LegacyImageSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $migration = app(ImageMigration::class);

        if ($migration->pendingMedia()->exists() || $migration->supersededMedia()->exists()
            || collect($migration->legacy()->status())->contains(
                fn (array $status): bool => ($status['without_media'][LegacyImageSource::RESOLVED] ?? 0) > 0,
            )) {
            throw new RuntimeException('Legacy image columns cannot be dropped while media migration or cleanup is pending. Run media:migrate-images --status, finish imports/upgrades and media:migrate-images --cleanup first.');
        }

        Schema::table('news', function (Blueprint $table): void {
            $table->dropColumn(['image', 'image_author']);
        });

        Schema::table('banners', function (Blueprint $table): void {
            $table->dropColumn('image_url');
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn(['image_url', 'image_focal_point']);
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn('featured_image');
        });
    }

    // Restores the schema only; removed values require the pre-deployment database backup.
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('featured_image', 255)->nullable()->after('layout');
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->string('image_url', 255)->nullable()->after('description');
            $table->string('image_focal_point', 20)->nullable()->after('image_url');
        });

        Schema::table('banners', function (Blueprint $table): void {
            $table->text('image_url')->nullable()->after('title');
        });

        Schema::table('news', function (Blueprint $table): void {
            $table->string('image', 200)->nullable()->after('other_lang_id');
            $table->string('image_author', 200)->nullable()->default('VU SA archyvo nuotrauka')->after('image');
        });
    }
};
