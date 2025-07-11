<?php

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generate aliases for existing tags that don't have them
        Tag::whereNull('alias')
            ->orWhere('alias', '')
            ->chunk(100, function ($tags) {
                foreach ($tags as $tag) {
                    // This will trigger the model's boot method which auto-generates alias
                    $tag->save();
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this - we're just populating missing data
    }
};
