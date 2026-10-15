---
title: 'Example: a calendar event'
---

# Example: a calendar event

A coordinator creates an event in Mano VU SA, and a student opens it on the public website. The
left side of the diagram is the write, the right side the read. Both paths meet at the `Calendar`
model.

<ArchitectureFlow flow="calendarExample" />

## 1. The form submits

`CalendarForm.vue` keeps the fields in a `useForm()` object. The page only says where to send them.
`forceFormData` is needed because images travel along.

```ts
// resources/js/Pages/Admin/Calendar/CreateCalendarEvent.vue
function handleCreateCalendar(form: unknown) {
  (form as InertiaForm<CalendarEventForm>).post(route('calendar.store'), {
    forceFormData: true,
  });
}
```

`route()` comes from Ziggy, so the JavaScript side uses the same route names as `routes/admin.php`.

## 2. Permission and validation

Before the controller runs, Laravel builds `StoreCalendarRequest`. If `authorize()` returns `false`
the response is 403; if `rules()` fail it is 422 and the errors go back to `form.errors`.

```php
// app/Http/Requests/StoreCalendarRequest.php
public function authorize(): bool
{
    return $this->user()->can('create', Calendar::class);
}
```

The rules come from `CalendarRequest`. Its `tenant_id` rule only allows a tenant where the user
holds `calendars.create.padalinys`.

## 3. The controller saves

The controller receives validated data and only assigns that to the model.

```php
// app/Http/Controllers/Admin/CalendarController.php
$calendar = $calendar->fill($request->safe()->except(['images', 'main_image', 'tags']));
$calendar->event_type_id = $request->validated('event_type_id');
$calendar->save();

$calendar->tags()->sync($request->validated('tags') ?? []);

HandleModelMediaUploads::execute($calendar, $request, [/* … */]);

return redirect()->route('calendar.index')->with('success', $this->entityMessage('created', 'calendar'));
```

`with('success', …)` goes into the session, `HandleInertiaRequests` passes it on as `flash.success`,
and `useToasts()` shows the toast on the list page.

## 4. The model takes care of itself

What must happen after every save lives in the model, not the controller, so it works the same
for editing and duplicating an event.

```php
// app/Models/Calendar.php
static::saved(function (self $calendar): void {
    Cache::tags(['calendar'])->flush();
    IcalendarService::clearCache();
    // …when the link changes, the old address is kept for a redirect
});
```

On top of that, `LogsModelActivity` records the change in the activity log and Scout updates the
Typesense index through the queue. Only published events are indexed (`shouldBeSearchable()`).

## 5. A student opens the event

`GET /en/calendar/2026/{permalink}` reaches `PublicPageController::calendarCanonical()`. The
`PublicController` constructor has already resolved the tenant from the subdomain.

```php
// app/Http/Controllers/Public/PublicPageController.php
$calendar = Calendar::query()
    ->whereYear('date', $year)
    ->where("permalink->{$lang}", $permalink)
    ->first();
```

If no event matches, `PublicUrlService` checks whether the address was changed (301), otherwise
it returns 404. When the event is found, `calendarEventMain()` returns
`Inertia::render('Public/CalendarEvent', [...])` with SEO data, and `public.ts` renders
`resources/js/Pages/Public/CalendarEvent.vue` in the public layout.

## Technical details

Tests that cover this path: permissions and saving (`tests/Feature/Admin/Calendar/CalendarControllerTest.php`),
the public address and redirects (`tests/Feature/Public/CalendarCanonicalUrlTest.php`), and the
public event page (`tests/Feature/Public/CalendarEventPageTest.php`). Run them with
`vendor/bin/sail artisan test --compact tests/Feature/Admin/Calendar`.
