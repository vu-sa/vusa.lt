---
doc_status: reviewed
title: Baneriai
area: banners
models: [Banner]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Content/BannerControllerTest.php
  - tests/Feature/Api/Admin/BannerApiControllerTest.php
  - tests/Browser/BannerIndexTest.php
  - resources/js/Components/AdminForms/__tests__/BannerForm.component.test.ts
  - resources/js/Components/Public/FullWidth/__tests__/PartnersBanner.component.test.ts
---

# Baneriai

Baneriai – grafiniai skydeliai su nuorodomis, viešos svetainės apačioje rodomi karuselėje. Jie skirti partneriams, rėmėjams, VU SA programoms, klubams ir projektams (PKP) bei specialioms akcijoms viešinti.

Skiltis pasiekiama adresu `/mano/banners`.

## Rekomendacijos {#rekomendacijos}

::: tip
- **Aiškus vaizdas:** parink paveikslėlį, kuriame logotipas ar pagrindinė žinutė būtų įskaitomi ir telefone. Apkarpydamas vaizdą patikrink, ar nenukerpi svarbių jo dalių.
- **Veikianti nuoroda:** prieš skelbdamas atverk banerio nuorodą ir patikrink, ar ji veda į numatytą puslapį. Jei svetainė palaiko HTTPS, naudok adresą su `https://`.
- **Aktualumas:** pasibaigus akcijai arba kai banerio informacija nebeaktuali, pažymėk jį kaip neaktyvų arba pašalink.
:::

## Kaip tai veikia

### Rodymas svetainėje ir prioritetas

Viešos svetainės puslapiuose baneriai pateikiami apačioje, virš poraštės. Karuselė slenka automatiškai; ją gali paslinkti ir pats:

- **Padalinio baneriai rodomi pirmiausia.** Jei tavo padalinys (pvz., VU SA MIF) turi aktyvių banerių, lankytojui MIF polapyje pirmiausia bus rodomi šie baneriai.
- **Bendri VU SA baneriai rodomi visada.** Po padalinio banerių karuselėje rodomi bendri centrinio biuro paskelbti baneriai. Jei padalinys savų banerių neturi, rodomi tik bendri baneriai.
- Paspaudus banerio, lankytojas naujame naršyklės skirtuke nukreipiamas į nurodytą partnerio ar projekto svetainę.

### Būsenos

Kiekvienas baneris turi vieną iš dviejų būsenų:

| Būsena | Reikšmė | Matomumas |
|---|---|---|
| **Aktyvus** (`active`) | Baneris įtrauktas į viešą karuselę. | Matomas viešoje svetainėje. |
| **Neaktyvus** (`inactive`) | Baneris išsaugotas sistemoje, bet nerodomas lankytojams. | Matomas tik administravimo sąraše. |

## Veiksmai

### Banerių sąrašas ir greitas būsenos keitimas

Banerių sąraše (`/mano/banners`) pateikiami visi pasiekiami baneriai:

- **Peržiūra užvedus:** užvedus pelės žymeklį ant banerio miniatiūros sąraše, iššokančiame lange parodomas didesnis paveikslėlis.
- **Tiesioginis būsenos perjungimas:** sąrašo stulpelyje „Būsena“ gali tiesiogiai pakeisti būseną (iš „Aktyvus“ į „Neaktyvus“ ir atvirkščiai) neatverdamas redagavimo formos. Pakeitimas iškart atnaujina viešą karuselę.
- **Filtrai ir paieška:** gali ieškoti banerių pagal pavadinimą arba filtruoti pagal aktyvumo būseną.
- **Išorinė nuoroda:** sąrašo eilutėje rodoma nuoroda; paspaudęs piktogramą šalia gali tiesiogiai patikrinti, ar nuoroda veikia.

### Naujo banerio kūrimas

Puslapio viršuje spausk **Naujas baneris** (arba eik adresu `/mano/banners/create`):

1. **Pavadinimas** – įrašyk partnerio arba akcijos pavadinimą (privalomas laukas). Jis padeda atpažinti banerį sąraše ir naudojamas kaip alternatyvusis tekstas ekrano skaitytuvams.
2. **Nuoroda** – įklijuok visą adresą (su `https://`), į kurį lankytojas pateks paspaudęs banerį. Šalia esantis mygtukas leidžia atidaryti ir patikrinti įvestą adresą naujame lange.
3. **Paveikslėlis** – įkelk skydelio failą. Įkėlimo lange veikia vaizdo apkarpymo įrankis, leidžiantis suderinti tinkamas proporcijas.
4. **Būsena** – dešiniajame skydelyje nustatyk, ar baneris iškart taps aktyvus, ar bus neaktyvus.
5. Spausk **Išsaugoti**.

Naujai sukurtas baneris automatiškai priskiriamas tavo atstovaujamam padaliniui (arba bendrai VU SA, jei turi centrinio biuro teises).

### Redagavimas

Norėdamas pakeisti banerio nuorodą, pavadinimą ar paveikslėlį, sąraše paspausk banerio pavadinimo arba pasirink veiksmų meniu **Redaguoti**. Atlikęs pakeitimus spausk **Išsaugoti**.

### Šalinimas ir atkūrimas

- **Perkėlimas į šiukšlinę:** veiksmų meniu **⋯** pasirink **Ištrinti**. Baneris perkeliamas į šiukšlinę ir nustoja būti rodomas svetainėje.
- **Šiukšlinės peržiūra:** sąrašo viršuje spausk mygtuką **Šiukšlinė**. Čia pateikiami pašalinti baneriai.
- **Atkūrimas:** šiukšlinėje prie pašalinto banerio pasirink **Atkurti**. Baneris grąžinamas į sąrašą.
- **Galutinis ištrynimas:** šiukšlinėje pasirink **Ištrinti visam laikui**. Šis veiksmas negrįžtamas.

## Kas ką gali {#teises}

| Veiksmas | Narys be rolės | Komunikacijos koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|---|
| Matyti sąrašą | – | ✓ (savo padalinio) | ✓ (visus padalinius) |
| Sukurti banerį | – | ✓ (savo padaliniui) | ✓ (visai organizacijai ir padaliniams) |
| Keisti būseną (aktyvus / neaktyvus) | – | ✓ (savo padalinio) | ✓ (visų) |
| Redaguoti banerį | – | ✓ (savo padalinio) | ✓ (visų) |
| Ištrinti į šiukšlinę / atkurti | – | ✓ (savo padalinio) | ✓ (visų) |
| Ištrinti visam laikui | – | ✓ (savo padalinio) | ✓ (visų) |

::: info Padalinio ir centrinio biuro atskyrimas
Padalinio komunikacijos koordinatorius mato ir tvarko tik savo padalinio banerius. Centrinio biuro komunikacijos koordinatorius mato visų padalinių skydelius ir kuria bendrus, visoje svetainėje matomus banerius.
:::

## Pranešimai ir automatizavimas {#pranesimai}

- Sukūrus, pakeitus ar ištrynus banerį pranešimai ar el. laiškai naudotojams nesiunčiami.
- **Automatinis talpyklos išvalymas:** kiekvieną kartą išsaugojus banerį arba pakeitus jo aktyvumo būseną, sistema automatiškai išvalo atitinkamo padalinio banerių talpyklą (`banners-{tenant_id}`). Pakeitimas viešoje svetainėje pasirodo be rankinio talpyklos atnaujinimo.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Banerių prieigos teises tikrina `BannerPolicy`.
- Padalinio komunikacijos koordinatoriaus rolė apima leidimus `banners.create.padalinys`, `banners.read.padalinys`, `banners.update.padalinys`, `banners.delete.padalinys`.
- Centrinio biuro komunikacijos koordinatoriaus rolė apima visos platformos leidimus `banners.create.*`, `banners.read.*`, `banners.update.*`, `banners.delete.*`.

### Kaip tai įgyvendinta

- Modelis: `App\Models\Banner`, naudojantis `SoftDeletes`.
- Administravimo valdiklis: `App\Http\Controllers\Admin\BannerController`.
- API valdiklis: `App\Http\Controllers\Api\Admin\BannerApiController` (palaiko greitąjį filtravimą pagal `is_active` ir bendrąsias TanStack lentelių užklausas).
- Greitasis būsenos atnaujinimas atliekamas maršrutu `PATCH /mano/banners/{banner}/status` (`banners.updateStatus`), valdomu per `UpdateBannerStatusRequest`.
- Paveikslėlių failai keliami į viešą saugyklą aplanke `banners`.
- Talpyklos raktas: `banners-{tenant_id}` tvarkomas per `Illuminate\Support\Facades\Cache::forget()`.
- Viešasis atvaizdavimas: `resources/js/Components/Public/FullWidth/PartnersBanner.vue`.
