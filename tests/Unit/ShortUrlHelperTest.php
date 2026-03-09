<?php

use App\Helpers\ShortUrlHelper;

describe('ShortUrlHelper', function () {
    it('encodes and decodes ID correctly', function () {
        $id = 1234;
        $encoded = ShortUrlHelper::encode($id);
        $decoded = ShortUrlHelper::decode($encoded);

        expect($decoded)->toBe($id);
    });

    it('encodes zero correctly', function () {
        $encoded = ShortUrlHelper::encode(0);
        $decoded = ShortUrlHelper::decode($encoded);

        expect($decoded)->toBe(0);
    });

    it('produces consistent encoding for the same ID', function () {
        $id = 5000;
        $encoded1 = ShortUrlHelper::encode($id);
        $encoded2 = ShortUrlHelper::encode($id);

        expect($encoded1)->toBe($encoded2);
    });

    it('produces minimum length output', function () {
        $encoded = ShortUrlHelper::encode(1);

        expect(strlen($encoded))->toBeGreaterThanOrEqual(4);
    });

    it('returns null for invalid characters', function () {
        $decoded = ShortUrlHelper::decode('invalid!@#');

        expect($decoded)->toBeNull();
    });

    it('generates correct document URL', function () {
        $id = 1234;
        $url = ShortUrlHelper::documentUrl($id);

        expect($url)->toContain('/d/');
        expect($url)->toContain(ShortUrlHelper::encode($id));
    });

    it('decodes document ID from code', function () {
        $id = 5678;
        $code = ShortUrlHelper::encode($id);
        $decoded = ShortUrlHelper::documentIdFromCode($code);

        expect($decoded)->toBe($id);
    });

    it('handles large IDs correctly', function () {
        $id = 99999;
        $encoded = ShortUrlHelper::encode($id);
        $decoded = ShortUrlHelper::decode($encoded);

        expect($decoded)->toBe($id);
    });
});
