---
doc_status: reviewed
last_reviewed: 2026-10-07
title: Užklausos kelias
coverage: ignore
---

# Užklausos kelias

Beveik kiekviena užklausa eina tuo pačiu keliu: middleware → maršrutas → teisės ir validacija →
valdiklis → modelis → atsakymas → Vue puslapis. Skiriasi tik tai, kuriuo iš trijų maršrutų failų
ji ateina.

<ArchitectureFlow flow="requestLifecycle" />

## Middleware

Visa grandinė aprašyta `bootstrap/app.php`. Dauguma sluoksnių – įprasti Laravel (slapukai, sesija,
CSRF, modelių susiejimas) arba skirti tik staging aplinkai ir PWA. Kasdieniame darbe svarbūs trys:
`auth` (`/mano/*` be prisijungimo nukreipia į prisijungimą), `SetLocale` (kalba iš URL ar sesijos) ir
`HandleInertiaRequests` (bendri props kiekvienam puslapiui).

## Trys maršrutų failai

- **`routes/admin.php`** – Mano VU SA, prefiksas `/mano`, visada su prisijungimu. Maršrutų
  pavadinimai neturi `admin.` priešdėlio: `route('calendar.index')`.
- **`routes/web.php`** – vieša svetainė. Adresai prasideda kalba (`/lt/…`, `/en/…`), o padalinys
  nustatomas iš subdomeno (`mif.vusa.lt`). Paskutinis maršrutas `{permalink}` pagauna turinio
  puslapius, todėl nauji maršrutai rašomi virš jo.
- **`routes/api.php`** – `/api/v1/*` JSON atsakymams, kai puslapiui reikia duomenų be naujo
  apsilankymo. Naršyklėje juos skaito `useApi()`.

## Teisės ir validacija

Duomenis keičiantys veiksmai gauna **FormRequest**: `authorize()` patikrina teisę, `rules()` –
laukus. Valdiklis naudoja tik `$request->validated()` arba `safe()`. Teisės tikrinamos per Policy
ir `ModelAuthorizer`, o jų formatas – `{resource}.{action}.{scope}`, pvz. `calendars.create.padalinys`.
Plačiau – [Teisės ir rolės](/pagrindai/teises).

## Atsakymas

- **Skaitant** valdiklis grąžina `Inertia::render('Aplankas/Puslapis', [...props])`. Pirmo
  apsilankymo metu naršyklė gauna `app.blade.php` su duomenimis viduje, vėliau – tik JSON.
- **Įrašius** grąžinamas peradresavimas su žinute: `back()->with('success', …)`. Naršyklė atlieka
  naują GET, o žinutė parodoma kaip pranešimas.
- `HandleInertiaRequests` prie kiekvieno puslapio prideda `auth.user`, `auth.can`, `flash` ir kitus
  bendrus props.
- Naršyklėje `resources/js/admin.ts` arba `public.ts` randa `resources/js/Pages/{pavadinimas}.vue`
  ir prideda maketą.

## Klaidos

- **422** – validacijos klaidos grįžta į `form.errors` ir rodomos prie laukų.
- **403** – Inertia užklausai grąžinama atgal su klaidos pranešimu. Tiesioginis apsilankymas
  `/mano` puslapyje rodo puslapį „Šis puslapis tau neprieinamas“ su paaiškinimu, kokios teisės
  trūksta. API grąžina 403 JSON.
- **404** – įrašas nerastas. Vieši puslapiai pirmiau patikrina, ar nuoroda nebuvo pakeista, ir
  tada peradresuoja (301).

## Kas vyksta įrašius

Dalis darbo atliekama ne pačioje užklausoje. Modelis išvalo HTML, įrašo veiksmą į veiklos žurnalą
ir paleidžia savo kabliukus, o paieškos indeksavimas, laiškai ir ilgesni darbai keliauja į eilę.
Diagramoje kaip pavyzdys naudojamas renginys (`Calendar`).

<ArchitectureFlow flow="sideEffects" />

Eilę apdoroja atskiras procesas, todėl lokaliai, pakeitus darbą ar klausytoją, jį reikia
perkrauti. Periodinius darbus aprašo `routes/console.php`.
