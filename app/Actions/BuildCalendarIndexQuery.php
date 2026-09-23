<?php

namespace App\Actions;

use App\Http\Requests\IndexCalendarRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Calendar;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Builder;

/**
 * The admin calendar list, shared by the page's first render and the API that refreshes it.
 * The calendar stays on the database: its Typesense collection is the public one and holds no
 * drafts, which the admin list must show.
 */
final class BuildCalendarIndexQuery
{
    use HasTanstackTables;

    /** @return Builder<Calendar> */
    public static function execute(IndexCalendarRequest $request, TanstackTableService $tableService): Builder
    {
        $query = (new self)->applyTanstackFilters(
            Calendar::query()->with(['eventType', 'tenant:id,shortname']),
            $request,
            $tableService,
            ['title'],
            [
                'tenantRelation' => 'tenant',
                'permission' => 'calendars.read.padalinys',
            ],
        );

        if ($request->getUntyped()) {
            $query->whereNull('event_type_id');
        }

        return $query;
    }
}
