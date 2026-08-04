<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `navigation.is_active` has never been read by `NavigationService::getNavigationForPublic()` —
 * it existed on the table but every row rendered regardless of the flag. This change starts
 * honouring it (see NavigationService), which would silently drop the two rows below out of
 * the live LT menu since they are `is_active = 0` despite currently being visible.
 *
 * Matched by URL rather than id: this table is a content copy across environments, and ids
 * are not guaranteed to line up, but these two Gmail-integration links are.
 */
return new class extends Migration
{
    private const array URLS = [
        'https://www.vusa.lt/lt/vu-el-pasto-integracija-i-gmail',
        'https://www.vusa.lt/lt/vu-el-pastas',
    ];

    public function up(): void
    {
        DB::table('navigation')
            ->whereIn('url', self::URLS)
            ->where('is_active', false)
            ->update(['is_active' => true]);
    }

    public function down(): void
    {
        // Intentionally not reversed — restoring the old (silently ignored) inactive
        // flag would hide these links again now that is_active is actually enforced.
    }
};
