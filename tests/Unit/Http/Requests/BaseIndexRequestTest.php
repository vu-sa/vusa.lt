<?php

use App\Http\Requests\IndexUserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('getFilters includes standalone search parameter', function (): void {
    $request = new IndexUserRequest([
        'search' => 'john',
        'filters' => json_encode(['tenant' => 'vusa']),
    ]);

    $filters = $request->getFilters();

    expect($filters)->toHaveKey('search', 'john')
        ->toHaveKey('tenant', 'vusa');
});

test('getFilters returns only search when no filters provided', function (): void {
    $request = new IndexUserRequest([
        'search' => 'doe',
    ]);

    $filters = $request->getFilters();

    expect($filters)->toHaveKey('search', 'doe');
});

test('getFilters returns empty array when nothing provided', function (): void {
    $request = new IndexUserRequest([]);

    $filters = $request->getFilters();

    expect($filters)->toBeEmpty();
});

test('getFilters ignores empty search string', function (): void {
    $request = new IndexUserRequest([
        'search' => '',
    ]);

    $filters = $request->getFilters();

    expect($filters)->not->toHaveKey('search');
});
