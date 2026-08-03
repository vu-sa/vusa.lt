<?php

use App\Helpers\ShortUrlHelper;

describe('ShortUrlHelper', function (): void {
    it('encodes and decodes ID correctly', function (): void {
        $id = 1234;
        $encoded = ShortUrlHelper::encode($id);
        $decoded = ShortUrlHelper::decode($encoded);

        expect($decoded)->toBe($id);
    });

    it('encodes zero correctly', function (): void {
        $encoded = ShortUrlHelper::encode(0);
        $decoded = ShortUrlHelper::decode($encoded);

        expect($decoded)->toBe(0);
    });

    it('produces consistent encoding for the same ID', function (): void {
        $id = 5000;
        $encoded1 = ShortUrlHelper::encode($id);
        $encoded2 = ShortUrlHelper::encode($id);

        expect($encoded1)->toBe($encoded2);
    });

    it('produces minimum length output', function (): void {
        $encoded = ShortUrlHelper::encode(1);

        expect(strlen($encoded))->toBeGreaterThanOrEqual(4);
    });

    it('returns null for invalid characters', function (): void {
        $decoded = ShortUrlHelper::decode('invalid!@#');

        expect($decoded)->toBeNull();
    });

    it('generates correct document URL', function (): void {
        $id = 1234;
        $url = ShortUrlHelper::documentUrl($id);

        expect($url)->toContain('/d/')
            ->toContain(ShortUrlHelper::encode($id));
    });

    it('decodes document ID from code', function (): void {
        $id = 5678;
        $code = ShortUrlHelper::encode($id);
        $decoded = ShortUrlHelper::documentIdFromCode($code);

        expect($decoded)->toBe($id);
    });

    it('handles large IDs correctly', function (): void {
        $id = 99999;
        $encoded = ShortUrlHelper::encode($id);
        $decoded = ShortUrlHelper::decode($encoded);

        expect($decoded)->toBe($id);
    });
});
