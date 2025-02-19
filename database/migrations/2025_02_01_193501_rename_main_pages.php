<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('main_page', 'quick_links');

        // Rename permissions
        $permissions = [
            'mainPages.create.own' => 'quickLinks.create.own',
            'mainPages.create.padalinys' => 'quickLinks.create.padalinys',
            'mainPages.create.*' => 'quickLinks.create.*',
            'mainPages.read.own' => 'quickLinks.read.own',
            'mainPages.read.padalinys' => 'quickLinks.read.padalinys',
            'mainPages.read.*' => 'quickLinks.read.*',
            'mainPages.update.own' => 'quickLinks.update.own',
            'mainPages.update.padalinys' => 'quickLinks.update.padalinys',
            'mainPages.update.*' => 'quickLinks.update.*',
            'mainPages.delete.own' => 'quickLinks.delete.own',
            'mainPages.delete.padalinys' => 'quickLinks.delete.padalinys',
            'mainPages.delete.*' => 'quickLinks.delete.*',
        ];

        foreach ($permissions as $oldName => $newName) {
            DB::table('permissions')->where('name', $oldName)->update(['name' => $newName, 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('quick_links', 'main_page');

        // Rename permissions
        $permissions = [
            'quickLinks.create.own' => 'mainPages.create.own',
            'quickLinks.create.padalinys' => 'mainPages.create.padalinys',
            'quickLinks.create.*' => 'mainPages.create.*',
            'quickLinks.read.own' => 'mainPages.read.own',
            'quickLinks.read.padalinys' => 'mainPages.read.padalinys',
            'quickLinks.read.*' => 'mainPages.read.*',
            'quickLinks.update.own' => 'mainPages.update.own',
            'quickLinks.update.padalinys' => 'mainPages.update.padalinys',
            'quickLinks.update.*' => 'mainPages.update.*',
            'quickLinks.delete.own' => 'mainPages.delete.own',
            'quickLinks.delete.padalinys' => 'mainPages.delete.padalinys',
            'quickLinks.delete.*' => 'mainPages.delete.*',
        ];

        foreach ($permissions as $oldName => $newName) {
            DB::table('permissions')->where('name', $oldName)->update(['name' => $newName]);
        }
    }
};
