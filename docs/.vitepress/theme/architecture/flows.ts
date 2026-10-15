/**
 * Diagrams for the developer chapter (`docs/kurejams/`, `docs/en/developers/`). Positions are placed
 * by hand, so no layout dependency is needed. Every `file` must exist in the repo; a test checks this.
 */

export type Locale = 'lt' | 'en'

export type Translated = Record<Locale, string>

export type FlowNodeKind = 'client' | 'http' | 'logic' | 'data' | 'async' | 'response' | 'error'

export interface FlowNode {
  id: string
  kind: FlowNodeKind
  label: Translated
  detail: Translated
  /** Repo-relative path, linked to GitHub. */
  file?: string
  position: { x: number, y: number }
}

export interface FlowEdge {
  source: string
  target: string
  label?: Translated
  branch?: 'error' | 'async'
}

export interface Flow {
  title: Translated
  nodes: FlowNode[]
  edges: FlowEdge[]
}

export const kindLabels: Record<FlowNodeKind, Translated> = {
  client: { lt: 'Naršyklė ir Vue', en: 'Browser and Vue' },
  http: { lt: 'Middleware ir maršrutai', en: 'Middleware and routes' },
  logic: { lt: 'Valdiklis, validacija, teisės', en: 'Controller, validation, permissions' },
  data: { lt: 'Modelis ir duomenų bazė', en: 'Model and database' },
  async: { lt: 'Fone (eilė, įvykiai)', en: 'In the background (queue, events)' },
  response: { lt: 'Atsakymas', en: 'Response' },
  error: { lt: 'Klaida', en: 'Error' },
}

const requestLifecycle: Flow = {
  title: { lt: 'Užklausos kelias', en: 'Request lifecycle' },
  nodes: [
    {
      id: 'browser',
      kind: 'client',
      label: { lt: 'Naršyklė', en: 'Browser' },
      detail: {
        lt: 'Pirmas apsilankymas – įprasta GET užklausa. Vėliau Inertia siunčia XHR užklausas su X-Inertia antrašte, o puslapis neperkraunamas.',
        en: 'The first visit is a plain GET. After that Inertia sends XHR requests with the X-Inertia header and the page never fully reloads.',
      },
      position: { x: 230, y: 0 },
    },
    {
      id: 'middleware',
      kind: 'http',
      label: { lt: 'Middleware', en: 'Middleware' },
      detail: {
        lt: 'Visa grandinė aprašyta bootstrap/app.php: slapukai, sesija, CSRF, modelių susiejimas, SetLocale (lt/en), HandleInertiaRequests ir keli staging / PWA sluoksniai. Įsidėmėtini: auth (/mano be prisijungimo nukreipia į prisijungimą), SetLocale ir HandleInertiaRequests.',
        en: 'The whole chain is defined in bootstrap/app.php: cookies, session, CSRF, route model binding, SetLocale (lt/en), HandleInertiaRequests and a few staging / PWA layers. Worth remembering: auth (/mano redirects guests to login), SetLocale and HandleInertiaRequests.',
      },
      file: 'bootstrap/app.php',
      position: { x: 230, y: 110 },
    },
    {
      id: 'routeAdmin',
      kind: 'http',
      label: { lt: '/mano/* – administravimas', en: '/mano/* – admin' },
      detail: {
        lt: 'routes/admin.php su web + auth middleware. Maršrutų pavadinimai be admin. priešdėlio, pvz. route(\'calendar.store\').',
        en: 'routes/admin.php with web + auth middleware. Route names have no admin. prefix, e.g. route(\'calendar.store\').',
      },
      file: 'routes/admin.php',
      position: { x: 0, y: 230 },
    },
    {
      id: 'routePublic',
      kind: 'http',
      label: { lt: 'Vieši puslapiai', en: 'Public pages' },
      detail: {
        lt: 'routes/web.php: /{lang}/… maršrutai su main grupe (viešoji navigacija). Pabaigoje – {permalink} maršrutas turinio puslapiams.',
        en: 'routes/web.php: /{lang}/… routes with the main group (public navigation). It ends with a catch-all {permalink} route for content pages.',
      },
      file: 'routes/web.php',
      position: { x: 230, y: 230 },
    },
    {
      id: 'routeApi',
      kind: 'http',
      label: { lt: '/api/v1/*', en: '/api/v1/*' },
      detail: {
        lt: 'routes/api.php su api grupe (be sesijos, su užklausų ribojimu). /api/v1/admin/* naudoja sesijos prisijungimą.',
        en: 'routes/api.php with the api group (no session, rate-limited). /api/v1/admin/* uses the session login.',
      },
      file: 'routes/api.php',
      position: { x: 460, y: 230 },
    },
    {
      id: 'formRequest',
      kind: 'logic',
      label: { lt: 'FormRequest', en: 'FormRequest' },
      detail: {
        lt: 'authorize() patikrina teisę, rules() – laukus. Valdiklis naudoja tik $request->validated() ar safe(), niekada $request->all().',
        en: 'authorize() checks the permission, rules() checks the fields. The controller only uses $request->validated() or safe(), never $request->all().',
      },
      file: 'app/Http/Requests',
      position: { x: 0, y: 340 },
    },
    {
      id: 'policy',
      kind: 'logic',
      label: { lt: 'Policy → ModelAuthorizer', en: 'Policy → ModelAuthorizer' },
      detail: {
        lt: 'Teisės formatas {resource}.{action}.{scope}, pvz. calendars.create.padalinys. ModelAuthorizer surenka teises iš naudotojo, jo pareigybių ir rolių.',
        en: 'Permissions look like {resource}.{action}.{scope}, e.g. calendars.create.padalinys. ModelAuthorizer resolves them from the user, their duties and roles.',
      },
      file: 'app/Services/ModelAuthorizer.php',
      position: { x: 0, y: 450 },
    },
    {
      id: 'adminController',
      kind: 'logic',
      label: { lt: 'Admin valdiklis', en: 'Admin controller' },
      detail: {
        lt: 'Paveldi AdminController. Kiekvienas veiksmas kviečia handleAuthorization() arba remiasi FormRequest authorize().',
        en: 'Extends AdminController. Every action calls handleAuthorization() or relies on the FormRequest authorize().',
      },
      file: 'app/Http/Controllers/AdminController.php',
      position: { x: 0, y: 560 },
    },
    {
      id: 'publicController',
      kind: 'logic',
      label: { lt: 'PublicController', en: 'PublicController' },
      detail: {
        lt: 'Konstruktorius iš subdomeno nustato padalinį (www → VU SA, mif → VU SA MIF). Valdikliai pasidalina kalbos perjungimo nuoroda per shareOtherLangURL().',
        en: 'The constructor resolves the tenant from the subdomain (www → VU SA, mif → VU SA MIF). Controllers share the language switch link through shareOtherLangURL().',
      },
      file: 'app/Http/Controllers/PublicController.php',
      position: { x: 230, y: 395 },
    },
    {
      id: 'apiController',
      kind: 'logic',
      label: { lt: 'API valdiklis', en: 'API controller' },
      detail: {
        lt: 'Paveldi ApiController ir ApiResponses: atsakymas visada { success, data, message? }.',
        en: 'Extends ApiController with ApiResponses: the response is always { success, data, message? }.',
      },
      file: 'app/Http/Controllers/Api/ApiController.php',
      position: { x: 460, y: 395 },
    },
    {
      id: 'services',
      kind: 'logic',
      label: { lt: 'Services / Actions', en: 'Services / Actions' },
      detail: {
        lt: 'Logika, kurią naudoja keli valdikliai, iškeliama į app/Services arba app/Actions (pvz. HandleModelMediaUploads).',
        en: 'Logic shared by several controllers lives in app/Services or app/Actions (e.g. HandleModelMediaUploads).',
      },
      file: 'app/Actions',
      position: { x: 230, y: 560 },
    },
    {
      id: 'model',
      kind: 'data',
      label: { lt: 'Eloquent modelis → MySQL', en: 'Eloquent model → MySQL' },
      detail: {
        lt: 'Modeliai saugo verčiamus laukus (lt/en), HTML išvalo įrašant ir patys registruoja veiksmus į veiklos žurnalą.',
        en: 'Models store translatable fields (lt/en), sanitize HTML on write and log changes to the activity log themselves.',
      },
      file: 'app/Models',
      position: { x: 230, y: 670 },
    },
    {
      id: 'sideEffects',
      kind: 'async',
      label: { lt: 'Šalutiniai efektai', en: 'Side effects' },
      detail: {
        lt: 'Paieškos indeksas, įvykiai, eilės darbai, pranešimai. Žr. antrą diagramą.',
        en: 'Search index, events, queued jobs, notifications. See the second diagram.',
      },
      position: { x: 460, y: 670 },
    },
    {
      id: 'redirect',
      kind: 'response',
      label: { lt: 'Redirect + flash', en: 'Redirect + flash' },
      detail: {
        lt: 'Po įrašymo grąžinamas back() arba to_route() su ->with(\'success\', …). Naršyklė atlieka naują GET, o žinutė parodoma kaip pranešimas (toast).',
        en: 'After a write the controller returns back() or to_route() with ->with(\'success\', …). The browser makes a new GET and the message shows as a toast.',
      },
      position: { x: 0, y: 790 },
    },
    {
      id: 'inertiaResponse',
      kind: 'response',
      label: { lt: 'Inertia::render()', en: 'Inertia::render()' },
      detail: {
        lt: 'Puslapio pavadinimas (pvz. Admin/Calendar/IndexCalendarEvents) ir props. Didelius, ne iš karto matomus duomenis galima atidėti su Inertia::defer().',
        en: 'A page name (e.g. Admin/Calendar/IndexCalendarEvents) and props. Large data that is not visible at first can be deferred with Inertia::defer().',
      },
      position: { x: 230, y: 790 },
    },
    {
      id: 'jsonResponse',
      kind: 'response',
      label: { lt: 'JSON atsakymas', en: 'JSON response' },
      detail: {
        lt: 'API atsakymus naršyklėje skaito useApi() kompozicija – kai duomenų reikia atnaujinti be puslapio perkrovimo.',
        en: 'API responses are read in the browser by the useApi() composable – when data needs refreshing without a page visit.',
      },
      file: 'resources/js/Composables/useApi.ts',
      position: { x: 460, y: 790 },
    },
    {
      id: 'sharedProps',
      kind: 'response',
      label: { lt: 'HandleInertiaRequests', en: 'HandleInertiaRequests' },
      detail: {
        lt: 'Prie kiekvieno puslapio prideda bendrus props: auth.user, auth.can, flash, app.locale ir kt.',
        en: 'Adds shared props to every page: auth.user, auth.can, flash, app.locale and more.',
      },
      file: 'app/Http/Middleware/HandleInertiaRequests.php',
      position: { x: 230, y: 900 },
    },
    {
      id: 'htmlOrJson',
      kind: 'response',
      label: { lt: 'HTML arba Inertia JSON', en: 'HTML or Inertia JSON' },
      detail: {
        lt: 'Pirmas apsilankymas gauna app.blade.php su puslapio duomenimis viduje. Kiti apsilankymai gauna tik JSON.',
        en: 'The first visit gets app.blade.php with the page data embedded. Later visits only get JSON.',
      },
      file: 'resources/views/app.blade.php',
      position: { x: 230, y: 1010 },
    },
    {
      id: 'vueEntry',
      kind: 'client',
      label: { lt: 'admin.ts / public.ts', en: 'admin.ts / public.ts' },
      detail: {
        lt: 'createInertiaApp randa resources/js/Pages/{pavadinimas}.vue, prideda maketą (AdminLayout arba viešą) ir vertimus.',
        en: 'createInertiaApp finds resources/js/Pages/{name}.vue and adds the layout (AdminLayout or the public one) and translations.',
      },
      file: 'resources/js/admin.ts',
      position: { x: 230, y: 1120 },
    },
    {
      id: 'page',
      kind: 'client',
      label: { lt: 'Vue puslapis', en: 'Vue page' },
      detail: {
        lt: 'Puslapis gauna props ir sudėliojamas iš komponentų (Layouts → Patterns → ui). Formos siunčia duomenis per useForm().',
        en: 'The page receives props and is composed from components (Layouts → Patterns → ui). Forms submit through useForm().',
      },
      file: 'resources/js/Pages',
      position: { x: 230, y: 1230 },
    },
    {
      id: 'errors',
      kind: 'error',
      label: { lt: '403 / 404 / 422', en: '403 / 404 / 422' },
      detail: {
        lt: '422: klaidos grįžta į form.errors. 403 Inertia užklausai: grįžtama atgal su klaidos pranešimu. Tiesioginis /mano apsilankymas be teisės: AccessDenied puslapis. API: 403 JSON.',
        en: '422: errors go back to form.errors. 403 on an Inertia request: back with an error toast. A direct /mano visit without permission: the AccessDenied page. API: 403 JSON.',
      },
      file: 'bootstrap/app.php',
      position: { x: -280, y: 395 },
    },
  ],
  edges: [
    { source: 'browser', target: 'middleware' },
    { source: 'middleware', target: 'routeAdmin' },
    { source: 'middleware', target: 'routePublic' },
    { source: 'middleware', target: 'routeApi' },
    { source: 'routeAdmin', target: 'formRequest' },
    { source: 'formRequest', target: 'policy' },
    { source: 'policy', target: 'adminController' },
    { source: 'formRequest', target: 'errors', branch: 'error', label: { lt: 'netinka', en: 'invalid' } },
    { source: 'policy', target: 'errors', branch: 'error', label: { lt: 'nėra teisės', en: 'denied' } },
    { source: 'routePublic', target: 'publicController' },
    { source: 'routeApi', target: 'apiController' },
    { source: 'adminController', target: 'services' },
    { source: 'publicController', target: 'services' },
    { source: 'apiController', target: 'services' },
    { source: 'services', target: 'model' },
    { source: 'model', target: 'sideEffects', branch: 'async' },
    { source: 'adminController', target: 'redirect', label: { lt: 'įrašius', en: 'after a write' } },
    { source: 'model', target: 'inertiaResponse', label: { lt: 'skaitant', en: 'on a read' } },
    { source: 'apiController', target: 'jsonResponse' },
    { source: 'redirect', target: 'inertiaResponse' },
    { source: 'inertiaResponse', target: 'sharedProps' },
    { source: 'sharedProps', target: 'htmlOrJson' },
    { source: 'htmlOrJson', target: 'vueEntry' },
    { source: 'vueEntry', target: 'page' },
  ],
}

const sideEffects: Flow = {
  title: { lt: 'Kas vyksta įrašius', en: 'What happens after a save' },
  nodes: [
    {
      id: 'fill',
      kind: 'data',
      label: { lt: 'fill() – HTML valymas', en: 'fill() – HTML sanitizing' },
      detail: {
        lt: 'HasTranslations išvalo raiškųjį tekstą (pvz. renginio aprašymą) jau priskiriant reikšmę, todėl jį išvalo bet kuris įrašymo kelias.',
        en: 'HasTranslations sanitizes rich text (e.g. the event description) as soon as it is assigned, so every write path is covered.',
      },
      file: 'app/Models/Traits/HasTranslations.php',
      position: { x: 230, y: 0 },
    },
    {
      id: 'save',
      kind: 'data',
      label: { lt: '$calendar->save()', en: '$calendar->save()' },
      detail: {
        lt: 'Eloquent įrašo eilutę į MySQL ir paleidžia modelio įvykius (saving, saved…).',
        en: 'Eloquent writes the row to MySQL and fires model events (saving, saved…).',
      },
      file: 'app/Models/Calendar.php',
      position: { x: 230, y: 110 },
    },
    {
      id: 'savedHook',
      kind: 'data',
      label: { lt: 'saved kabliukas', en: 'saved hook' },
      detail: {
        lt: 'Calendar::booted(): išvalo kalendoriaus ir iCal talpyklas, o pasikeitus nuorodai įsimena senąjį viešą URL peradresavimui.',
        en: 'Calendar::booted(): clears the calendar and iCal caches, and when the link changes, remembers the old public URL for a redirect.',
      },
      file: 'app/Models/Calendar.php',
      position: { x: 0, y: 230 },
    },
    {
      id: 'activityLog',
      kind: 'data',
      label: { lt: 'Veiklos žurnalas', en: 'Activity log' },
      detail: {
        lt: 'LogsModelActivity įrašo, kas ir ką pakeitė. Tai matoma įrašo istorijoje.',
        en: 'LogsModelActivity records who changed what. It shows up in the record history.',
      },
      file: 'app/Models/Traits/LogsModelActivity.php',
      position: { x: 230, y: 230 },
    },
    {
      id: 'scout',
      kind: 'async',
      label: { lt: 'Scout → eilė', en: 'Scout → queue' },
      detail: {
        lt: 'Searchable modelis po įrašymo įdeda indeksavimo darbą į eilę (SCOUT_QUEUE).',
        en: 'A Searchable model queues an indexing job after the save (SCOUT_QUEUE).',
      },
      file: 'config/scout.php',
      position: { x: 460, y: 230 },
    },
    {
      id: 'events',
      kind: 'async',
      label: { lt: 'Įvykiai → klausytojai', en: 'Events → listeners' },
      detail: {
        lt: 'Kiti modeliai skelbia savo įvykius (pvz. CommentPosted). Klausytojai registruojami ranka EventServiceProvider – automatinio radimo nėra.',
        en: 'Other models dispatch their own events (e.g. CommentPosted). Listeners are registered by hand in EventServiceProvider – there is no auto-discovery.',
      },
      file: 'app/Providers/EventServiceProvider.php',
      position: { x: 230, y: 350 },
    },
    {
      id: 'worker',
      kind: 'async',
      label: { lt: 'Eilės darbininkas', en: 'Queue worker' },
      detail: {
        lt: 'Redis eilę apdoroja atskiras procesas (supervisor serveryje, Sail lokaliai). Lėti darbai neužlaiko atsakymo.',
        en: 'A separate process works the Redis queue (supervisor on the server, Sail locally). Slow work never holds up the response.',
      },
      file: 'config/queue.php',
      position: { x: 460, y: 350 },
    },
    {
      id: 'typesense',
      kind: 'async',
      label: { lt: 'Typesense indeksas', en: 'Typesense index' },
      detail: {
        lt: 'Vieša ir administravimo paieška naršyklėje tiesiogiai klausia Typesense su ribotu raktu.',
        en: 'Public and admin search query Typesense directly from the browser with a scoped key.',
      },
      file: 'app/Services/Typesense',
      position: { x: 460, y: 470 },
    },
    {
      id: 'jobs',
      kind: 'async',
      label: { lt: 'Darbai (Jobs)', en: 'Jobs' },
      detail: {
        lt: 'Ilgesni darbai, pvz. SharePoint sinchronizavimas, vykdomi eilėje.',
        en: 'Longer work, e.g. SharePoint sync, runs on the queue.',
      },
      file: 'app/Jobs',
      position: { x: 230, y: 470 },
    },
    {
      id: 'notifications',
      kind: 'async',
      label: { lt: 'Pranešimai', en: 'Notifications' },
      detail: {
        lt: 'Laiškai, pranešimai platformoje ir push. Realiu laiku per Reverb į echo.ts.',
        en: 'Email, in-app and push notifications. Real-time ones go through Reverb to echo.ts.',
      },
      file: 'app/Notifications',
      position: { x: 0, y: 470 },
    },
    {
      id: 'scheduler',
      kind: 'async',
      label: { lt: 'Planuoklis', en: 'Scheduler' },
      detail: {
        lt: 'routes/console.php: periodiniai darbai ir komandos, pvz. SharePoint sinchronizavimas, priminimai apie posėdžius ir vėluojančias užduotis.',
        en: 'routes/console.php: periodic jobs and commands, e.g. SharePoint sync, meeting and overdue task reminders.',
      },
      file: 'routes/console.php',
      position: { x: 0, y: 350 },
    },
  ],
  edges: [
    { source: 'fill', target: 'save' },
    { source: 'save', target: 'savedHook' },
    { source: 'save', target: 'activityLog' },
    { source: 'save', target: 'scout', branch: 'async' },
    { source: 'save', target: 'events', branch: 'async', label: { lt: 'kiti modeliai', en: 'other models' } },
    { source: 'scout', target: 'worker', branch: 'async' },
    { source: 'worker', target: 'typesense', branch: 'async' },
    { source: 'events', target: 'jobs', branch: 'async' },
    { source: 'events', target: 'notifications', branch: 'async' },
    { source: 'scheduler', target: 'notifications', branch: 'async' },
    { source: 'scheduler', target: 'jobs', branch: 'async' },
  ],
}

const calendarExample: Flow = {
  title: { lt: 'Pavyzdys: renginys kalendoriuje', en: 'Example: a calendar event' },
  nodes: [
    {
      id: 'form',
      kind: 'client',
      label: { lt: 'CreateCalendarEvent.vue', en: 'CreateCalendarEvent.vue' },
      detail: {
        lt: 'CalendarForm.vue laiko laukus useForm() objekte. Pateikus kviečiama form.post(route(\'calendar.store\'), { forceFormData: true }) – nuotraukos keliauja multipart užklausa.',
        en: 'CalendarForm.vue keeps the fields in a useForm() object. Submitting calls form.post(route(\'calendar.store\'), { forceFormData: true }) – images travel as a multipart request.',
      },
      file: 'resources/js/Pages/Admin/Calendar/CreateCalendarEvent.vue',
      position: { x: 0, y: 0 },
    },
    {
      id: 'adminRoute',
      kind: 'http',
      label: { lt: 'POST /mano/calendar', en: 'POST /mano/calendar' },
      detail: {
        lt: 'Route::resource(\'calendar\', …) su web + auth middleware. Precognition leidžia formai tikrinti laukus dar prieš pateikimą.',
        en: 'Route::resource(\'calendar\', …) with web + auth middleware. Precognition lets the form validate fields before submitting.',
      },
      file: 'routes/admin.php',
      position: { x: 0, y: 110 },
    },
    {
      id: 'authorize',
      kind: 'logic',
      label: { lt: 'StoreCalendarRequest::authorize()', en: 'StoreCalendarRequest::authorize()' },
      detail: {
        lt: 'can(\'create\', Calendar::class) → CalendarPolicy → ModelAuthorizer patikrina calendars.create.* teisę.',
        en: 'can(\'create\', Calendar::class) → CalendarPolicy → ModelAuthorizer checks a calendars.create.* permission.',
      },
      file: 'app/Http/Requests/StoreCalendarRequest.php',
      position: { x: 0, y: 220 },
    },
    {
      id: 'rules',
      kind: 'logic',
      label: { lt: 'CalendarRequest::rules()', en: 'CalendarRequest::rules()' },
      detail: {
        lt: 'Pavadinimas abiem kalbomis, datos, padalinys (tik tas, kuriam turi teisę) ir kiti laukai.',
        en: 'Title in both languages, dates, tenant (only one you have permission for) and other fields.',
      },
      file: 'app/Http/Requests/CalendarRequest.php',
      position: { x: 0, y: 330 },
    },
    {
      id: 'denied',
      kind: 'error',
      label: { lt: '403 → klaidos pranešimas', en: '403 → error toast' },
      detail: {
        lt: 'Inertia užklausai grąžinama back() su error flash – forma lieka atidaryta.',
        en: 'An Inertia request gets back() with an error flash – the form stays open.',
      },
      file: 'bootstrap/app.php',
      position: { x: 230, y: 220 },
    },
    {
      id: 'invalid',
      kind: 'error',
      label: { lt: '422 → form.errors', en: '422 → form.errors' },
      detail: {
        lt: 'Klaidos parodomos prie laukų, įvesti duomenys neprarandami.',
        en: 'Errors show next to the fields and the input is kept.',
      },
      file: 'resources/js/Components/AdminForms/CalendarForm.vue',
      position: { x: 230, y: 330 },
    },
    {
      id: 'store',
      kind: 'logic',
      label: { lt: 'CalendarController::store()', en: 'CalendarController::store()' },
      detail: {
        lt: 'fill($request->safe()->except([…])), save(), tags()->sync() ir HandleModelMediaUploads nuotraukoms.',
        en: 'fill($request->safe()->except([…])), save(), tags()->sync() and HandleModelMediaUploads for the images.',
      },
      file: 'app/Http/Controllers/Admin/CalendarController.php',
      position: { x: 0, y: 440 },
    },
    {
      id: 'calendarModel',
      kind: 'data',
      label: { lt: 'Calendar → MySQL', en: 'Calendar → MySQL' },
      detail: {
        lt: 'Renginys įrašomas į calendars lentelę, nuotraukos – per Spatie Media Library.',
        en: 'The event is written to the calendars table, images through Spatie Media Library.',
      },
      file: 'app/Models/Calendar.php',
      position: { x: 230, y: 550 },
    },
    {
      id: 'calendarEffects',
      kind: 'async',
      label: { lt: 'Talpyklos, žurnalas, Typesense', en: 'Caches, log, Typesense' },
      detail: {
        lt: 'saved kabliukas išvalo talpyklas, veiklos žurnalas įrašo pakeitimą, o Scout per eilę atnaujina paieškos indeksą (tik nejuodraštinius renginius).',
        en: 'The saved hook clears caches, the activity log records the change and Scout updates the search index through the queue (only events that are not drafts).',
      },
      file: 'app/Models/Calendar.php',
      position: { x: 230, y: 660 },
    },
    {
      id: 'redirectIndex',
      kind: 'response',
      label: { lt: 'redirect → calendar.index', en: 'redirect → calendar.index' },
      detail: {
        lt: '->with(\'success\', …) įdeda žinutę į sesiją. Naršyklė atlieka GET /mano/calendar.',
        en: '->with(\'success\', …) puts the message in the session. The browser makes GET /mano/calendar.',
      },
      position: { x: 0, y: 660 },
    },
    {
      id: 'indexPage',
      kind: 'client',
      label: { lt: 'IndexCalendarEvents.vue + toast', en: 'IndexCalendarEvents.vue + toast' },
      detail: {
        lt: 'HandleInertiaRequests perduoda flash.success, o useToasts() jį parodo kaip pranešimą.',
        en: 'HandleInertiaRequests passes flash.success and useToasts() shows it as a toast.',
      },
      file: 'resources/js/Composables/useToasts.ts',
      position: { x: 0, y: 770 },
    },
    {
      id: 'publicRoute',
      kind: 'http',
      label: { lt: 'GET /lt/kalendorius/2026/{permalink}', en: 'GET /en/calendar/2026/{permalink}' },
      detail: {
        lt: 'Lankytojas atidaro renginį. Maršrutas routes/web.php, grupė main (viešoji navigacija), SetLocale nustato kalbą iš URL.',
        en: 'A visitor opens the event. The route is in routes/web.php, in the main group (public navigation); SetLocale takes the language from the URL.',
      },
      file: 'routes/web.php',
      position: { x: 460, y: 0 },
    },
    {
      id: 'tenant',
      kind: 'logic',
      label: { lt: 'PublicController – padalinys', en: 'PublicController – tenant' },
      detail: {
        lt: 'Iš subdomeno nustatomas padalinys, nuo jo priklauso navigacija, baneriai ir nuorodos.',
        en: 'The tenant is resolved from the subdomain; the navigation, banners and links depend on it.',
      },
      file: 'app/Http/Controllers/PublicController.php',
      position: { x: 460, y: 110 },
    },
    {
      id: 'canonical',
      kind: 'logic',
      label: { lt: 'calendarCanonical()', en: 'calendarCanonical()' },
      detail: {
        lt: 'Ieško renginio pagal metus ir nuorodą (permalink) pasirinkta kalba.',
        en: 'Looks up the event by year and permalink in the chosen language.',
      },
      file: 'app/Http/Controllers/Public/PublicPageController.php',
      position: { x: 460, y: 220 },
    },
    {
      id: 'notFound',
      kind: 'error',
      label: { lt: '301 arba 404', en: '301 or 404' },
      detail: {
        lt: 'Jei nuoroda buvo pakeista, PublicUrlService peradresuoja į naują adresą (301). Kitaip – 404.',
        en: 'If the link was changed, PublicUrlService redirects to the new address (301). Otherwise 404.',
      },
      file: 'app/Services/PublicUrlService.php',
      position: { x: 740, y: 220 },
    },
    {
      id: 'eventMain',
      kind: 'response',
      label: { lt: 'Inertia::render(\'Public/CalendarEvent\')', en: 'Inertia::render(\'Public/CalendarEvent\')' },
      detail: {
        lt: 'calendarEventMain() sudeda renginį, nuotraukas, susijusius renginius ir SEO duomenis (applyPageHead, JSON-LD).',
        en: 'calendarEventMain() adds the event, images, related events and SEO data (applyPageHead, JSON-LD).',
      },
      file: 'app/Http/Controllers/Public/PublicPageController.php',
      position: { x: 460, y: 660 },
    },
    {
      id: 'publicPage',
      kind: 'client',
      label: { lt: 'public.ts → CalendarEvent.vue', en: 'public.ts → CalendarEvent.vue' },
      detail: {
        lt: 'Puslapis parodomas viešame makete, kurį public.ts priskiria visiems Public/* puslapiams.',
        en: 'The page renders in the public layout that public.ts assigns to every Public/* page.',
      },
      file: 'resources/js/Pages/Public/CalendarEvent.vue',
      position: { x: 460, y: 770 },
    },
  ],
  edges: [
    { source: 'form', target: 'adminRoute' },
    { source: 'adminRoute', target: 'authorize' },
    { source: 'authorize', target: 'rules' },
    { source: 'authorize', target: 'denied', branch: 'error' },
    { source: 'rules', target: 'invalid', branch: 'error' },
    { source: 'rules', target: 'store' },
    { source: 'store', target: 'calendarModel', label: { lt: 'įrašo', en: 'writes' } },
    { source: 'calendarModel', target: 'calendarEffects', branch: 'async' },
    { source: 'store', target: 'redirectIndex' },
    { source: 'redirectIndex', target: 'indexPage' },
    { source: 'publicRoute', target: 'tenant' },
    { source: 'tenant', target: 'canonical' },
    { source: 'canonical', target: 'notFound', branch: 'error', label: { lt: 'nerasta', en: 'not found' } },
    { source: 'canonical', target: 'calendarModel', label: { lt: 'skaito', en: 'reads' } },
    { source: 'calendarModel', target: 'eventMain' },
    { source: 'eventMain', target: 'publicPage' },
  ],
}

export const flows = { requestLifecycle, sideEffects, calendarExample } satisfies Record<string, Flow>

export type FlowName = keyof typeof flows

export const repositoryUrl = 'https://github.com/vu-sa/vusa.lt'

export function fileUrl(file: string): string {
  return `${repositoryUrl}/blob/main/${file}`
}
