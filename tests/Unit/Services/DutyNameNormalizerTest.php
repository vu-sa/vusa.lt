<?php

use App\Services\DutyNameNormalizer;

/**
 * Pairs below are drawn from real duplicate/near-duplicate duty names found in
 * production (grouped within the same institution) — see the duty-management
 * UX plan — plus a couple of synthetic variants exercising the same rule.
 * Each pair must normalize to the same string.
 */
test('gendered/variant pairs normalize to the same form', function (string $a, string $b): void {
    expect(DutyNameNormalizer::normalize($a))
        ->toBe(DutyNameNormalizer::normalize($b))
        ->not->toBe('');
})->with([
    'koordinatorius / koordinatorė' => ['Komunikacijos koordinatorius', 'Komunikacijos koordinatorė'],
    'narys / narė' => ['Kolegialaus valdymo organo narys', 'Kolegialaus valdymo organo narė'],
    'administratorius variants' => ['Administratorius', 'Administratorius (-ė)'],
    'administratorius / administratorė with trailing space' => ['Administratorius', 'Administratorė '],
    'valdybos narys casing' => ['Valdybos Narys', 'Valdybos narys'],
    'atstovas (-ė) suffix' => ['SPK atstovas', 'SPK atstovas (-ė)'],
    'kuratorius subject suffix, gendered' => [
        'VU SA CHGF Kuratorius (Biochemija)',
        'VU SA CHGF Kuratorė (Biochemija)',
    ],
    'kuratorius casing only' => ['VU SA TSPMI Kuratorius', 'VU SA TSPMI kuratorius'],
    'mid-string gender marker' => [
        'Tarptautinių Studentų (-čių) Reikalų Koordinatorius',
        'Tarptautinių studentų (-čių) reikalų koordinatorė',
    ],
    'no space before gender-marker paren (synthetic)' => [
        'Studentų atstovas(-ė) VU Centrinėje akademinės etikos komisijoje',
        'Studentų atstovas VU Centrinėje akademinės etikos komisijoje',
    ],
]);

test('unrelated or differently-scoped names do not collapse together', function (string $a, string $b): void {
    expect(DutyNameNormalizer::normalize($a))->not->toBe(DutyNameNormalizer::normalize($b));
})->with([
    'different duty entirely' => ['Studentų atstovas', 'Pirmininkas'],
    'scoped vs unscoped' => ['Studentų atstovas', 'Studentų atstovas SPK'],
    'different subject areas' => [
        'VU SA CHGF Kuratorius (Chemija)',
        'VU SA CHGF Kuratorius (Biochemija)',
    ],
    'different institution qualifier' => ['Komunikacijos koordinatorius', 'Rinkodaros koordinatorius'],
]);

test('handles empty and whitespace-only input without throwing', function (): void {
    expect(DutyNameNormalizer::normalize(''))->toBe('');
    expect(DutyNameNormalizer::normalize('   '))->toBe('');
});

test('is idempotent', function (): void {
    $normalized = DutyNameNormalizer::normalize('Komunikacijos koordinatorius');

    expect(DutyNameNormalizer::normalize($normalized))->toBe($normalized);
});
