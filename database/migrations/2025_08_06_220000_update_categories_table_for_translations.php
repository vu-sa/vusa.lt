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
        Schema::table('categories', function (Blueprint $table) {
            // First, add temporary columns for translations
            $table->json('name_translations')->nullable();
            $table->json('description_translations')->nullable();
        });

        // Migrate existing data
        $categories = DB::table('categories')->get();
        foreach ($categories as $category) {
            $nameTranslations = [
                'lt' => $category->name,
                'en' => $category->name, // Default to same value for both languages
            ];

            $descriptionTranslations = $category->description ? [
                'lt' => $category->description,
                'en' => $category->description,
            ] : null;

            DB::table('categories')->where('id', $category->id)->update([
                'name_translations' => json_encode($nameTranslations),
                'description_translations' => $descriptionTranslations ? json_encode($descriptionTranslations) : null,
            ]);
        }

        Schema::table('categories', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['name', 'description']);
        });

        Schema::table('categories', function (Blueprint $table) {
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
        Schema::table('categories', function (Blueprint $table) {
            // Add temporary columns for old format
            $table->string('name_string', 255)->nullable();
            $table->text('description_string')->nullable();
        });

        // Migrate data back
        $categories = DB::table('categories')->get();
        foreach ($categories as $category) {
            $nameData = json_decode($category->name, true);
            $descriptionData = $category->description ? json_decode($category->description, true) : null;

            DB::table('categories')->where('id', $category->id)->update([
                'name_string' => $nameData['lt'] ?? $nameData['en'] ?? 'Unknown',
                'description_string' => $descriptionData ? ($descriptionData['lt'] ?? $descriptionData['en'] ?? null) : null,
            ]);
        }

        Schema::table('categories', function (Blueprint $table) {
            // Drop JSON columns
            $table->dropColumn(['name', 'description']);
        });

        Schema::table('categories', function (Blueprint $table) {
            // Rename back to original columns
            $table->renameColumn('name_string', 'name');
            $table->renameColumn('description_string', 'description');
        });
    }
};
