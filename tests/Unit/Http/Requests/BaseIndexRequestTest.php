<?php

use App\Http\Requests\IndexUserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getFilters includes standalone search parameter', function () {
    $request = new IndexUserRequest([
        'search' => 'john',
        'filters' => json_encode(['tenant' => 'vusa']),
    ]);

    $filters = $request->getFilters();

    expect($filters)->toHaveKey('search', 'john')
        ->toHaveKey('tenant', 'vusa');
});

test('getFilters returns only search when no filters provided', function () {
    $request = new IndexUserRequest([
        'search' => 'doe',
    ]);

    $filters = $request->getFilters();

    expect($filters)->toHaveKey('search', 'doe');
});

test('getFilters returns empty array when nothing provided', function () {
    $request = new IndexUserRequest([]);

    $filters = $request->getFilters();

    expect($filters)->toBe([]);
});

test('getFilters ignores empty search string', function () {
    $request = new IndexUserRequest([
        'search' => '',
    ]);

    $filters = $request->getFilters();

    expect($filters)->not->toHaveKey('search');
});
