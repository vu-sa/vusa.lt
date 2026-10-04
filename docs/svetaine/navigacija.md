---
doc_status: reviewed
title: Navigacija
area: navigation
models: [Navigation]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Navigation/NavigationControllerTest.php
  - tests/Feature/Api/Admin/NavigationLinkApiControllerTest.php
  - tests/Feature/SoftDelete/NavigationCascadeTest.php
  - tests/Unit/Services/NavigationServiceTest.php
  - tests/Browser/PublicNavigationHeadTest.php
---

# Navigacija

Navigacija – pagrindinis svetainės viršutinis meniu (antraštė) ir svetainės poraštės nuorodų stulpeliai, vienodi visame viešame portale. Pagrindinė navigacija užtikrina patogų studentų ir svečių judėjimą tarp svarbiausių VU SA sričių, padalinių ir paslaugų.

Skiltis pasiekiama adresu `/mano/navigation`.

## Rekomendacijos {#rekomendacijos}

::: tip
- **Aiški struktūra:** susijusias nuorodas grupuok po jas apibūdinančia skiltimi. Patikrink, ar reikiamą puslapį lengva rasti ir telefone.
- **Trumpi pavadinimai:** rinkis lankytojui suprantamus užrašus, pvz., „Kontaktai“, „Naujienos“, „DUK“.
- **Poraštė:** nuorodas grupuok pagal paskirtį, pvz., informaciją apie VU SA, informaciją studentams, dokumentus ir kontaktus. Nebūtina užpildyti visų keturių galimų stulpelių.
:::

## Kaip tai veikia

Svetainės navigaciją sudaro dvi atskiros zonos, valdomos toje pačioje sistemoje: **Antraštė (Header)** ir **Poraštė (Footer)**.

### Viršutinis meniu (Antraštė)

- **Medžio tipo struktūra:** meniu sudarytas iš pagrindinių šakninių skilčių (pvz., „Apie mus“, „Studentams“, „Atstovavimas“) ir po jomis esančių polapių ar nuorodų.
- **Šakniniai elementai:** atlieka išskleidžiamųjų kategorijų vaidmenį. Jie neturi atskiro internetinio adreso – paspaudus atveriamas jų polapių sąrašas.
- **Pavaldūs meniu punktai:** konkrečios nuorodos į vidinius puslapius, naujienas, kalendorių, dokumentus ar išorinius šaltinius.
- **Vilkimas:** meniu punktų išdėstymą ir hierarchiją galima patogiai keisti pele arba lietimu.

### Poraštės meniu (Poraštė)

- **Stulpelių sistema:** poraštėje rodomi iki **4 stulpelių** su nuorodomis.
- **Stulpelio antraštė:** kiekvienas stulpelis turi savo pavadinimą. Jei nurodomas adresas, antraštė veikia kaip nuoroda; jei laukas tuščias arba įrašyta `#`, ji pateikiama kaip paprastas antraštinis tekstas.
- **Nuorodos stulpelyje:** po kiekviena antrašte pateikiamas plokščias nuorodų sąrašas (pvz., kontaktai, teisinė informacija, svarbios nuorodos).

## Veiksmai

Navigacijos valdymo lange (`/mano/navigation`) viršuje rasi du skirtukus: **Antraštė** ir **Poraštė**.

### Viršutinio meniu tvarkymas

1. **Pridėti šakninę skiltį:** spausk **Pridėti kategoriją**. Įvesk pavadinimą lietuvių ir anglų kalbomis bei pasirink piktogramą. Šakniniam elementui adresas nenurodomas.
2. **Pridėti nuorodą į kategoriją:** prie norimos skilties spausk **+ Pridėti nuorodą**:
   - Įvesk pavadinimą (LT ir EN).
   - Pasirink nuorodos tipą (vidinis puslapis, naujiena, kalendorius, institucija, dokumentas arba tiesioginis išorinis adresas).
   - Išsaugok.
3. **Meniu pertvarkymas:**
   - Nutempk meniu punktą aukštyn arba žemyn, kad pakeistum jo rodymo eilę.
   - Pakeitęs išdėstymą spausk **Išsaugoti tvarką**.
4. **Meniu punkto šalinimas:** punkto meniu pasirink **Ištrinti**. Ištrynus šakninę kategoriją, į šiukšlinę perkeliami ir visi po ja buvę punktai.

### Poraštės meniu tvarkymas

1. Pasirink skirtuką **Poraštė**.
2. **Naujas stulpelis:** spausk **Naujas poraštės stulpelis** (jei jau sukurti 4 stulpeliai, mygtukas tampa neaktyvus, nes pasiektas keturių stulpelių limitas). Nurodyk pavadinimą ir neprivalomą adresą.
3. **Nuorodų pridėjimas:** prie atitinkamo stulpelio spausk **Pridėti nuorodą**, įvesk pavadinimą ir tikslų adresą.
4. **Rikiavimas ir redagavimas:** redaguok stulpelių pavadinimus arba pašalink nebereikalingas nuorodas.

### Šalinimas ir atkūrimas

- Pašalinti navigacijos elementai perkeliami į šiukšlinę.
- Šiukšlinės rodinyje galima atkurti netyčia ištrintą punktą (`navigation.restore`) arba ištrinti jį negrįžtamai (`navigation.forceDelete`).

## Kas ką gali {#teises}

Navigacija daro įtaką visos organizacijos reprezentaciniam įvaizdžiui, todėl jos valdymas yra centralizuotas:

| Veiksmas | Padalinio koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|
| Matyti navigacijos sąrašą | – | ✓ |
| Kurti ir redaguoti meniu punktus | – | ✓ |
| Keisti rikiavimo tvarką | – | ✓ |
| Tvarkyti poraštės stulpelius | – | ✓ |
| Šalinti ir atkurti elementus | – | ✓ |

::: warning Padalinio teisės
Padalinių komunikacijos koordinatoriai negali tiesiogiai redaguoti bendros svetainės navigacijos. Jei padaliniui reikia naujo meniu punkto viršutinėje navigacijoje, kreipkis į Centrinio biuro komunikacijos koordinatorių. Konkretaus padalinio nuorodoms naudok [Greitąsias nuorodas](/svetaine/greitosios-nuorodos).
:::

## Pranešimai ir automatizavimas {#pranesimai}

- **Talpyklos atnaujinimas:** kiekvieną kartą atlikus navigacijos ar poraštės pakeitimą, sistema automatiškai išvalo viešos svetainės navigacijos talpyklą (`NavigationService::clearCache()`). Atnaujintas meniu lankytojams pasirodo iš karto.
- **Kaskadinis trynimas:** pašalinus tėvinį meniu elementą, visi po juo esantys elementai automatiškai perkeliami į šiukšlinę kartu su tėviniu įrašu.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Prieigos teises kontroliuoja `NavigationPolicy`.
- Leidimų išteklius duomenų bazėje vadinasi daugiskaita: `navigations` (ne `navigation`).
- Reikalingi leidimai: `navigations.create.*`, `navigations.read.*`, `navigations.update.*`, `navigations.delete.*`. Šiuos leidimus turi tik Centrinio biuro komunikacijos koordinatoriaus rolė ir pagrindinis administratorius.

### Kaip tai įgyvendinta

- Modelis: `App\Models\Navigation`, naudojantis `SoftDeletes`.
- Vieta nustatoma per atributą `extra_attributes.location` (`header` arba `footer`).
- Poraštės stulpelių skaičiaus apribojimas: `NavigationService::FOOTER_MAX_COLUMNS = 4` užtikrinamas per `NavigationRequest::withValidator()`.
- Valdiklis: `App\Http\Controllers\Admin\NavigationController`.
- Meniu medis generuojamas per `App\Services\NavigationService::getTreeForAdmin()` ir `getFooterTreeForAdmin()`.
- Viešasis atvaizdavimas: `App\Services\NavigationService::getNavigationForPublic()` ir `getFooterNavigationForPublic()`.
