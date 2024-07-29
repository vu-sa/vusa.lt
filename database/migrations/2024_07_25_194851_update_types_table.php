<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('types', function (Blueprint $table) {
            $table->dropColumn('extra_attributes');

            $table->renameColumn('title', 'title_old');
            $table->renameColumn('description', 'description_old');

            $table->json('title')->nullable()->after('parent_id');
            $table->json('description')->nullable()->after('title');
        });

        $types = \App\Models\Type::all();

        foreach ($types as $type) {
            $type->setTranslation('title', 'lt', $type->title_old);
            $type->setTranslation('title', 'en', '');

            if (isset($type->description_old)) {
                $type->setTranslation('description', 'lt', $type->description_old);
                $type->setTranslation('description', 'en', '');
            }

            $type->save();
        }

        Schema::table('types', function (Blueprint $table) {
            $table->dropUnique(['title', 'model_type']);
            $table->dropColumn('title_old');
            $table->dropColumn('description_old');
        });

        Schema::table('types', function (Blueprint $table) {
            $table->unique(['title', 'model_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
