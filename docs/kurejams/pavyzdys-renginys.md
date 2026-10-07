---
doc_status: reviewed
last_reviewed: 2026-10-07
title: 'Pavyzdys: renginys'
tests:
  - tests/Feature/Admin/Calendar/CalendarControllerTest.php
  - tests/Feature/Public/CalendarCanonicalUrlTest.php
  - tests/Feature/Public/CalendarEventPageTest.php
---

# Pavyzdys: renginys

Koordinatorius Mano VU SA sukuria renginį, o studentas jį atidaro viešoje svetainėje. Kairėje
diagramos pusėje – įrašymas, dešinėje – skaitymas. Abu keliai susitinka ties `Calendar` modeliu.

<ArchitectureFlow flow="calendarExample" />

## 1. Forma siunčia duomenis

`CalendarForm.vue` laiko laukus `useForm()` objekte. Puslapis tik nurodo, kur juos siųsti.
`forceFormData` reikalingas, nes kartu keliauja nuotraukos.

```ts
// resources/js/Pages/Admin/Calendar/CreateCalendarEvent.vue
function handleCreateCalendar(form: unknown) {
  (form as InertiaForm<CalendarEventForm>).post(route('calendar.store'), {
    forceFormData: true,
  });
}
```

`route()` ateina iš Ziggy, todėl JavaScript pusėje naudojami tie patys maršrutų pavadinimai kaip ir
`routes/admin.php`.

## 2. Teisė ir validacija

Prieš kviečiant valdiklį Laravel sukuria `StoreCalendarRequest`. Jei `authorize()` grąžina `false`,
atsakymas yra 403, o jei `rules()` netenkinamos – 422, ir klaidos grįžta į `form.errors`.

```php
// app/Http/Requests/StoreCalendarRequest.php
public function authorize(): bool
{
    return $this->user()->can('create', Calendar::class);
}
```

Taisyklės paveldimos iš `CalendarRequest`. Ten `tenant_id` taisyklė leidžia pasirinkti tik tą
padalinį, kuriame naudotojas turi `calendars.create.padalinys` teisę.

## 3. Valdiklis įrašo

Valdiklis gauna jau patikrintus duomenis ir tik juos priskiria modeliui.

```php
// app/Http/Controllers/Admin/CalendarController.php
$calendar = $calendar->fill($request->safe()->except(['images', 'main_image', 'tags']));
$calendar->event_type_id = $request->validated('event_type_id');
$calendar->save();

$calendar->tags()->sync($request->validated('tags') ?? []);

HandleModelMediaUploads::execute($calendar, $request, [/* … */]);

return redirect()->route('calendar.index')->with('success', $this->entityMessage('created', 'calendar'));
```

`with('success', …)` patenka į sesiją, `HandleInertiaRequests` ją perduoda kaip `flash.success`, o
`useToasts()` parodo pranešimą sąrašo puslapyje.

## 4. Modelis susitvarko pats

Kas turi įvykti po kiekvieno įrašymo, aprašyta modelyje, ne valdiklyje. Taip tas pats veikia ir
redaguojant, ir kopijuojant renginį.

```php
// app/Models/Calendar.php
static::saved(function (self $calendar): void {
    Cache::tags(['calendar'])->flush();
    IcalendarService::clearCache();
    // …pakeitus nuorodą, senas adresas įsimenamas peradresavimui
});
```

Be to, `LogsModelActivity` įrašo pakeitimą į veiklos žurnalą, o Scout per eilę atnaujina Typesense
indeksą. Indeksuojami tik paskelbti renginiai (`shouldBeSearchable()`).

## 5. Studentas atidaro renginį

`GET /lt/kalendorius/2026/{permalink}` pasiekia `PublicPageController::calendarCanonical()`.
`PublicController` konstruktorius jau nustatė padalinį iš subdomeno.

```php
// app/Http/Controllers/Public/PublicPageController.php
$calendar = Calendar::query()
    ->whereYear('date', $year)
    ->where("permalink->{$lang}", $permalink)
    ->first();
```

Jei renginys nerastas, `PublicUrlService` patikrina, ar adresas nebuvo pakeistas (301), kitaip
grąžinama 404. Radus renginį, `calendarEventMain()` grąžina
`Inertia::render('Public/CalendarEvent', [...])` su SEO duomenimis, o `public.ts` parodo
`resources/js/Pages/Public/CalendarEvent.vue` viešame makete.

## Techninė informacija

Testai, kuriuose matyti šis kelias: teisės ir įrašymas (`CalendarControllerTest`), viešas adresas ir
peradresavimai (`CalendarCanonicalUrlTest`), viešas renginio puslapis (`CalendarEventPageTest`).
Paleisti juos galima su `vendor/bin/sail artisan test --compact tests/Feature/Admin/Calendar`.
