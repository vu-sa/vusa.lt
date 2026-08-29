<?php

use App\Models\Content;
use App\Models\ContentPart;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Editor forms submitted as FormData (see EditHomePage.vue's `forceFormData`)
 * arrive with every scalar stringified — Inertia encodes booleans as "1"/"0" and
 * numbers as "8000". ContentPart's saving hook must cast the known boolean and
 * integer option/content keys back, or a stored "0" reads as truthy on the JS
 * side and flips the switch semantics.
 */
function makeFormDataPart(string $type, array $jsonContent, array $options): ContentPart
{
    $content = Content::factory()->create();

    return $content->parts()->create([
        'type' => $type,
        'json_content' => $jsonContent,
        'options' => $options,
        'order' => 0,
    ]);
}

test('boolean option keys saved as FormData strings are cast back to booleans', function (): void {
    $part = makeFormDataPart('hero-carousel', [], [
        'autoplay' => '1',
        'showArrows' => '1',
        'showIndicators' => '0',
    ]);

    expect($part->fresh()->options->toArray())->toBe([
        'autoplay' => true,
        'showArrows' => true,
        'showIndicators' => false,
    ]);
});

test('integer option keys saved as FormData strings are cast back to integers', function (): void {
    $part = makeFormDataPart('hero-carousel', [], [
        'autoplay' => 'true',
        'autoplayDelay' => '8000',
    ]);

    $options = $part->fresh()->options->toArray();
    expect($options['autoplay'])->toBeTrue()
        ->and($options['autoplayDelay'])->toBeInt()
        ->toBe(8000);
});

test('boolean json_content keys are cast too (deck slide imageLeft)', function (): void {
    $part = makeFormDataPart('carousel-slide-deck', [
        ['icon' => 'info', 'badge' => 'B', 'title' => 'T', 'description' => '', 'imageSrc' => '/x.webp', 'imageAlt' => '', 'imageLeft' => '0'],
    ], []);

    $json = $part->fresh()->json_content->toArray();
    expect($json[0]['imageLeft'])->toBeFalse();
});

test('unlisted and non-boolean-ish values are left untouched', function (): void {
    $part = makeFormDataPart('hero-carousel', [], [
        'scrim' => 'medium',
        'height' => '1',
    ]);

    // scrim is not a boolean key; height IS not in the boolean list — both must
    // survive as the authored strings ("1" as a height label, not true).
    expect($part->fresh()->options->toArray())->toBe([
        'scrim' => 'medium',
        'height' => '1',
    ]);
});

test('real booleans and absent options are unaffected', function (): void {
    $part = makeFormDataPart('card-stack', [], ['autoplay' => true, 'hintText' => 'Spustelėk']);

    expect($part->fresh()->options->toArray())->toBe([
        'autoplay' => true,
        'hintText' => 'Spustelėk',
    ]);

    $nullOptions = Content::factory()->create()->parts()->create([
        'type' => 'card-stack',
        'json_content' => [],
        'options' => null,
        'order' => 0,
    ]);

    expect($nullOptions->fresh()->options)->toBeNull();
});
