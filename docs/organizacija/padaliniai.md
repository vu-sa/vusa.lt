---
doc_status: reviewed
title: Padaliniai
area: tenants
models: [Tenant]
last_reviewed: 2026-10-27
tests:
  - tests/Unit/Models/TenantTimestampTest.php
  - tests/Feature/Admin/Management/TenantControllerTest.php
  - resources/js/Features/Admin/Tenants/__tests__/TenantSheetForm.component.test.ts
  - resources/js/Pages/Admin/People/__tests__/IndexTenant.component.test.ts
  - tests/Browser/TenantPrimaryInstitutionTest.php
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

Redagavimo lange po laukais rodomos sukūrimo ir paskutinio atnaujinimo datos. Padaliniams šios datos kaupiamos nuo 2026-10-27: ankstesnė sukūrimo data lieka nežinoma, o atnaujinimo data atsiranda kitą kartą išsaugojus pakeitimus.

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

- Pasirenkama tik tam padaliniui priklausanti, neištrinta institucija. Pasirinkimą galima pakeisti arba išvalyti.
- Jos nuotrauka ir trumpas pavadinimas rodomi viešame padalinio pasirinkime; nuotrauka gali būti rodoma ir Mano VU SA pradžioje.
- Pagrindinė institucija iškeliama viešos kontaktų paieškos sąraše.
- Ji naudojama [narių registracijos](/organizacija/formos-ir-registracijos) patvirtinimo laiškuose ir pranešimuose. Gavėjai parenkami pagal padaliniui priskirtą narių registracijos atsakomybę, o ne institucijos vadovą.

Padalinio koordinatoriams ir kuratoriams skirtas kontaktų puslapis instituciją randa pagal sutampančius padalinio ir institucijos trumpinius (`alias`), o ne šį pasirinkimą.

<ChangelogNote date="2026-10-27" title="Pagrindinės institucijos pasirinkimas">
Instituciją gali rasti paieškoje redagavimo lange. Pasirinkta institucija taip pat rodoma padalinių sąraše.
</ChangelogNote>

## Veiksmai

### Padalinių sąrašas ir paieška {#sarasas}

Padalinių sąraše (`/mano/tenants`) pateikiami visi sistemos padaliniai:

- **Paieška**: galima ieškoti pagal padalinio pavadinimą, trumpinį ar subdomeną (`alias`).
- **Filtravimas pagal tipą**: šoninėje juostoje galima vienu paspaudimu atsirinkti fakultetinius padalinius, PKP ar pagrindinį biurą.
- **Rikiavimas**: pagal pavadinimą nuo A iki Z arba nuo Z iki A.
- **Pagrindinė institucija**: rodoma lentelėje ir kortelėse; jei nepasirinkta, rodoma „Nepriskirta“.

Kadangi padalinių skaičius organizacijoje yra nedidelis, visas sąrašas pateikiamas iš karto, o paieška ir rikiavimas atliekami naršyklėje.

### Kūrimas ir redagavimas {#kurimas}

Naujas padalinys kuriamas paspaudus mygtuką **Naujas padalinys** sąrašo viršuje, o esamas redaguojamas
paspaudus jo pavadinimo arba per veiksmų meniu **⋯ → Redaguoti**:

Atsidariusiame šoniniame lange (`TenantSheetForm`) užpildomi laukai:

| Laukas | Kur naudojamas |
|---|---|
| **Pilnas pavadinimas** (`fullname`) | Padalinių sąrašuose ir lietuviško viešo pradžios puslapio antraštėje. |
| **Trumpas pavadinimas** (`shortname`), pvz., VU SA MIF | Navigacijoje, filtruose ir kontaktų kortelėse. |
| **Tipas** | Atskiriami fakultetiniai padaliniai, PKP ir Centrinis biuras; atstovavimo rodikliai bei ataskaitos neapima PKP. |
| **Subdomenas** (`alias`), pvz., mif | Viešos svetainės adresui ir padalinio turinio parinkimui. |
| **Trumpinys VU** (`shortname_vu`), pvz., MIF | Fakulteto pavadinimui studijų medžiagos puslapyje ir padalinių pasirinkimų rikiavimui. |
| **Pagrindinė institucija** | Viešam padalinio pasirinkimui, pradžios nuotraukai, kontaktų paieškai ir narių registracijos laiškams; žr. [paskirtį](#pagrindine-institucija). |
| **Įjungti tikslus** | Įjungia tikslus ir veiksmus teisę juos valdyti turintiems nariams bei viešą padalinio tikslų puslapį. |

Pagrindinės institucijos laukas atveria paiešką tame pačiame lange. **Pasirinkti** patvirtina pasirinkimą,
**Išvalyti** paruošia tuščią pasirinkimą, o **Grįžti** atšaukia paieškoje padarytus pakeitimus.
Padalinio duomenys išsaugomi tik paspaudus **Išsaugoti**.

Naują padalinį pirmiausia išsaugok, tada sukurk jam instituciją ir priskirk ją kaip pagrindinę.
Jei anksčiau priskirta institucija priklauso kitam padaliniui, prieš išsaugant ją reikia pakeisti arba išvalyti.

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
| Pateikiama nario registracija | Pagrindinė institucija naudojama patvirtinimo laiške ir pranešimuose; ji turi būti priskirta | Registruojantis asmuo ir narių registracijos atsakomybę turinčios pareigybės |

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
