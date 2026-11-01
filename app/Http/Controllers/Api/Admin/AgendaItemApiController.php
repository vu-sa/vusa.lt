<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Pivots\AgendaItem;
use Illuminate\Http\JsonResponse;

class AgendaItemApiController extends ApiController
{
    /** Every translation with the votes, for an edit sheet opened from a page that lists items. */
    public function editable(AgendaItem $agendaItem): JsonResponse
    {
        abort_if($agendaItem->meeting === null, 404);

        $this->authorize('update', $agendaItem);

        return $this->jsonSuccess([
            ...$agendaItem->toFullArray(),
            'votes' => $agendaItem->votes->map->toFullArray()->all(),
        ]);
    }
}
