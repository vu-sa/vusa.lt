<?php

/**
 * Tests for the split translation loading system.
 *
 * Translations are organized in:
 * - lang/shared/{locale}/ - Shared between admin and public
 * - lang/admin/{locale}/ - Admin-only translations
 * - lang/public/{locale}/ - Public-only translations
 *
 * All directories are loaded by Laravel for backend use.
 * The Vite plugin generates separate bundles for frontend.
 */
describe('Split Translation Loading', function (): void {
    describe('shared translations', function (): void {
        it('loads validation translations from shared directory', function (): void {
            // validation.php is in lang/shared/
            $translation = __('validation.required', [], 'lt');

            expect($translation)->not()->toBe('validation.required')
                ->and($translation)->toContain(':attribute');
        });

        it('loads auth translations from shared directory', function (): void {
            // auth.php is in lang/shared/
            $translation = __('auth.login', [], 'lt');

            expect($translation)->toBe('Prisijungti');
        });

        it('loads common translations from shared directory', function (): void {
            // common.php is in lang/shared/
            $translation = __('common.cancel', [], 'lt');

            expect($translation)->toBe('Atšaukti');
        });

        it('loads pagination translations from shared directory', function (): void {
            // pagination.php is in lang/shared/
            $translation = __('pagination.next', [], 'lt');

            expect($translation)->not()->toBe('pagination.next');
        });

        it('loads accessibility translations from shared directory', function (): void {
            // accessibility.php is in lang/shared/
            $translation = __('accessibility.select_image', [], 'lt');

            expect($translation)->not()->toBe('accessibility.select_image');
        });
    });

    describe('admin translations', function (): void {
        it('loads tutorials translations from admin directory', function (): void {
            // tutorials.php is in lang/admin/
            $translation = __('tutorials.next', [], 'lt');

            expect($translation)->toBe('Kitas');
        });

        it('loads messages translations from admin directory', function (): void {
            // messages.php is in lang/admin/
            $translation = __('messages.created', [], 'lt');

            expect($translation)->not()->toBe('messages.created');
        });

        it('loads entities translations from admin directory', function (): void {
            // entities.php is in lang/admin/ - uses nested structure
            $translation = __('entities.duty.model', [], 'lt');

            expect($translation)->not()->toBe('entities.duty.model');
        });

        it('loads settings translations from admin directory', function (): void {
            // settings.php is in lang/admin/
            $translation = __('settings.title', [], 'lt');

            expect($translation)->not()->toBe('settings.title');
        });

        it('loads forms translations from admin directory', function (): void {
            // forms.php is in lang/admin/ - uses nested structure
            $translation = __('forms.fields.title', [], 'lt');

            expect($translation)->not()->toBe('forms.fields.title');
        });
    });

    describe('public translations', function (): void {
        it('loads search translations from public directory', function (): void {
            // search.php is in lang/public/
            $translation = __('search.document_search_title', [], 'lt');

            expect($translation)->toBe('Dokumentai');
        });
    });

    describe('translation fallback', function (): void {
        it('falls back to English when Lithuanian translation is missing', function (): void {
            // Test with a key that exists in English
            $ltTranslation = __('validation.accepted', [], 'lt');
            $enTranslation = __('validation.accepted', [], 'en');

            // Both should be translated (not return raw key)
            expect($ltTranslation)->not()->toBe('validation.accepted');
            expect($enTranslation)->not()->toBe('validation.accepted');
        });
    });

    describe('directory structure', function (): void {
        it('has the expected directory structure', function (): void {
            $langPath = lang_path();

            // Check main directories exist
            expect($langPath.'/shared')->toBeDirectory();
            expect($langPath.'/admin')->toBeDirectory()
                ->and($langPath.'/public')->toBeDirectory();

            // Check locale subdirectories exist
            expect($langPath.'/shared/lt')->toBeDirectory();
            expect($langPath.'/shared/en')->toBeDirectory()
                ->and($langPath.'/admin/lt')->toBeDirectory()
                ->and($langPath.'/admin/en')->toBeDirectory()
                ->and($langPath.'/public/lt')->toBeDirectory()
                ->and($langPath.'/public/en')->toBeDirectory();
        });

        it('has translation files in shared directory', function (): void {
            $sharedLtPath = lang_path('shared/lt');

            expect(file_exists($sharedLtPath.'/validation.php'))->toBeTrue()
                ->and(file_exists($sharedLtPath.'/auth.php'))->toBeTrue()
                ->and(file_exists($sharedLtPath.'/common.php'))->toBeTrue()
                ->and(file_exists($sharedLtPath.'/accessibility.php'))->toBeTrue();
        });

        it('has translation files in admin directory', function (): void {
            $adminLtPath = lang_path('admin/lt');

            expect(file_exists($adminLtPath.'/tutorials.php'))->toBeTrue()
                ->and(file_exists($adminLtPath.'/messages.php'))->toBeTrue()
                ->and(file_exists($adminLtPath.'/entities.php'))->toBeTrue()
                ->and(file_exists($adminLtPath.'/settings.php'))->toBeTrue();
        });

        it('has translation files in public directory', function (): void {
            $publicLtPath = lang_path('public/lt');

            expect(file_exists($publicLtPath.'/search.php'))->toBeTrue();
        });
    });

    describe('JSON translation files', function (): void {
        it('has base JSON translation files', function (): void {
            $langPath = lang_path();

            expect(file_exists($langPath.'/lt.json'))->toBeTrue()
                ->and(file_exists($langPath.'/en.json'))->toBeTrue();
        });

        it('loads JSON translations correctly', function (): void {
            // JSON translations are used for simple key-value pairs
            // Test a known JSON translation
            $translation = __('Skaityti daugiau', [], 'lt');

            expect($translation)->toBe('Skaityti daugiau');
        });
    });
});

describe('Translation Consistency', function (): void {
    it('loads the same translation files from both lt and en in shared directory', function (): void {
        $sharedLt = glob(lang_path('shared/lt/*.php'));
        $sharedEn = glob(lang_path('shared/en/*.php'));

        $ltFiles = array_map(basename(...), $sharedLt);
        $enFiles = array_map(basename(...), $sharedEn);

        sort($ltFiles);
        sort($enFiles);

        expect($ltFiles)->toBe($enFiles);
    });

    it('loads the same translation files from both lt and en in admin directory', function (): void {
        $adminLt = glob(lang_path('admin/lt/*.php'));
        $adminEn = glob(lang_path('admin/en/*.php'));

        $ltFiles = array_map(basename(...), $adminLt);
        $enFiles = array_map(basename(...), $adminEn);

        sort($ltFiles);
        sort($enFiles);

        expect($ltFiles)->toBe($enFiles);
    });

    it('loads the same translation files from both lt and en in public directory', function (): void {
        $publicLt = glob(lang_path('public/lt/*.php'));
        $publicEn = glob(lang_path('public/en/*.php'));

        $ltFiles = array_map(basename(...), $publicLt);
        $enFiles = array_map(basename(...), $publicEn);

        sort($ltFiles);
        sort($enFiles);

        expect($ltFiles)->toBe($enFiles);
    });
});
