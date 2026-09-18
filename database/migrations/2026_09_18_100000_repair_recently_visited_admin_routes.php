<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Repairs user ui_preferences where recent_pages or pinned_pages recorded route "page"
     * (the public catch-all) instead of the actual admin route name.
     */
    public function up(): void
    {
        $router = app('router')->getRoutes();

        DB::table('users')
            ->whereNotNull('ui_preferences')
            ->orderBy('id')
            ->each(function (object $user) use ($router): void {
                $prefs = json_decode((string) $user->ui_preferences, true);

                if (! is_array($prefs)) {
                    return;
                }

                $modified = false;

                foreach (['recent_pages', 'pinned_pages'] as $key) {
                    if (! isset($prefs[$key]) || ! is_array($prefs[$key])) {
                        continue;
                    }

                    foreach ($prefs[$key] as &$entry) {
                        if (($entry['route'] ?? null) === 'page' && ! empty($entry['url']) && str_starts_with((string) $entry['url'], '/mano')) {
                            try {
                                $request = Request::create((string) $entry['url']);
                                $matched = $router->match($request);

                                if ($matched && $matched->getName() && $matched->getName() !== 'page') {
                                    $entry['route'] = $matched->getName();
                                    $modified = true;
                                }
                            } catch (\Throwable) {
                                // Keep original if route cannot be matched
                            }
                        }
                    }
                    unset($entry);
                }

                if ($modified) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['ui_preferences' => json_encode($prefs)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data fix is intentionally non-reversible (restoring corrupted 'page' routes is undesirable).
    }
};
