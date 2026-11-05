<?php

/**
 * Every trash action on a HandlesSoftDeletes controller needs a route that binds trashed
 * models — without `withTrashed()` the record 404s. Register them with `Route::softDeletes()`.
 */

use App\Http\Controllers\Admin\SupportRequestController;
use App\Http\Traits\HandlesSoftDeletes;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * @return array<string, array{class-string, string}> keyed by `Controller@action`
 */
function softDeleteControllerActions(): array
{
    $actions = [];

    foreach (glob(app_path('Http/Controllers/Admin/*.php')) as $file) {
        $class = 'App\\Http\\Controllers\\Admin\\'.basename($file, '.php');

        if (! class_exists($class) || ! in_array(HandlesSoftDeletes::class, class_uses_recursive($class), true)) {
            continue;
        }

        foreach (['restore', 'forceDelete'] as $method) {
            if (method_exists($class, $method)) {
                $actions["{$class}@{$method}"] = [$class, $method];
            }
        }
    }

    return $actions;
}

/**
 * @return array<string, Route>
 */
function softDeleteRoutesByAction(): array
{
    return collect(RouteFacade::getRoutes()->getRoutes())
        ->keyBy(fn (Route $route): string => $route->getActionName())
        ->all();
}

test('every soft-delete action has a route that binds trashed models', function (): void {
    $routes = softDeleteRoutesByAction();
    $problems = [];

    foreach (softDeleteControllerActions() as $action => [$class, $method]) {
        $route = $routes[$action] ?? null;

        if ($route === null) {
            $problems[] = "{$action}: no route";

            continue;
        }

        if (! $route->allowsTrashedBindings()) {
            $problems[] = "{$action}: missing withTrashed()";
        }

        $expectedVerb = match (true) {
            $class === SupportRequestController::class => 'POST',
            $method === 'restore' => 'PATCH',
            default => 'DELETE',
        };

        if (! in_array($expectedVerb, $route->methods(), true)) {
            $problems[] = "{$action}: expected {$expectedVerb}, got ".implode('|', $route->methods());
        }

        if (! Str::endsWith($route->getName() ?? '', ".{$method}")) {
            $problems[] = "{$action}: route name should end in .{$method}";
        }
    }

    expect(softDeleteControllerActions())->not->toBeEmpty()
        ->and($problems)->toBe([]);
});
