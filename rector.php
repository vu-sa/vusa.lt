<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        /* __DIR__.'/bootstrap', */
        __DIR__.'/config',
        __DIR__.'/lang',
        __DIR__.'/public',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withSets([PestSetList::CODING_STYLE])
    ->withComposerBased(laravel: true)
    // The Laravel 8 set renames PendingMail::sendNow() to send(), which is not a no-op:
    // send() enqueues a ShouldQueue mailable, so transport failures never surface inline.
    // These three call sites deliver synchronously on purpose.
    ->withSkip([
        RenameMethodRector::class => [
            __DIR__.'/app/Console/Commands/ProcessNotificationDigests.php',
            __DIR__.'/app/Console/Commands/TestMail.php',
            __DIR__.'/app/Http/Controllers/Admin/DashboardController.php',
            __DIR__.'/app/Http/Controllers/Admin/ProfileController.php',
        ],
    ])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
