---
doc_status: reviewed
last_reviewed: 2026-10-07
title: Kūrėjams
coverage: ignore
---

# Kūrėjams

Šis skyrius skirtas žmogui, kuris pradeda prižiūrėti ar kurti vusa.lt kodą. Jis paaiškina, kaip
užklausa iš naršyklės keliauja per Laravel ir grįžta į Vue puslapį. Kaip naudotis platforma,
aprašo likusi gido dalis.

## Technologijos

vusa.lt – viena **Laravel** programa, kuri aptarnauja ir viešą svetainę, ir vidinę platformą
**Mano VU SA** (`/mano`). Puslapiai rašomi **Vue 3**, o tarp jų ir Laravel stovi **Inertia.js**.
Valdiklis grąžina ne HTML ir ne API atsakymą, o puslapio pavadinimą su duomenimis (props). Inertia
naršyklėje parodo atitinkamą Vue komponentą. Atskiro API puslapiams rašyti nereikia, o maršrutai,
teisės ir validacija lieka Laravel pusėje.

Jei esi matęs MVC, didžioji dalis bus pažįstama: maršrutas → valdiklis → modelis → vaizdas. Vaizdo
vietoje čia – Vue puslapis per Inertia. Programa didelė dėl funkcijų kiekio, ne dėl neįprastos
architektūros. Atidumo reikia tik keliose vietose:

- **Inertia** – valdiklis grąžina puslapio pavadinimą ir duomenis, ne Blade vaizdą ir ne API atsakymą.
- **Teisės** – `ModelAuthorizer` su sritimis (`*`, `padalinys`, `own`), gaunamomis per pareigybes ir
  roles. Tai daugiau nei įprastos Laravel policy.
- **Padalinys iš subdomeno** ir paskutinis `{permalink}` maršrutas viešoje svetainėje.
- **Darbas už valdiklio ribų** – modelių kabliukai ir eilė, o Typesense paieška klausiama tiesiai
  iš naršyklės.

Duomenys saugomi **MySQL**, talpykla, sesijos ir eilės veikia per **Redis**, paieška – per
**Typesense**. Lokaliai viskas paleidžiama per **Laravel Sail** (Docker).

## Kur kas yra

| Aplankas | Kas jame |
| --- | --- |
| `bootstrap/app.php` | Middleware grupės, maršrutų failų registracija, klaidų apdorojimas |
| `routes/admin.php`, `routes/web.php`, `routes/api.php` | `/mano/*`, viešų puslapių ir `/api/v1/*` maršrutai |
| `app/Http/Controllers` | Valdikliai: `Admin/`, `Public/`, `Api/` |
| `app/Http/Requests` | FormRequest klasės – teisės (`authorize()`) ir validacija (`rules()`) |
| `app/Policies`, `app/Services/ModelAuthorizer.php` | Kas ką gali daryti |
| `app/Models` | Eloquent modeliai |
| `app/Services`, `app/Actions` | Logika, kurią naudoja keli valdikliai |
| `resources/js/Pages` | Vue puslapiai, kuriuos grąžina `Inertia::render()` |
| `resources/js/Components` | Komponentai: `ui/` → `Patterns/` → srities aplankai → `Layouts/` |

## Kaip skaityti diagramas

Diagramos interaktyvios: užvedus pelę ant žingsnio arba jį paspaudus, šalia parodomas paaiškinimas
ir nuoroda į failą GitHub. Spalva rodo sluoksnį, punktyrinė rodyklė – darbą fone, raudona – klaidos kelią. Po kiekviena
diagrama tie patys žingsniai išvardyti tekstu.

- [Užklausos kelias](./uzklausos-kelias) – bendra schema ir kas vyksta įrašius duomenis.
- [Pavyzdys: renginys](./pavyzdys-renginys) – vienas renginys nuo sukūrimo Mano VU SA iki viešo puslapio.

## Toliau

Kodo taisyklės, kurių laikosi ir žmonės, ir AI agentai, surašytos repozitorijos
[`AGENTS.md`](https://github.com/vu-sa/vusa.lt/blob/main/AGENTS.md) ir `.ai/rules/` aplanke.
Smulkesni vadovai: [valdikliai](https://github.com/vu-sa/vusa.lt/blob/main/app/Http/Controllers/CLAUDE.md),
[komponentai](https://github.com/vu-sa/vusa.lt/blob/main/resources/js/Components/CLAUDE.md),
[testai](https://github.com/vu-sa/vusa.lt/blob/main/tests/CLAUDE.md).
