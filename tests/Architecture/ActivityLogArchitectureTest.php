<?php

/**
 * Activity Log Architecture Tests
 *
 * Enforces that every model opts into activity logging through
 * App\Models\Traits\LogsModelActivity rather than wiring up Spatie's
 * LogsActivity trait directly. The wrapper picks the correct
 * logFillable()/logUnguarded() strategy automatically -- using LogsActivity
 * directly reintroduces the "some models silently log nothing" bug the
 * wrapper exists to prevent (see LogsModelActivity::defaultActivitylogOptions).
 *
 * @see AGENTS.md for logging conventions
 */

use App\Models\Traits\LogsModelActivity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

arch('only LogsModelActivity may use Spatie\'s LogsActivity trait directly')
    ->expect('App\Models')
    ->classes()
    ->not->toUse(LogsActivity::class)
    ->ignoring(LogsModelActivity::class);
