<?php

namespace App\Services\Typesense;

use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Engines\TypesenseEngine;
use Typesense\Client;

class MeetingSearchEngine extends TypesenseEngine
{
    public static function resolve(): Engine
    {
        $engine = app(EngineManager::class)->engine('typesense');

        if (! $engine instanceof TypesenseEngine) {
            return $engine;
        }

        $config = config('scout.typesense');

        return new self(new Client($config['client-settings']), $config['max_total_results'] ?? 1000, $config);
    }

    public function update($models): void
    {
        foreach ($models as $model) {
            $meetingId = $model instanceof AgendaItem ? $model->meeting_id : $model->getKey();

            $model->getConnection()->afterCommit(fn () => app(MeetingSearchLock::class)->run((string) $meetingId, function () use ($model): void {
                // A worker may have loaded its model before a restrictive save took the lock.
                $fresh = $model->fresh();

                if ($fresh === null || ($fresh instanceof Meeting && $fresh->trashed()) || ! $fresh->shouldBeSearchable()) {
                    parent::delete(new Collection([$model]));

                    return;
                }

                parent::update(new Collection([$fresh]));
            }));
        }
    }

    public function delete($models): void
    {
        foreach ($models as $model) {
            $meetingId = $model instanceof AgendaItem ? $model->meeting_id : $model->getKey();
            app(MeetingSearchLock::class)->run((string) $meetingId, fn () => parent::delete(new Collection([$model])));
        }
    }
}
