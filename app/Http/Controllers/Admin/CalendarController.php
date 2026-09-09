<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DuplicateCalendarAction;
use App\Actions\GetTenantsForUpserts;
use App\Actions\HandleModelMediaUploads;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexCalendarRequest;
use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\PublicUrl;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CalendarController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexCalendarRequest $request)
    {
        $this->handleAuthorization('viewAny', Calendar::class);

        $query = Calendar::query()->with(['category', 'tenant:id,shortname']);

        $searchableColumns = ['title'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenant',
                'permission' => 'calendars.read.padalinys',
            ]
        );

        $deletedCount = $this->getTrashedCount($query);

        $calendar = $query->paginate($request->getPerPage())
            ->withQueryString();

        return $this->inertiaResponse('Admin/Calendar/IndexCalendarEvents', [
            'calendar' => [
                'data' => $calendar->getCollection()
                    ->map(function ($event) {
                        /** @var Calendar $event */
                        return $event->toFullArray();
                    }),
                'meta' => [
                    'total' => $calendar->total(),
                    'per_page' => $calendar->perPage(),
                    'current_page' => $calendar->currentPage(),
                    'last_page' => $calendar->lastPage(),
                    'from' => $calendar->firstItem(),
                    'to' => $calendar->lastItem(),
                ],
            ],
            'allCategories' => Category::all(['id', 'alias', 'name', 'description']),
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Calendar::class);

        return $this->inertiaResponse('Admin/Calendar/CreateCalendarEvent', [
            'assignableTenants' => GetTenantsForUpserts::execute('calendars.create.padalinys', $this->authorizer),
            'categories' => Category::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCalendarRequest $request)
    {
        $calendar = new Calendar;

        // safe(), not except(): on a FormRequest, $request->except() returns raw input minus the
        // named keys, so anything unvalidated would be mass-assigned straight through fill().
        $calendar = $calendar->fill($request->safe()->except(['images', 'main_image']));
        $calendar->category_id = $request->validated('category_id');

        $calendar->save();

        // Handle media uploads using centralized action
        HandleModelMediaUploads::execute($calendar, $request, [
            'main_image' => ['collection' => 'main_image', 'single' => true],
            'images' => ['collection' => 'images', 'single' => false],
        ]);

        return redirect()->route('calendar.index')->with('success', $this->entityMessage('created', 'calendar'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Calendar $calendar)
    {
        $this->handleAuthorization('view', $calendar);

        return $this->inertiaResponse('Admin/Calendar/ShowCalendarEvent', [
            'calendar' => $calendar,
            'images' => $calendar->getMedia('images'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Calendar $calendar)
    {
        $this->handleAuthorization('update', $calendar);

        return $this->inertiaResponse('Admin/Calendar/EditCalendarEvent', [
            'calendar' => [
                ...$calendar->toFullArray(),
                'images' => $calendar->getMedia('images')->map(
                    fn ($image) => [
                        'id' => $image->id,
                        'name' => $image->name,
                        'url' => $image->original_url,
                        'status' => 'finished',
                    ]
                ),
                'public_urls' => $calendar->publicUrls()->get(['id', 'url', 'locale', 'created_at']),
                // Informational only — still resolves live (PublicPageController::calendarLegacy()),
                // never stored, so nothing to delete here.
                'legacy_date_urls' => $this->legacyDateUrls($calendar),
            ],
            'categories' => Category::all(),
            'assignableTenants' => GetTenantsForUpserts::execute('calendars.update.padalinys', $this->authorizer),
            // An event standing for a meeting is not an ordinary event: publishing it is what
            // opens that meeting's agenda to the public, so the form has to say so.
            'meeting' => $this->announcedMeeting($calendar),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function legacyDateUrls(Calendar $calendar): array
    {
        return collect(['lt', 'en'])->mapWithKeys(function (string $locale) use ($calendar) {
            // useFallbackLocale: false — don't show an "English" legacy URL that's actually
            // just the Lithuanian title, for an event with no English title of its own.
            $title = $calendar->getTranslation('title', $locale, false);

            if (blank($title)) {
                return [$locale => null];
            }

            return [$locale => LocalizedRouteSlugs::route('calendar.event.legacy', [
                'year' => $calendar->date->format('Y'),
                'month' => $calendar->date->format('m'),
                'day' => $calendar->date->format('d'),
                'slug' => Str::slug($title),
            ], $locale)];
        })->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function announcedMeeting(Calendar $calendar): ?array
    {
        $meeting = $calendar->meeting;

        if ($meeting === null) {
            return null;
        }

        $meeting->loadMissing('institutions');

        return [
            'id' => $meeting->id,
            'start_time' => $meeting->start_time,
            'title' => $meeting->title,
            'trashed' => $meeting->trashed(),
            'agenda_items_count' => $meeting->agendaItems()->count(),
            'institution_name' => $meeting->institutions->first()?->name,
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCalendarRequest $request, Calendar $calendar)
    {
        DB::transaction(function () use ($request, $calendar): void {
            // Exclude file fields from fill. An event announcing a meeting also gives up its
            // timing: the meeting owns it and pushes changes down (Meeting::syncCalendarEventTiming),
            // so accepting a date here would only let the two drift. The form disables the
            // fields; this is what actually enforces it.
            $protected = ['images', 'main_image'];

            if ($calendar->meeting_id !== null) {
                $protected[] = 'date';
                $protected[] = 'end_date';
            }

            $calendar->fill($request->safe()->except($protected));
            $calendar->category_id = $request->validated('category_id');

            $calendar->save();

            // Handle media uploads using centralized action
            HandleModelMediaUploads::execute($calendar, $request, [
                'main_image' => ['collection' => 'main_image', 'single' => true],
                'images' => ['collection' => 'images', 'single' => false],
            ]);
        });

        return back()->with('success', $this->entityMessage('updated', 'calendar'));
    }

    public function duplicate(Calendar $calendar)
    {
        $this->handleAuthorization('create', Calendar::class);

        $newCalendar = DuplicateCalendarAction::execute($calendar);

        return redirect()->route('calendar.edit', $newCalendar->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Calendar $calendar)
    {
        $this->handleAuthorization('delete', $calendar);

        $calendar->delete();

        return redirect()->route('calendar.index')->with('info', $this->entityMessage('deleted', 'calendar'));
    }

    /**
     * Remove a legacy public URL (an old permalink that still 301-redirects here).
     */
    public function destroyPublicUrl(Calendar $calendar, PublicUrl $publicUrl): RedirectResponse
    {
        $this->handleAuthorization('update', $calendar);

        // Resolve through the relation so a crafted payload cannot reach another event's URLs.
        $publicUrlFromDb = $calendar->publicUrls()->find($publicUrl->id);

        abort_if($publicUrlFromDb === null, 403, 'Public URL does not belong to this calendar event.');

        $publicUrlFromDb->delete();

        return back()->with('info', $this->entityMessage('deleted', 'publicUrl'));
    }

    // TODO: something with this???
    public function destroyMedia(Calendar $calendar, Media $media)
    {
        $this->handleAuthorization('update', $calendar);

        $calendar->getMedia('images')->where('id', '=', $media->id)->first()?->delete();

        return back()->with('info', __('messages.calendar.image_deleted'));
    }

    public function restore(Calendar $calendar): RedirectResponse
    {
        return $this->restoreModel($calendar);
    }

    public function forceDelete(Calendar $calendar): RedirectResponse
    {
        return $this->forceDeleteModel($calendar);
    }
}
