<?php

use App\Http\Requests\IndexResourceCategoryRequest;
use App\Http\Requests\IndexTrashRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Resolve the form request through the container so it validates like a real request. */
function resolveIndexRequest(string $class, array $query): mixed
{
    app()->instance('request', Request::create('/', 'GET', $query));

    return app($class);
}

test('reads the API sorting JSON first', function (): void {
    $request = resolveIndexRequest(IndexResourceCategoryRequest::class, [
        'sorting' => json_encode([['id' => 'created_at', 'desc' => true]]),
        'sort' => 'name:asc',
    ]);

    expect($request->getSorting())->toBe([['id' => 'created_at', 'desc' => true]]);
});

test('falls back to the collection URL sort so a bookmarked order holds on first render', function (): void {
    $request = resolveIndexRequest(IndexResourceCategoryRequest::class, ['sort' => 'created_at:desc']);

    expect($request->getSorting())->toBe([['id' => 'created_at', 'desc' => true]]);
});

test('ignores a malformed URL sort', function (string $sort): void {
    $request = resolveIndexRequest(IndexResourceCategoryRequest::class, ['sort' => $sort]);

    expect($request->getSorting())->toBe([['id' => 'name', 'desc' => false]]);
})->with(['name', 'name:sideways', 'name) desc --:asc', 'relevance']);

test('applies a subclass sorting allowlist to the URL sort too', function (): void {
    resolveIndexRequest(IndexTrashRequest::class, ['sort' => 'agendaItems.title:asc']);
})->throws(ValidationException::class);
