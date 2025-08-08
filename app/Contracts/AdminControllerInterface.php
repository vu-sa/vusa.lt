<?php

namespace App\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

/**
 * Interface for standardized admin controller behavior
 *
 * This interface defines the standard methods that admin controllers should implement
 * to ensure consistent response handling and proper return types.
 */
interface AdminControllerInterface
{
    /**
     * Display a listing of the resource
     */
    public function index(Request $request): InertiaResponse;

    /**
     * Show the form for creating a new resource
     */
    public function create(): InertiaResponse;

    /**
     * Store a newly created resource in storage
     */
    public function store(Request $request): RedirectResponse;

    /**
     * Display the specified resource
     */
    public function show(mixed $model): InertiaResponse;

    /**
     * Show the form for editing the specified resource
     */
    public function edit(mixed $model): InertiaResponse;

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, mixed $model): RedirectResponse;

    /**
     * Remove the specified resource from storage
     */
    public function destroy(mixed $model): RedirectResponse;
}
