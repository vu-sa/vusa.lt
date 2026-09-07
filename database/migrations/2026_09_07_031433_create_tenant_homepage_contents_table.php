<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_homepage_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id');
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'locale']);
            $table->unique('content_id');
        });

        DB::table('tenants')->whereNotNull('content_id')->orderBy('id')->each(function (object $tenant): void {
            DB::table('tenant_homepage_contents')->insert([
                'tenant_id' => $tenant->id,
                'content_id' => $tenant->content_id,
                'locale' => 'lt',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['content_id']);
            $table->dropColumn('content_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('content_id')->nullable()->constrained();
        });

        DB::table('tenant_homepage_contents')->where('locale', 'lt')->orderBy('id')->each(function (object $homepageContent): void {
            DB::table('tenants')->where('id', $homepageContent->tenant_id)->update([
                'content_id' => $homepageContent->content_id,
            ]);
        });

        Schema::dropIfExists('tenant_homepage_contents');
    }
};
