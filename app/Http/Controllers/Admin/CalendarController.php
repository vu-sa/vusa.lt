<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BuildCalendarIndexQuery;
use App\Actions\DuplicateCalendarAction;
use App\Actions\GetTenantsForUpserts;
use App\Actions\HandleModelMediaUploads;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexCalendarRequest;
use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarIndexRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Calendar;
use App\Models\EventType;
use App\Models\PublicUrl;
use App\Models\Tag;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $calendar = BuildCalendarIndexQuery::execute($request, $this->tableService)
            ->paginate($request->getPerPage());

        return $this->inertiaResponse('Admin/Calendar/IndexCalendarEvents', [
            'calendar' => [
                'data' => $calendar->getCollection()->map(fn (Calendar $event): array => $event->toFullArray())->values(),
                'meta' => [
                    'total' => $calendar->total(),
                    'per_page' => $calendar->perPage(),
                    'current_page' => $calendar->currentPage(),
                    'last_page' => $calendar->lastPage(),
                ],
            ],
            'eventTypes' => EventType::query()->orderBy('sort_order')->get(['id', 'slug', 'name']),
            'deletedCount' => $this->scopedTrashedCount(Calendar::query(), 'tenant', 'calendars.read.padalinys'),
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
            'eventTypes' => EventType::query()->orderBy('sort_order')->get(),
            'availableTags' => Tag::orderBy('alias')->get()->map->toFullArray(),
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
        // `tags` isn't a column — fill() would try to write it as one — so it's excluded here
        // and synced through the relation below instead.
        $calendar = $calendar->fill($request->safe()->except(['images', 'main_image', 'tags']));
        $calendar->event_type_id = $request->validated('event_type_id');

        $calendar->save();

        $calendar->tags()->sync($request->validated('tags') ?? []);

        // Handle media uploads using centralized action
        HandleModelMediaUploads::execute($calendar, $request, [
            'main_image' => ['collection' => 'main_image', 'single' => true],
            'images' => ['collection' => 'images', 'single' => false],
        ]);

        return redirect()->route('calendar.index')->with('success', $this->entityMessage('created', 'calendar'));
    }

    /**
     * Display the specified resource.
     * Content objects (Decision O4) have no separate record page; the editor is canonical.
     */
    public function show(Calendar $calendar)
    {
        $this->handleAuthorization('view', $calendar);

        return redirect()->route('calendar.edit', $calendar);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Calendar $calendar)
    {
        $this->handleAuthorization('view', $calendar);

        $canUpdate = $request->user()->can('update', $calendar);
        $calendar->loadMissing('tenant:id,type,shortname');

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
                'tags' => $calendar->tags->pluck('id')->toArray(),
            ],
            'eventTypes' => EventType::query()->orderBy('sort_order')->get(),
            'availableTags' => Tag::orderBy('alias')->get()->map->toFullArray(),
            'assignableTenants' => $canUpdate
                ? GetTenantsForUpserts::execute('calendars.update.padalinys', $this->authorizer)
                : collect([$calendar->tenant]),
            'canUpdate' => $canUpdate,
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
            // `tags` isn't a column — fill() would try to write it as one — so it's excluded
            // here and synced through the relation below instead.
            $protected = ['images', 'main_image', 'tags'];

            if ($calendar->meeting_id !== null) {
                $protected[] = 'date';
                $protected[] = 'end_date';
            }

            $calendar->fill($request->safe()->except($protected));
            $calendar->event_type_id = $request->validated('event_type_id');

            $calendar->save();

            $calendar->tags()->sync($request->validated('tags') ?? []);

            // Handle media uploads using centralized action
            HandleModelMediaUploads::execute($calendar, $request, [
                'main_image' => ['collection' => 'main_image', 'single' => true],
                'images' => ['collection' => 'images', 'single' => false],
            ]);
        });

        return back()->with('success', $this->entityMessage('updated', 'calendar'));
    }

    public function updateIndex(UpdateCalendarIndexRequest $request, Calendar $calendar): RedirectResponse
    {
        $data = $request->validated();

        if (array_key_exists('is_draft', $data)) {
            $calendar->is_draft = $data['is_draft'];
        }

        if (array_key_exists('event_type_id', $data)) {
            $calendar->event_type_id = $data['event_type_id'];
        }

        $calendar->save();

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
