<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            // First, add temporary columns for translations
            $table->json('name_translations')->nullable();
            $table->json('description_translations')->nullable();
        });

        // Migrate existing data
        $tags = DB::table('tags')->get();
        foreach ($tags as $tag) {
            $nameTranslations = [
                'lt' => $tag->name,
                'en' => $tag->name, // Default to same value for both languages
            ];

            $descriptionTranslations = $tag->description ? [
                'lt' => $tag->description,
                'en' => $tag->description,
            ] : null;

            DB::table('tags')->where('id', $tag->id)->update([
                'name_translations' => json_encode($nameTranslations),
                'description_translations' => $descriptionTranslations ? json_encode($descriptionTranslations) : null,
            ]);
        }

        Schema::table('tags', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['name', 'description']);
        });

        Schema::table('tags', function (Blueprint $table) {
            // Rename new columns to original names
            $table->renameColumn('name_translations', 'name');
            $table->renameColumn('description_translations', 'description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            // Add temporary columns for old format
            $table->string('name_string', 255)->nullable();
            $table->text('description_string')->nullable();
        });

        // Migrate data back
        $tags = DB::table('tags')->get();
        foreach ($tags as $tag) {
            $nameData = json_decode($tag->name, true);
            $descriptionData = $tag->description ? json_decode($tag->description, true) : null;

            DB::table('tags')->where('id', $tag->id)->update([
                'name_string' => $nameData['lt'] ?? $nameData['en'] ?? 'Unknown',
                'description_string' => $descriptionData ? ($descriptionData['lt'] ?? $descriptionData['en'] ?? null) : null,
            ]);
        }

        Schema::table('tags', function (Blueprint $table) {
            // Drop JSON columns
            $table->dropColumn(['name', 'description']);
        });

        Schema::table('tags', function (Blueprint $table) {
            // Rename back to original columns
            $table->renameColumn('name_string', 'name');
            $table->renameColumn('description_string', 'description');
        });
    }
};
