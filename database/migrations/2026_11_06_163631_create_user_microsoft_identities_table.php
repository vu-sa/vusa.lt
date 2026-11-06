<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_microsoft_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('microsoft_tenant_id');
            $table->string('microsoft_object_id');
            $table->string('principal_name');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['microsoft_tenant_id', 'microsoft_object_id'], 'user_microsoft_identities_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_microsoft_identities');
    }
};
