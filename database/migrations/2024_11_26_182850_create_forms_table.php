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
        Schema::drop('registrations');
        Schema::drop('registration_forms');

        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            // TODO: don't forget to implement
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            // create field to add url for visible forms
            $table->json('path')->nullable()->comment('URL path for visible forms');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
