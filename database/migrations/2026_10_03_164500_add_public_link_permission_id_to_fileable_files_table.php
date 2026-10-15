<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fileable_files', function (Blueprint $table) {
            $table->string('public_link_permission_id')->nullable()->after('public_link')
                ->comment('SharePoint permission behind public_link, deleted to revoke it');
        });
    }

    public function down(): void
    {
        Schema::table('fileable_files', function (Blueprint $table) {
            $table->dropColumn('public_link_permission_id');
        });
    }
};
