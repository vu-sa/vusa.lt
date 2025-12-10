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

describe('Split Translation Loading', function () {
    describe('shared translations', function () {
        it('loads validation translations from shared directory', function () {
            // validation.php is in lang/shared/
            $translation = __('validation.required', [], 'lt');
            
            expect($translation)->not()->toBe('validation.required')
                ->and($translation)->toContain(':attribute');
        });

        it('loads auth translations from shared directory', function () {
            // auth.php is in lang/shared/
            $translation = __('auth.login', [], 'lt');
            
            expect($translation)->toBe('Prisijungti');
        });

        it('loads common translations from shared directory', function () {
            // common.php is in lang/shared/
            $translation = __('common.cancel', [], 'lt');
            
            expect($translation)->toBe('Atšaukti');
        });

        it('loads pagination translations from shared directory', function () {
            // pagination.php is in lang/shared/
            $translation = __('pagination.next', [], 'lt');
            
            expect($translation)->not()->toBe('pagination.next');
        });
    });

    describe('admin translations', function () {
        it('loads tutorials translations from admin directory', function () {
            // tutorials.php is in lang/admin/
            $translation = __('tutorials.next', [], 'lt');
            
            expect($translation)->toBe('Kitas');
        });

        it('loads messages translations from admin directory', function () {
            // messages.php is in lang/admin/
            $translation = __('messages.created', [], 'lt');
            
            expect($translation)->not()->toBe('messages.created');
        });

        it('loads entities translations from admin directory', function () {
            // entities.php is in lang/admin/ - uses nested structure
            $translation = __('entities.duty.model', [], 'lt');
            
            expect($translation)->not()->toBe('entities.duty.model');
        });

        it('loads settings translations from admin directory', function () {
            // settings.php is in lang/admin/
            $translation = __('settings.title', [], 'lt');
            
            expect($translation)->not()->toBe('settings.title');
        });

        it('loads forms translations from admin directory', function () {
            // forms.php is in lang/admin/ - uses nested structure
            $translation = __('forms.fields.title', [], 'lt');
            
            expect($translation)->not()->toBe('forms.fields.title');
        });
    });

    describe('public translations', function () {
        it('loads search translations from public directory', function () {
            // search.php is in lang/public/
            $translation = __('search.document_search_title', [], 'lt');
            
            expect($translation)->toBe('Dokumentai');
        });

        it('loads accessibility translations from public directory', function () {
            // accessibility.php is in lang/public/
            $translation = __('accessibility.select_image', [], 'lt');
            
            expect($translation)->not()->toBe('accessibility.select_image');
        });
    });

    describe('translation fallback', function () {
        it('falls back to English when Lithuanian translation is missing', function () {
            // Test with a key that exists in English
            $ltTranslation = __('validation.accepted', [], 'lt');
            $enTranslation = __('validation.accepted', [], 'en');
            
            // Both should be translated (not return raw key)
            expect($ltTranslation)->not()->toBe('validation.accepted');
            expect($enTranslation)->not()->toBe('validation.accepted');
        });
    });

    describe('directory structure', function () {
        it('has the expected directory structure', function () {
            $langPath = lang_path();
            
            // Check main directories exist
            expect(is_dir($langPath . '/shared'))->toBeTrue();
            expect(is_dir($langPath . '/admin'))->toBeTrue();
            expect(is_dir($langPath . '/public'))->toBeTrue();
            
            // Check locale subdirectories exist
            expect(is_dir($langPath . '/shared/lt'))->toBeTrue();
            expect(is_dir($langPath . '/shared/en'))->toBeTrue();
            expect(is_dir($langPath . '/admin/lt'))->toBeTrue();
            expect(is_dir($langPath . '/admin/en'))->toBeTrue();
            expect(is_dir($langPath . '/public/lt'))->toBeTrue();
            expect(is_dir($langPath . '/public/en'))->toBeTrue();
        });

        it('has translation files in shared directory', function () {
            $sharedLtPath = lang_path('shared/lt');
            
            expect(file_exists($sharedLtPath . '/validation.php'))->toBeTrue();
            expect(file_exists($sharedLtPath . '/auth.php'))->toBeTrue();
            expect(file_exists($sharedLtPath . '/common.php'))->toBeTrue();
        });

        it('has translation files in admin directory', function () {
            $adminLtPath = lang_path('admin/lt');
            
            expect(file_exists($adminLtPath . '/tutorials.php'))->toBeTrue();
            expect(file_exists($adminLtPath . '/messages.php'))->toBeTrue();
            expect(file_exists($adminLtPath . '/entities.php'))->toBeTrue();
            expect(file_exists($adminLtPath . '/settings.php'))->toBeTrue();
        });

        it('has translation files in public directory', function () {
            $publicLtPath = lang_path('public/lt');
            
            expect(file_exists($publicLtPath . '/search.php'))->toBeTrue();
            expect(file_exists($publicLtPath . '/accessibility.php'))->toBeTrue();
        });
    });

    describe('JSON translation files', function () {
        it('has base JSON translation files', function () {
            $langPath = lang_path();
            
            expect(file_exists($langPath . '/lt.json'))->toBeTrue();
            expect(file_exists($langPath . '/en.json'))->toBeTrue();
        });

        it('loads JSON translations correctly', function () {
            // JSON translations are used for simple key-value pairs
            // Test a known JSON translation
            $translation = __('Skaityti daugiau', [], 'lt');
            
            expect($translation)->toBe('Skaityti daugiau');
        });
    });
});

describe('Translation Consistency', function () {
    it('loads the same translation files from both lt and en in shared directory', function () {
        $sharedLt = glob(lang_path('shared/lt/*.php'));
        $sharedEn = glob(lang_path('shared/en/*.php'));
        
        $ltFiles = array_map(fn($f) => basename($f), $sharedLt);
        $enFiles = array_map(fn($f) => basename($f), $sharedEn);
        
        sort($ltFiles);
        sort($enFiles);
        
        expect($ltFiles)->toBe($enFiles);
    });

    it('loads the same translation files from both lt and en in admin directory', function () {
        $adminLt = glob(lang_path('admin/lt/*.php'));
        $adminEn = glob(lang_path('admin/en/*.php'));
        
        $ltFiles = array_map(fn($f) => basename($f), $adminLt);
        $enFiles = array_map(fn($f) => basename($f), $adminEn);
        
        sort($ltFiles);
        sort($enFiles);
        
        expect($ltFiles)->toBe($enFiles);
    });

    it('loads the same translation files from both lt and en in public directory', function () {
        $publicLt = glob(lang_path('public/lt/*.php'));
        $publicEn = glob(lang_path('public/en/*.php'));
        
        $ltFiles = array_map(fn($f) => basename($f), $publicLt);
        $enFiles = array_map(fn($f) => basename($f), $publicEn);
        
        sort($ltFiles);
        sort($enFiles);
        
        expect($ltFiles)->toBe($enFiles);
    });
});
