<?php

use App\Actions\NormalizeInstitutionAliases;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seeded aliases were shared (`padalinio-studentu-atstovai-8` by 23 institutions) or not
     * URL-safe. Preview with `institutions:normalize-aliases --dry-run`.
     */
    public function up(): void
    {
        app(NormalizeInstitutionAliases::class)->apply();
    }

    public function down(): void
    {
        // Old addresses keep redirecting through public_urls; the seeded values are not restored.
    }
};
