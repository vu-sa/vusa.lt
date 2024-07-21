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
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropUnique(['parent_id', 'alias']);
            $table->dropColumn('parent_id');

            $table->renameColumn('name', 'name_old');
            $table->renameColumn('short_name', 'short_name_old');
            $table->renameColumn('alias', 'alias_old');
            $table->renameColumn('description', 'description_old');

            $table->json('name')->nullable()->after('id');
            $table->json('short_name')->nullable()->after('name');
            $table->json('alias')->nullable()->after('short_name');
            $table->json('description')->nullable()->after('alias');
            $table->json('address')->nullable()->after('description');
            $table->string('phone')->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('logo_url')->nullable()->after('image_url');
            $table->boolean('is_active')->default(true)->after('tenant_id');
        });

        $institutions = \App\Models\Institution::all();

        foreach ($institutions as $institution) {

            $institution->setTranslation('name', 'lt', $institution->name_old);
            // check if extra_attributes.en.name, if yes, append to name
            if (isset($institution->extra_attributes['en']['name'])) {
                $institution->setTranslation('name', 'en', $institution->extra_attributes['en']['name']);
            }

            if (isset($institution->short_name_old)) {
                $institution->setTranslation('short_name', 'lt', $institution->short_name_old);
                // check if extra_attributes.en.short_name, if yes, append to short_name
                if (isset($institution->extra_attributes['en']['short_name'])) {
                    $institution->setTranslation('short_name', 'en', $institution->extra_attributes['en']['short_name']);
                }
            }

            if (isset($institution->alias_old)) {
                $institution->setTranslation('alias', 'lt', $institution->alias_old);
                // check if extra_attributes.en.alias, if yes, append to alias
                if (isset($institution->extra_attributes['en']['alias'])) {
                    $institution->setTranslation('alias', 'en', $institution->extra_attributes['en']['alias']);
                }
            }
            
            if (isset($institution->description_old)) {
                $institution->setTranslation('description', 'lt', $institution->description_old);
                // check if extra_attributes.en.description, if yes, append to description

                if (isset($institution->extra_attributes['en']['description'])) {
                    $institution->setTranslation('description', 'en', $institution->extra_attributes['en']['description']);
                }
            }

            $institution->save();
        }
        Schema::table('institutions', function (Blueprint $table) {
            // rename and drop columns
            $table->dropUnique(['name', 'padalinys_id']);
            $table->dropColumn('name_old');
            $table->dropColumn('short_name_old');
            $table->dropColumn('alias_old');
            $table->dropColumn('description_old');
            $table->dropColumn('extra_attributes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
