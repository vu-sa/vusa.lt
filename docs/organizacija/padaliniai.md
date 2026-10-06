---
doc_status: reviewed
title: Padaliniai
area: tenants
models: [Tenant]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Management/TenantControllerTest.php
  - tests/Feature/CrossTenantDutyTest.php
  - tests/Feature/System/TenantDataIsolationTest.php
  - tests/Unit/Models/TenantHostnameTest.php
---

# Padaliniai

**Padalinys** (`tenant`) – tai organizacinis VU SA vienetas: fakultetinis padalinys (pvz., VU SA MIF),
programos, klubai ir projektai (PKP) arba bendrasis Centrinis biuras.

Platformoje padalinių duomenys ir prieigos teisės atskiriami: beveik kiekvienas sistemos
įrašas (naujienos, puslapiai, pareigybės, institucijos, ištekliai, studijų programos) priklauso konkrečiam
padaliniui, o naudotojų teisės ir matomumas yra ribojami jų atstovaujamais padaliniais.

Padalinių valdymo skiltis pasiekiama adresu `/mano/tenants`.

## Kaip tai veikia

### Padalinių tipai {#tipai}

Sistemoje kiekvienas padalinys turi nustatytą **tipą** (`TenantType`):

| Tipas | Reikšmė | Paskirtis |
|---|---|---|
| **padalinys** | Fakultetinis padalinys | Studentų atstovybės padalinys (MIF, TF, TSPMI, FF ir kt.). Dalyvauja studentų atstovavime, turi koordinatorius ir kuratorius. |
| **pkp** | Programos, klubai, projektai | Studentų iniciatyvos ir organizacijos (pvz., VU debatų klubas, Teisės klinika ir kt.). Neturi formalaus fakultetinio atstovavimo valdymo organų. |
| **pagrindinis** | Centrinis biuras | Visos organizacijos lygmens padalinys (VU SA bendrai). Sistemoje yra tik viena tokia eilutė (`Tenant::main()`). |

Pagal tipą sistema atskiria atstovaujamuosius padalinius (`TenantType::representational()`): atstovavimo rodikliai,
posėdžių ataskaitos ir valdymo suvestinės apima tik `pagrindinis` ir `padalinys` tipo vienetus, neįtraukiant PKP.

### Subdomenas ir svetainės adresas {#alias}

Kiekvienas padalinys turi unikalų **subdomeną / kelią** (`alias`):

- Pavyzdžiui, padalinio trumpinys `mif` lemia, kad viešoje svetainėje padalinio informacija pasiekiama adresu `mif.vusa.lt`.
- Pagal subdomeną sistema nustato, kurio padalinio naujienos, renginiai, kontaktai ir puslapiai atvaizduojami lankytojui.

### Pagrindinė institucija {#pagrindine-institucija}

Padaliniui galima priskirti **pagrindinę instituciją** (`primary_institution_id`):

- Tai vadovaujantis padalinio organas (dažniausiai padalinio taryba arba valdyba).
- Pagal šią instituciją nustatomi padalinio vadovai (pirmininkas) ir nukreipiami su padaliniu susiję
  pranešimai (pavyzdžiui, [Narių registracijos](/organizacija/formos-ir-registracijos) anketos).

## Veiksmai

### Padalinių sąrašas ir paieška {#sarasas}

Padalinių sąraše (`/mano/tenants`) pateikiami visi sistemos padaliniai:

- **Paieška**: galima ieškoti pagal padalinio pavadinimą, trumpinį ar subdomeną (`alias`).
- **Filtravimas pagal tipą**: šoninėje juostoje galima vienu paspaudimu atsirinkti fakultetinius padalinius, PKP ar pagrindinį biurą.
- **Rikiavimas**: pagal pavadinimą nuo A iki Z arba nuo Z iki A.

Kadangi padalinių skaičius organizacijoje yra nedidelis, visas sąrašas pateikiamas iš karto, o paieška ir rikiavimas atliekami naršyklėje.

### Kūrimas ir redagavimas {#kurimas}

Naujas padalinys kuriamas paspaudus mygtuką **Naujas padalinys** sąrašo viršuje, o esamas redaguojamas
paspaudus jo pavadinimo arba per veiksmų meniu **⋯ → Redaguoti**:

Atsidariusiame šoniniame lange (`TenantSheetForm`) užpildomi laukai:

1. **Pavadinimas** (`fullname`) – visas oficialus padalinio pavadinimas (pvz., *VU SA Matematikos ir informatikos fakultete*).
2. **Trumpinys** (`shortname`) – padalinio trumpinys (pvz., *MIF*).
3. **Tipas** – pasirenkamas tipas iš sąrašo (*Padalinys*, *Programos, klubai, projektai*, *Centrinis biuras*).
4. **Subdomenas (alias)** – unikalus identifikatorius mažosiomis raidėmis (pvz., *mif*).
5. **VU trumpinys** (`shortname_vu`, neprivaloma) – universiteto naudojamas fakulteto trumpinys.
6. **Pagrindinė institucija** (neprivaloma) – padalinio pagrindinė institucija iš sąrašo.

### Padalinio pagrindinio puslapio redagavimas {#pagrindinis-puslapis}

Kiekvieno padalinio subdomenas turi savo pagrindinį reprezentacinį puslapį. Jo turinį redaguoja padalinio komunikacijos koordinatorius:

1. Atverk adresą `/mano/tenants/{tenant}/main-page/edit` (arba per svetainės valdymo meniu).
2. Pasirink redaguojamą kalbą (**LT** arba **EN**).
3. Naudok blokinį turinio redaktorių: dėliok reprezentacines karuseles, teksto blokus, mygtukus ir nuorodas.
4. Išsaugok pakeitimus. Sistema automatiškai išvalo atitinkamos kalbos pradinio puslapio talpyklą, todėl atnaujinimai viešoje svetainėje matomi iš karto.

### Padalinio šalinimas {#trynimas}

Padalinio ištrynimas yra griežtai apribotas saugumo taisyklėmis:

- Veiksmų meniu **Ištrinti** rodomas tik Superadministratoriui.
- **Galima ištrinti tik PKP tipo padalinius** (`TenantType::Pkp`).
- Fakultetinių padalinių ir Centrinio biuro ištrinti **negalima** – bandymą ištrinti sistema automatiškai atmeta, nes tai pažeistų su padaliniu susietų studentų atstovavimo istoriją, pareigybes ir dokumentus.

## Kas ką gali {#teises}

| Veiksmas | Narys | Komunikacijos koordinatorius | Centrinio biuro koordinatoriai | Superadministratorius |
|---|---|---|---|---|
| Matyti padalinių sąrašą | – | – | – | ✓ |
| Sukurti naują padalinį | – | – | – | ✓ |
| Redaguoti padalinio nustatymus | – | – | – | ✓ |
| Redaguoti padalinio pagrindinį puslapį | – | ✓, tik savo padalinio | ✓, visų padalinių | ✓ |
| Ištrinti PKP padalinį | – | – | – | ✓ |
| Ištrinti VU SA padalinį | – | – | – | – (blokuojama) |

::: warning Padalinių administravimas yra centralizuotas
Padalinių kūrimas, jų pavadinimų ar subdomenų keitimas yra centralizuota Superadministratoriaus
funkcija. Padalinių koordinatoriai kasdieniame darbe administruoja savo padalinio turinį, pareigybes
ir narius, tačiau pačios padalinio struktūros nekeičia.
:::

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas nutinka | Kas gauna |
|---|---|---|
| Išsaugomas padalinys ar pakeičiami jo duomenys | Automatiškai išvaloma padalinių paieškos ir sąrašų talpykla (`tenant:main`, `tenant:all`) | Sistemos komponentai |
| Atnaujinamas padalinio pagrindinis puslapis | Išvaloma atitinkamo subdomeno pagrindinio puslapio talpykla (`homepage_content_{id}_{locale}`) | Vieša svetainė |
| Padaliniui priskiriama pagrindinė institucija | Jos vadovai tampa padalinio vadovais sistemos pranešimų grandinėse | Padalinio pirmininkas |

Padalinių kūrimas ar redagavimas **nesiunčia** el. laiškų ar automatinių pranešimų nariams.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas tiems, kurie tvarko roles ar tikrina sistemos elgseną.

### Teisės

- Padalinių prieigą reglamentuoja `TenantPolicy`.
- `TenantPolicy::viewAny`, `view`, `create`, `update` reikalauja `User::isSuperAdmin()`.
- Padalinio pagrindinio puslapio redagavimą reglamentuoja `TenantPolicy::updateMainPage`: tikrinamas leidimas `pages.update.padalinys` konkrečiame padalinyje per `ModelAuthorizer::scope()->allowsTenant($tenant)`.
- Trynimas (`TenantPolicy::delete`) reikalauja `isSuperAdmin()` ir leidžiamas tik jei `$tenant->type === TenantType::Pkp`.

### Kaip tai įgyvendinta

- Modelis `Tenant` naudoja `Searchable` (Laravel Scout), `HasRelationships` ir `#[WithoutTimestamps]`.
- Išvardijamasis tipas `TenantType` apibrėžia tris padalinių tipus: `pagrindinis`, `padalinys`, `pkp`. Metodas `TenantType::representational()` grąžina tipus, dalyvaujančius atstovavime.
- Padalinio sukūrimas ir atnaujinimas valdomas per `TenantController::store` ir `update`, naudojant `StoreTenantRequest` ir `UpdateTenantRequest`.
- Pagrindinio puslapio turinys saugomas `TenantHomepageContent` modelyje, susietame su `Content` ir `ContentPart` blokais.
- Talpyklos atnaujinimas: `Tenant::booted()` kabliukas trina `tenant:main` ir `tenant:all` raktus; puslapio išsaugojimas `TenantController::updateMainPage` trina žymas `Cache::tags(['homepage'])`.
