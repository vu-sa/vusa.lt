<?php

namespace App\Support;

use App\Models\Task;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Model;

class TaskModelIdeHelperHook implements ModelHookInterface
{
    public function run(ModelsCommand $command, Model $model): void
    {
        if (! $model instanceof Task) {
            return;
        }

        // A deleted morph target is absent even though taskable_id itself is required.
        $command->setProperty('taskable', 'Model|\\Eloquent', true, null, '', true);
    }
}
