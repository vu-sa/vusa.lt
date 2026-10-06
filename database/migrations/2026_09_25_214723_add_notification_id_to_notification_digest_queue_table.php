<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_digest_queue', function (Blueprint $table): void {
            $table->uuid('notification_id')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('notification_digest_queue', function (Blueprint $table): void {
            $table->dropColumn('notification_id');
        });
    }
};
