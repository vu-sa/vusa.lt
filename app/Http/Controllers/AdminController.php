<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Inertia\Response as InertiaResponse;

/**
 * Base controller for admin routes with proper return types
 *
 * This base class provides correct return type declarations for admin controllers
 * to eliminate PHPStan errors and improve IDE support.
 */
abstract class AdminController extends Controller
{
    /**
     * Return an Inertia response for admin pages
     */
    protected function inertiaResponse(string $component, array $props = []): InertiaResponse
    {
        return inertia($component, $props);
    }

    /**
     * Return a redirect response
     */
    protected function redirectResponse(string $route, mixed ...$parameters): RedirectResponse
    {
        return redirect()->route($route, ...$parameters);
    }

    /**
     * Return a redirect back response
     */
    protected function redirectBack(array $with = []): RedirectResponse
    {
        return back()->with($with);
    }

    /**
     * Return a redirect back response (alias for redirectBack)
     */
    protected function backResponse(array $with = []): RedirectResponse
    {
        return $this->redirectBack($with);
    }

    /**
     * Return a redirect response with success message
     */
    protected function redirectWithSuccess(string $route, string $message, mixed ...$parameters): RedirectResponse
    {
        return $this->redirectResponse($route, ...$parameters)->with('success', $message);
    }

    /**
     * Return a redirect response with error message
     */
    protected function redirectWithError(string $route, string $message, mixed ...$parameters): RedirectResponse
    {
        return $this->redirectResponse($route, ...$parameters)->with('error', $message);
    }

    /**
     * Return a redirect response with info message
     */
    protected function redirectWithInfo(string $route, string $message, mixed ...$parameters): RedirectResponse
    {
        return $this->redirectResponse($route, ...$parameters)->with('info', $message);
    }

    /**
     * Return a redirect back response with success message
     */
    protected function redirectBackWithSuccess(string $message): RedirectResponse
    {
        return $this->redirectBack(['success' => $message]);
    }

    /**
     * Return a redirect back response with error message
     */
    protected function redirectBackWithError(string $message): RedirectResponse
    {
        return $this->redirectBack(['error' => $message]);
    }

    /**
     * Return a redirect back response with info message
     */
    protected function redirectBackWithInfo(string $message): RedirectResponse
    {
        return $this->redirectBack(['info' => $message]);
    }

    /**
     * Handle authorization with consistent error handling
     * Supports both authorize($ability, $model) and authorize($model, $ability) patterns
     */
    protected function handleAuthorization(mixed $modelOrAbility, mixed $abilityOrModel = null): void
    {
        if (is_string($modelOrAbility) && $abilityOrModel !== null) {
            // Pattern: handleAuthorization($ability, $model)
            $this->authorize($modelOrAbility, $abilityOrModel);
        } elseif (is_string($abilityOrModel)) {
            // Pattern: handleAuthorization($model, $ability)
            $this->authorize($abilityOrModel, $modelOrAbility);
        } else {
            // Fallback to first parameter as ability
            $this->authorize($modelOrAbility, $abilityOrModel);
        }
    }

    /**
     * Redirect to index route for a given resource
     */
    protected function redirectToIndex(string $resource, array $parameters = []): RedirectResponse
    {
        return $this->redirectResponse("{$resource}.index", ...$parameters);
    }

    /**
     * Redirect to index with success message
     */
    protected function redirectToIndexWithSuccess(string $resource, string $message, array $parameters = []): RedirectResponse
    {
        return $this->redirectToIndex($resource, $parameters)->with('success', $message);
    }

    /**
     * Redirect to index with error message
     */
    protected function redirectToIndexWithError(string $resource, string $message, array $parameters = []): RedirectResponse
    {
        return $this->redirectToIndex($resource, $parameters)->with('error', $message);
    }

    /**
     * Redirect to index with info message
     */
    protected function redirectToIndexWithInfo(string $resource, string $message, array $parameters = []): RedirectResponse
    {
        return $this->redirectToIndex($resource, $parameters)->with('info', $message);
    }
}
