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
        Schema::table('duties', function (Blueprint $table) {
            $table->dropUnique(['name', 'institution_id', 'email']);

            $table->renameColumn('name', 'name_old');
            $table->renameColumn('description', 'description_old');

            $table->json('name')->nullable()->after('id');
            $table->json('description')->nullable()->after('name');
        });

        $duties = \App\Models\Duty::all();

        foreach ($duties as $duty) {

            $duty->setTranslation('name', 'lt', $duty->name_old);

            $extra_attributes = json_decode($duty->extra_attributes);

            if (isset($extra_attributes->en->name)) {
                $duty->setTranslation('name', 'en', $extra_attributes->en->name);
            }

            if (isset($duty->description_old)) {
                $duty->setTranslation('description', 'lt', $duty->description_old);
                // check if extra_attributes.en.description, if yes, append to description
            }

            if (isset($extra_attributes->en->description)) {
                $duty->setTranslation('description', 'en', $extra_attributes->en->description);
            }

            $duty->save();
        }

        Schema::table('duties', function (Blueprint $table) {
            // rename and drop columns
            $table->dropColumn('name_old');
            $table->dropColumn('description_old');
            $table->dropColumn('extra_attributes');
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
