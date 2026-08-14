<?php

use App\Providers\ActivityLogServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\BroadcastServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\TestingServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;
use App\Providers\TypesenseServiceProvider;
use Laravel\Tinker\TinkerServiceProvider;

return [
    ActivityLogServiceProvider::class,
    AppServiceProvider::class,
    AuthServiceProvider::class,
    BroadcastServiceProvider::class,
    EventServiceProvider::class,
    TelescopeServiceProvider::class,
    TypesenseServiceProvider::class, // Register our new Typesense service provider
    TestingServiceProvider::class, // Testing-only hooks (no-op outside the testing env)
    TinkerServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
];
