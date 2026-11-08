<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('search documents include pronouns only when the user shows them', function (bool $shown, string $lt, string $en): void {
    $user = User::factory()->create([
        'pronouns' => ['lt' => 'Jie/jų', 'en' => 'They/them'],
        'show_pronouns' => $shown,
    ]);

    $document = $user->toSearchableArray();

    expect($document['pronouns_lt'])->toBe($lt)
        ->and($document['pronouns_en'])->toBe($en);
})->with([
    'shown' => [true, 'Jie/jų', 'They/them'],
    'hidden' => [false, '', ''],
]);
