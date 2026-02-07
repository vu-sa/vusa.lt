<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds highlights, layout selection, and additional SEO/publishing fields
     * to news and pages tables for enhanced content presentation.
     */
    public function up(): void
    {
        // Update news table
        Schema::table('news', function (Blueprint $table) {
            // Highlights: up to 3 key takeaway points displayed prominently
            $table->json('highlights')->nullable()->after('main_points');

            // Layout: determines visual presentation style
            // Options: 'modern' (default), 'classic', 'immersive'
            $table->string('layout', 20)->default('modern')->after('highlights');

            // Track when content was last meaningfully edited
            $table->timestamp('last_edited_at')->nullable()->after('updated_at');
        });

        // Update pages table
        Schema::table('pages', function (Blueprint $table) {
            // Highlights: up to 3 key points for the page
            $table->json('highlights')->nullable()->after('is_active');

            // Layout: determines visual presentation style
            // Options: 'default', 'wide', 'focused'
            $table->string('layout', 20)->default('default')->after('highlights');

            // Featured image for OG/SEO and visual representation
            $table->string('featured_image', 255)->nullable()->after('layout');

            // Meta description for SEO (separate from content)
            $table->text('meta_description')->nullable()->after('featured_image');

            // Publish time for scheduled publishing (similar to news)
            $table->timestamp('publish_time')->nullable()->after('meta_description');

            // Track when content was last meaningfully edited
            $table->timestamp('last_edited_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['highlights', 'layout', 'last_edited_at']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'highlights',
                'layout',
                'featured_image',
                'meta_description',
                'publish_time',
                'last_edited_at',
            ]);
        });
    }
};
