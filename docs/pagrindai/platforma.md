---
doc_status: reviewed
title: Platforma
area: actionWindow
last_reviewed: 2026-10-04
tests:
  - tests/Feature/System/SharedInertiaPropsTest.php
  - resources/js/Composables/__tests__/useActionWindowCatalog.test.ts
  - tests/Feature/Search/SearchExperienceTest.php
  - tests/Browser/SearchExperienceTest.php
  - resources/js/Shared/Search/__tests__/SearchExperience.test.ts
  - resources/js/Components/CommandPalette/__tests__/AdminCommandPalette.component.test.ts
  - resources/js/Shared/Search/__tests__/FacetOptions.component.test.ts
  - resources/js/__tests__/designTokens.test.ts
  - resources/js/Components/Patterns/__tests__/StatusBadge.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DuplicateDutyWarning.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DuplicateUserWarning.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/Select/__tests__/SearchSelectDialog.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/__tests__/SearchHitRow.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/__tests__/SearchSplitView.component.test.ts
  - tests/Browser/ActionWindowTest.php
  - resources/js/Components/AdminForms/__tests__/InstitutionForm.component.test.ts
  - resources/js/Components/AdminForms/__tests__/ResourceForm.component.test.ts
  - resources/js/Components/AdminForms/__tests__/UserForm.component.test.ts
  - resources/js/Components/AdminForms/__tests__/StudyProgramForm.component.test.ts
  - tests/Browser/AdminDesignSurfaceTest.php
  - tests/Browser/AdminSearchPickerLayoutTest.php
  - tests/Browser/AdminAccessibilityTest.php
  - tests/Browser/AdminShellTest.php
  - tests/Browser/DefaultThemeTest.php
  - tests/Feature/Admin/SoftDeletableResourcesTest.php
  - tests/Feature/Admin/Search/SearchControllerTest.php
  - tests/Feature/Admin/Search/SearchVisibilityParityTest.php
  - resources/js/Components/Layouts/Shell/__tests__/PaletteField.component.test.ts
  - tests/Unit/Models/TranslatableExpectationsTest.php
  - tests/Feature/System/TranslationIntegrityTest.php
---

# Platforma

Mano VU SA sudaro **darbo sritys** (Mano, ViSAK, Rezervacijos, Svetainė, Organizacija, Sistema).
Kiekviena sritis turi skiltis. Viršutinė juosta leidžia perjungti sritis, o mygtukas **+ Sukurti**
atveria langą su visais tau leidžiamais kūrimo veiksmais.

<DocScreenshot name="action-window" narrow alt="Langas „Ką norėtum padaryti?“, atidaromas mygtuku „+ Sukurti“" caption="Mygtukas „+ Sukurti“ viršutinėje juostoje atveria šį langą." />

Lango pasirinkimų „Kaip studentų atstovas“, „Kaip VU SA narys“ ir „Kaip koordinatorius“ galūnės
pritaikomos pagal tavo profilio įvardžius, o jų nesant – pagal vardą. Pavyzdžiui, pasirinkus
„ji / jos“, matysi „Kaip studentų atstovė“ ir „Kaip koordinatorė“.

::: info Ko nematai – to neturi
Sritys, skiltys ir veiksmai rodomi tik tada, kai turi teisę jais naudotis. Jie niekada nerodomi
išjungti. Jei kolega mato skiltį, kurios tu nematai, skiriasi jūsų teisės (žr.
[Teisės ir rolės](/pagrindai/teises)).
:::

## Mygtukai ir klaviatūra {#mygtukai-ir-klaviatura}

<ChangelogNote version="v3.0" date="2026-10-02" title="Viena kūrimo vieta ir darbo sričių navigacija">

Vietoje seno administravimo meniu rinkis darbo sritį ir jos skiltį. **+ Sukurti** rodo tau leidžiamus kūrimo veiksmus; telefono juostose pasieksi tuos pačius pagrindinius darbus.

</ChangelogNote>

Pagrindiniai kūrimo veiksmai išsiskiria ryškia spalva ir didžiosiomis raidėmis. Kiti veiksmai
rašomi įprastai, kad būtų lengviau juos perskaityti. Naršant klaviatūra, aktyvi nuoroda turi
matomą apvadą.

Darbo srities pasirinkimas viršutinėje juostoje yra tokio pat aukščio kaip paieškos laukas. Jutikliniame ekrane mygtukas lieka didesnis, kad būtų patogu paspausti.

Sąrašuose įrašo veiksmai matomi tiek eilutėse, tiek lentelėje. Vien piktograma pažymėti mygtukai
turi aiškų pavadinimą ekrano skaitytuvui, o telefone jų paspaudimo vieta yra bent 44 × 44 taškai.
Įrašų rūšys, turinčios šiukšlinę, leidžia atkurti įrašą arba, turint teisę ir nesant susiejimų apribojimų, ištrinti jį visam laikui.

## Paieška ir įrašų pasirinkimas {#paieska-ir-pasirinkimas}

### Rasti skiltį ar įrašą {#bendra-paieska}

Viršutinėje juostoje atverk paiešką arba spausk **⌘/Ctrl + K** – atsidarys komandų paletė.
Joje rasi tau prieinamas skiltis, veiksmus ir įrašų paiešką. Žvaigždute prisek dažnai naudojamą
puslapį; prisegtos nuorodos rodomos paletės pradžioje. Telefone paiešką pasieksi iš navigacijos.

Paletėje rezultatai grupuojami pagal įrašo rūšį. **Rodyti visus** nuveda į atitinkamą sąrašą, kuriame gali tikslinti paiešką ir filtrus.
Paieška nesuteikia naujų teisių: kitų įrašų ar jų veiksmų prieiga priklauso nuo tavo pareigų ir rolių.

<ChangelogNote version="v3.0" date="2026-10-02" title="Paieška turinyje ir aiškesni filtrai">

Puslapius ir naujienas rasi pagal išsaugotą turinio tekstą. Rezultato ištrauka parodo, kur sutapo paieška. Lietuvių ir anglų tekstuose paieška atpažįsta ir skirtingas žodžio formas. Pagal aktualumą rikiuojami teksto atitikmenys; pasirinktą rikiavimą pagal datą ar pavadinimą taikysi tiesiogiai.

Pirmiausia rodomi atitikmenys paties įrašo varde ar pavadinime. Ieškodamas žmogaus vardo pirmiau rasi jo profilį, o pareigybės pavadinimo, pavyzdžiui, „Prezidentė“ – pareigybę. Susijusių žmonių, pareigybių ir institucijų paminėjimai turi mažesnį svorį; dabartinė darbo sritis nekeičia paieškos rezultatų eilės. Sutapęs vardas ar pavadinimas paryškinamas pačioje antraštėje, taip pat viešoje dokumentų paieškoje. Dokumento pavadinimą rasi ir pradėjęs rašyti žodį, pavyzdžiui, „įstat“. Trumpa turinio ištrauka pateikiama mažesniu šriftu tik tada, kai nekartoja vardo ar pavadinimo; HTML žymos nerodomos.

</ChangelogNote>

### Grįžti prie sąrašo {#sarasai}

Sąrašo paieška ir filtrai veikia tik tos rūšies įrašams. Pakeitęs filtrus ar rikiavimą ir
atvėręs įrašą, grįžk atgal – sąrašas išlaiko tavo pasirinktą būklę. **Išvalyti filtrus** naudok,
jei tikėtino įrašo nematai. Prieš kartodamas kūrimą patikrink, ar jis jau nėra sąraše.

Filtro parinkties skaičius rodo įrašus, atitinkančius paiešką ir **kitus filtrus**, neįskaitant to filtro pasirinkimo. Pavyzdžiui, pasirinkęs vieną padalinį vis dar matai kitų padalinių skaičius pagal pasirinktą kalbą ir paieškos tekstą. Kelios vieno filtro reikšmės praplečia rezultatus, o skirtingi filtrai taikomi kartu. Įrašai gali patekti į kelias parinktis, todėl skaičių nesudėk. Brūkšnys reiškia, kad skaičius nežinomas.

Ilgesniame parinkčių sąraše įrašyk ieškomą reikšmę į filtro paiešką. Ji ieško ir tarp iš pradžių nematomų parinkčių; tavo pasirinkimai lieka matomi ir juos gali pašalinti. Ši laikina paieška nekeičia pagrindinės frazės, filtrų ar puslapio adreso. Tie patys susitarimai galioja svetainės archyvuose ir susijusių įrašų pasirinkimo languose.

### Pasirinkti susijusį įrašą

Kai forma prašo susieti kitą įrašą, pasirinkimo lange gali ieškoti, filtruoti ir peržiūrėti radinį prieš jį pridėdamas. Pažymėtus įrašus patvirtini lango apačioje; jei laukas neprivalomas, pasirinkimą gali išvalyti. Telefone peržiūrėjęs radinį mygtuku „Atgal į sąrašą“ grįši prie rezultatų. Nepasiekiamą išteklių gali peržiūrėti, bet negali pasirinkti.

Kuriant narį ar pareigybę, perspėjimas apie galimą dublikatą parodo sutapimus ir galimus veiksmus. Tai patarimas: sutampantys vardai savaime neuždraudžia išsaugoti įrašo. Atverti ar sujungti kitą įrašą siūloma tik tada, kai turi teisę jį tvarkyti.

### Formų antraštės {#formu-antrastes}

Redaguojant įrašo pavadinimą, didžioji formos antraštė iškart atspindi rašomą tekstą. Viršutinėje
juostoje lieka išsaugotas įrašo pavadinimas, o kuriant – formos paskirtis, pvz., „Nauja naujiena“.

## Bendrosios platformos galimybės {#bendrosios-galimybes}

Platforma turi bendrų mechanizmų, kurie vienodai veikia visose darbo srityse ir moduliuose:

### Šiukšlinė ir atkūrimas {#siuksline}

Turinio ir konfigūracijos įrašai (naujienos, puslapiai, baneriai, kalendoriaus renginiai, failai, ištekliai) palaiko laikiną ištrynimą:
- Ištrintas įrašas nepašalinamas iš duomenų bazės iš karto – jis perkeliamas į **šiukšlinę**.
- Naudotojai, turintys įrašo trynimo teisę, gali jį **atkurti** mygtuku „Atkurti“.
- Visiškas pašalinimas („Ištrinti visam laikui“) reikalauja super administratoriaus teisės, apsaugančios nuo netyčinio duomenų praradimo.
- **Operaciniai įrašai** (pvz., **rezervacijos**) į šiukšlinę nekeliami: juos ištrynus, įrašas iš karto negrįžtamai pašalinamas kartu su susijusiais tarpiniais duomenimis.

### Daugiakalbiškumas ir vertimai {#vertimai}

VU SA platforma yra dvikalbė (lietuvių ir anglų k.):
- **Formose su verčiamais laukais** kalbas perjungi LT ir EN valdikliu. Privalomos kalbos ir laukai priklauso nuo įrašo rūšies – vadovaukis forma bei jos gidu.
- **Verčiamų laukų peržiūroje** rodoma pasirinkta kalba; trūkstamas vertimas gali būti pakeičiamas atsargine kalba. Puslapiai ir naujienos turi [atskirus susietus įrašus kiekvienai kalbai](/svetaine/puslapiai#kalbu-versijos), todėl jų vertimas savaime nesukuriamas.

## Spalvos, ženklai ir formos {#spalvos-zenklai-ir-formos}

Mano VU SA turi atrodyti ramiai ir aiškiai: popieriaus spalvos fonas, tamsus tekstas, VU SA raudona
(tamsioje temoje – gintarinė), kampai stačiakampiai, o sritis skiria plonos linijos, ne rėmeliai ir
šešėliai. Kiekvienas ekranas pirmiausia atsako, *kas laukia tavęs*, ir tik tada – *kur gali eiti*.

Spalva platformoje visada ką nors reiškia, o ką – nusako **forma**:

| Ką matai | Ką tai reiškia | Pavyzdys |
|---|---|---|
| **Vientisas raudonas (gintarinis) mygtukas** | Vienas pagrindinis veiksmas toje ekrano dalyje arba „tu esi čia“ | **+ SUKURTI**, **IŠSAUGOTI** |
| **Spalvotas ženkliukas su žodžiu ir piktograma** | Būsena | „Vėluoja“, „Patvirtinta“ |
| **Mažas spalvotas taškas ar brūkšnelis** | Kategorija: įrašo tipas, renginio tipas, padalinys, grafiko serija | Posėdžio tipo žymė kalendoriuje |

Raudona (VU SA spalva) niekada nereiškia būsenos, o būsenos spalva niekada nėra vienintelis
ženklas – šalia visada yra žodis ir piktograma.

### Būsenos {#busenos}

| Spalva | Kada naudojama | Pavyzdžiai |
|---|---|---|
| Pilka | Juodraštis, atšaukta, archyvuota | juodraštis, atšaukta |
| Mėlyna | Pateikta arba artėja | pateikta, artėjantis posėdis |
| Violetinė | Vyksta | vykdoma |
| Gintarinė | Reikia tavo veiksmo arba trūksta informacijos | laukia sprendimo, neišsaugoti pakeitimai |
| Žalia | Atlikta, patvirtinta | atlikta, patvirtinta |
| Raudona | Vėluoja, atmesta, nepavyko | vėluoja, atmesta |

Kai viskas gerai, ženkliuko dažniausiai nėra – rodoma tik tai, į ką verta atkreipti dėmesį.

### Didžiosios raidės ir kampai

- **Didžiosiomis raidėmis** rašomi tik pagrindiniai kūrimo mygtukai, skilčių pavadinimai, formų laukų
  pavadinimai, virš antraščių esantys trumpi užrašai (pvz., „VISAK · POSĖDŽIAI“) ir skirtukai. Visi
  kiti mygtukai, filtrai ir būsenos rašomi įprastai.
- **Kampai visada statūs.** Rėmelis aplink ką nors reiškia objektą, su kuriuo gali ką nors daryti;
  kortelių kortelėse nebūna.
- **Grafikuose** (posėdžių ir pareigybių laiko juostose) tamsios juostos – tikri duomenys, pilkos –
  praeitis, gintarinės – tai, kas dar neišsaugota arba reikalauja dėmesio.

### Tema, dydis ir įrenginiai

- Platforma pagal nutylėjimą atsidaro **šviesia tema**, net jei tavo įrenginys nustatytas tamsiai.
  Tamsią temą įjungsi paspaudęs savo vardą → **Išvaizda → Tamsi tema**; pasirinkimas išsaugomas.
- **Išvaizda → Prieinamumas** leidžia padidinti tekstą, paryškinti linijas ir pabraukti nuorodas.
- Telefone ir planšetėje galima atlikti tą patį, ką kompiuteryje: telefone mygtukas **+ Sukurti** ir
  pranešimai persikelia į apatinę juostą, o niekas nepasiekiama tik užvedus pelę.
- Klaviatūra: **?** parodo sparčiųjų klavišų sąrašą, **/** perkelia į sąrašo paiešką.



## Techninė informacija {#technine-informacija}

Typesense laukų svoriai ir schemos aprašyti `config/scout.php`; naršyklė gauna patikrintus kolekcijų paieškos profilius. `search:reindex` atkuria kiekvienos kolekcijos schemą, patikrina laukus ir tik tada įjungia tos kolekcijos antrą profilio versiją. Iki atkūrimo naudojami ankstesnės schemos laukai. `TYPESENSE_SEARCH_PROFILE_VERSION=1` ir konfigūracijos podėlio atnaujinimas grąžina ankstesnius paieškos laukus nekeičiant duomenų. Po Redis podėlio išvalymo profilio įjungimą atkurk tuo pačiu indeksavimo veiksmu.

Turinio tekstas įtraukiamas po patvirtintų pakeitimų; dinaminiai blokų sąrašai, formų pateikimai ir PDF failų tekstas neišplečiami. Lietuvių ir anglų kamienų laukai naudoja tik tikrus tos kalbos vertimus ar įrašo kalbą. Filtro skaičiavimo ir parinkčių paieškos užklausos išlaiko kolekcijos prieigos raktą ir privalomus pagrindinius filtrus.


::: details Kuriantiems platformą
Spalvos aprašytos kaip kintamieji `resources/css/theme/design-tokens.css` ir
`resources/css/theme/base-tokens.css`: `--brand` / `--brand-fill` – VU SA spalva, `--status-*` –
šešios būsenos, `--cat-1…8` – kategorijos. Taisyklės, kada kurį naudoti, – `.ai/rules/css.md` ir
`.ai/rules/js-pages-admin.md`; kontrastą tikrina Storybook istorija `Patterns/ColourSystem`.
Būseną visada rodyk per `StatusBadge`, o ne savo spalvomis.
:::

- Spalvų kintamieji abiem temoms ir kategorijų atspalvių atstumai tikrinami `designTokens.test.ts`.
- `StatusBadge` visada rodo žodį ir piktogramą, o spalvą ima iš būsenos vaidmens.
- Stačiakampius kampus, šriftą ir fokuso apvadą naršyklėje tikrina `AdminDesignSurfaceTest`, šviesią
  temą pagal nutylėjimą – `DefaultThemeTest`, prieinamumo nustatymus – `AdminAccessibilityTest`,
  telefono juostą ir sparčiuosius klavišus – `AdminShellTest`, langą **+ Sukurti** – `ActionWindowTest`.
- Laikiną ištrynimą (`SoftDeletes`) ir jo elgseną sąrašuose tikrina `SoftDeletableResourcesTest`; visiškas ištrynimas reikalauja `*.forceDelete` teisės, kurią turi tik super administratorius.
- Daugiakalbių laukų elgseną (Spatie `HasTranslations`, `toFullArray()` administravimo formose ir `toArray()` lokalizuotai peržiūrai) bei vertimų vientisumą užtikrina `TranslatableExpectationsTest` ir `TranslationIntegrityTest`.
