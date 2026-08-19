<?php

use App\Models\Page;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.privacy_page_id_lt', null);
        $this->migrator->add('site.privacy_page_id_en', null);

        if (! $this->migrator->exists('site.privacy_page_id')) {
            return;
        }

        // The legacy single pointer moves into the slot matching its page's language.
        // It is read through update()'s closure because the migrator has no getter.
        $this->migrator->update('site.privacy_page_id', function ($oldId) {
            if (is_string($oldId) && $oldId !== '') {
                $lang = Page::query()->find($oldId)?->lang;

                if (in_array($lang, ['lt', 'en'], true)) {
                    $this->migrator->update("site.privacy_page_id_{$lang}", fn () => $oldId);
                }
            }

            return $oldId; // value unchanged; the legacy key is dropped below
        });

        $this->migrator->delete('site.privacy_page_id');
    }

    public function down(): void
    {
        // Best-effort inverse: keep the LT pointer, falling back to EN.
        $this->migrator->update('site.privacy_page_id_lt', function ($lt) {
            $this->migrator->update('site.privacy_page_id_en', function ($en) use ($lt) {
                $this->migrator->add('site.privacy_page_id', $lt ?? $en);

                return $en;
            });

            return $lt;
        });

        $this->migrator->delete('site.privacy_page_id_lt');
        $this->migrator->delete('site.privacy_page_id_en');
    }
};
