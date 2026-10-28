<?php

use App\Http\Requests\IndexResourceCategoryRequest;

test('reads the API sorting JSON first', function (): void {
    $request = IndexResourceCategoryRequest::create('/', 'GET', [
        'sorting' => json_encode([['id' => 'created_at', 'desc' => true]]),
        'sort' => 'name:asc',
    ]);

    expect($request->getSorting())->toBe([['id' => 'created_at', 'desc' => true]]);
});

test('falls back to the collection URL sort so a bookmarked order holds on first render', function (): void {
    $request = IndexResourceCategoryRequest::create('/', 'GET', ['sort' => 'created_at:desc']);

    expect($request->getSorting())->toBe([['id' => 'created_at', 'desc' => true]]);
});

test('ignores a malformed URL sort', function (string $sort): void {
    $request = IndexResourceCategoryRequest::create('/', 'GET', ['sort' => $sort]);

    expect($request->getSorting())->toBe([['id' => 'name', 'desc' => false]]);
})->with(['name', 'name:sideways', 'name) desc --:asc', 'relevance']);
