<?php

use App\Services\FileUsage\FileReferenceMatcher;
use App\Services\HtmlSanitizerService;

/**
 * File Manager keeps upload names verbatim, so real references carry spaces,
 * Lithuanian letters and punctuation — and each storage path encodes them
 * differently. Every case below is a form production actually writes.
 */
dataset('file paths', [
    'ascii' => 'ataskaita.pdf',
    'space' => 'Ataskaita 2024.pdf',
    'lithuanian letters' => 'Ąžuolų šventė.jpg',
    'parentheses and plus' => 'VU SA (galutinis) v2+priedai.pdf',
    'ampersand and dash' => 'Q&A – dažni klausimai.pdf',
    'lithuanian quotes and apostrophe' => "„Kabutės“ ir 'apostrofai'.pdf",
    'like wildcards' => '50% nuolaida_studentams.pdf',
    'generic name' => '1.jpg',
    'lithuanian folder' => 'padaliniai/vusamif/Renginių nuotraukos/Šventė.jpg',
]);

function uploadUrl(string $relativePath): string
{
    return '/uploads/files/'.$relativePath;
}

function sanitizedLink(string $url): string
{
    return app(HtmlSanitizerService::class)
        ->sanitizeRichContent('<p><a href="'.htmlspecialchars($url).'">failas</a></p>');
}

function nfd(string $value): string
{
    return Normalizer::normalize($value, Normalizer::FORM_D);
}

dataset('stored forms', [
    'raw plain column' => fn (string $url): string => $url,
    'without leading slash' => fn (string $url): string => ltrim($url, '/'),
    'tiptap json (json_encode default)' => fn (string $url): string => json_encode(['type' => 'image', 'attrs' => ['src' => $url]]),
    'sanitized html (news.short)' => sanitizedLink(...),
    'sanitized html in spatie translation' => fn (string $url): string => json_encode(['lt' => sanitizedLink($url)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'sanitized html in escaped json' => fn (string $url): string => json_encode(['lt' => sanitizedLink($url)]),
    'percent-encoded by a browser' => fn (string $url): string => implode('/', array_map(rawurlencode(...), explode('/', $url))),
    'absolute www url' => fn (string $url): string => 'https://www.vusa.lt'.$url,
    'absolute tenant url' => fn (string $url): string => 'http://mif.vusa.lt'.$url,
    'decomposed (NFD) letters' => nfd(...),
    'decomposed letters in escaped json' => fn (string $url): string => json_encode(['src' => nfd($url)]),
    'with query string' => fn (string $url): string => $url.'?v=2',
    'inside plain text' => fn (string $url): string => 'Atsisiųsk: '.$url.' šiandien',
    'legacy path without files/' => fn (string $url): string => str_replace('/uploads/files/', '/uploads/', $url),
]);

test('every stored form of a reference matches', function (string $relativePath, Closure $store): void {
    $stored = $store(uploadUrl($relativePath));

    expect(new FileReferenceMatcher($relativePath, matchLegacyPath: true)->matches($stored))->toBeTrue();
})->with('file paths')->with('stored forms');

test('the SQL pre-filter needles survive every stored form', function (string $relativePath, Closure $store): void {
    $stored = $store(uploadUrl($relativePath));

    foreach (new FileReferenceMatcher($relativePath, matchLegacyPath: true)->coarseNeedles() as $needle) {
        expect($stored)->toContain($needle);
    }
})->with('file paths')->with('stored forms');

test('a file stored with decomposed letters matches a composed reference', function (): void {
    $matcher = new FileReferenceMatcher(nfd('Šventė.jpg'), matchLegacyPath: false);

    expect($matcher->matches('/uploads/files/Šventė.jpg'))->toBeTrue();
});

test('pre-filter needles are the longest runs that survive encoding', function (): void {
    $matcher = new FileReferenceMatcher('padaliniai/vusamif/Ąžuolų šventė (1).jpg', matchLegacyPath: false);

    expect($matcher->coarseNeedles())->toBe(['padaliniai', 'vusamif', 'vent', '.jpg']);
});

test('a reference to another file does not match', function (string $relativePath, string $stored): void {
    expect(new FileReferenceMatcher($relativePath, matchLegacyPath: false)->matches($stored))->toBeFalse();
})->with([
    'same path on a foreign host' => ['foto.jpg', 'https://cdn.example.com/uploads/files/foto.jpg'],
    'host that only ends in vusa.lt' => ['foto.jpg', 'https://evilvusa.lt/uploads/files/foto.jpg'],
    'host that only starts with vusa.lt' => ['foto.jpg', 'https://vusa.lt.example.com/uploads/files/foto.jpg'],
    'same name in another folder' => ['foto.jpg', '/uploads/files/kitas/foto.jpg'],
    'name with a prefix' => ['foto.jpg', '/uploads/files/kita-foto.jpg'],
    'longer extension' => ['foto.jpg', '/uploads/files/foto.jpg.webp'],
    'name with a suffix' => ['foto.jpg', '/uploads/files/foto (1).jpg'],
    'bare filename' => ['foto.jpg', 'foto.jpg'],
    'different case' => ['foto.jpg', '/uploads/files/Foto.jpg'],
    'underscore is not a wildcard' => ['a_b.pdf', '/uploads/files/axb.pdf'],
    'legacy path when another file lives there' => ['news/foto.webp', '/uploads/news/foto.webp'],
    'empty value' => ['foto.jpg', ''],
]);
