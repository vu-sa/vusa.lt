---
doc_status: reviewed
title: Platforma
area: actionWindow
last_reviewed: 2026-10-28
tests:
  - resources/js/Components/Collection/__tests__/CollectionRowActions.component.test.ts
  - resources/js/Components/Collection/__tests__/CollectionResults.component.test.ts
  - resources/js/Components/Collection/__tests__/CollectionPreviewNavigation.component.test.ts
  - tests/Browser/AdminCollectionPagesTest.php
  - resources/js/Components/Patterns/__tests__/RecordActionMenu.component.test.ts
  - resources/js/Composables/__tests__/useStartFm.test.ts
  - resources/js/Components/__tests__/StartFmPlayer.component.test.ts
  - resources/js/Components/Layouts/__tests__/FormPage.component.test.ts
  - resources/js/Components/Patterns/__tests__/ModelTimestamps.component.test.ts
  - resources/js/Components/Patterns/__tests__/SheetForm.component.test.ts
  - resources/js/Composables/__tests__/useCollectionRecordActions.test.ts
  - tests/Browser/CollectionTrashRefreshTest.php
  - tests/Browser/PostCreateNavigationTest.php
  - tests/Feature/Admin/Content/PageControllerTest.php
  - tests/Feature/Api/Admin/ContentEditorTest.php
  - resources/js/Pages/Admin/People/__tests__/CreateRecordNavigation.component.test.ts
  - resources/js/Composables/__tests__/useContentEditor.component.test.ts
  - tests/Feature/System/SharedInertiaPropsTest.php
  - resources/js/Composables/__tests__/useActionWindowCatalog.test.ts
  - tests/Feature/Search/SearchExperienceTest.php
  - tests/Browser/SearchExperienceTest.php
  - resources/js/Shared/Search/__tests__/SearchExperience.test.ts
  - resources/js/Components/CommandPalette/__tests__/AdminCommandPalette.component.test.ts
  - resources/js/Shared/Search/__tests__/FacetOptions.component.test.ts
  - resources/js/__tests__/designTokens.test.ts
  - resources/js/Components/Patterns/__tests__/StatusBadge.component.test.ts
  - resources/js/Components/Layouts/__tests__/FormPage.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DuplicateDutyWarning.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DuplicateUserWarning.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/Select/__tests__/SearchSelectDialog.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/__tests__/SearchHitRow.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/__tests__/SearchSplitView.component.test.ts
  - resources/js/Composables/__tests__/useListDetailSelection.test.ts
  - resources/js/Components/Layouts/__tests__/CollectionPage.component.test.ts
  - resources/js/Components/Patterns/__tests__/Preview.component.test.ts
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
  - resources/js/Components/Layouts/Shell/__tests__/ShellTopBar.component.test.ts
  - resources/js/Components/Layouts/Shell/__tests__/MobileBottomBar.component.test.ts
  - tests/Unit/Models/TranslatableExpectationsTest.php
  - tests/Feature/System/TranslationIntegrityTest.php
---

# Platforma

Mano VU SA sudaro **darbo sritys** (Mano, ViSAK, Rezervacijos, Svetainė, Organizacija, Sistema).
Kiekviena sritis turi skiltis. Viršutinė juosta leidžia perjungti sritis, o mygtukas **Veiksmai**
atveria leidžiamus veiksmus: fiksuoti posėdį, pranešti apie jo nebuvimą ar kurti įrašus.

<DocScreenshot name="action-window" narrow alt="Langas „Ką norėtum padaryti?“, atidaromas mygtuku „Veiksmai“" caption="Mygtukas „Veiksmai“ viršutinėje juostoje atveria šį langą." />

Lango pasirinkimų „Kaip studentų atstovas“, „Kaip VU SA narys“ ir „Kaip koordinatorius“ galūnės
pritaikomos pagal tavo profilio įvardžius, o jų nesant – pagal vardą. Pavyzdžiui, pasirinkus
„ji / jos“, matysi „Kaip studentų atstovė“ ir „Kaip koordinatorė“.

::: info Ko nematai – to neturi
Sritys, skiltys ir veiksmai rodomi tik tada, kai turi teisę jais naudotis. Jie niekada nerodomi
išjungti. Jei kolega mato skiltį, kurios tu nematai, skiriasi jūsų teisės (žr.
[Teisės ir rolės](/pagrindai/teises)).
:::

## Mygtukai ir klaviatūra {#mygtukai-ir-klaviatura}

<ChangelogNote date="2026-10-02" title="Bendras veiksmų langas ir darbo sričių navigacija">

Vietoje seno administravimo meniu pasirenkama darbo sritis ir jos skiltis. **Veiksmai** rodo
leidžiamus veiksmus; telefono juostose pasiekiami tie patys pagrindiniai darbai.

</ChangelogNote>

Mygtukas **Veiksmai** pažymėtas žymeklio simboliu ir rašomas didžiosiomis raidėmis. Pagrindiniai kūrimo veiksmai išsiskiria ryškia spalva ir didžiosiomis raidėmis. Kiti veiksmai
rašomi įprastai, kad būtų lengviau juos perskaityti. Naršant klaviatūra, aktyvi nuoroda turi
matomą apvadą.

Darbo srities pasirinkimas viršutinėje juostoje yra tokio pat aukščio kaip paieškos laukas. Jutikliniame ekrane mygtukas lieka didesnis, kad būtų patogu paspausti.

Slenkant puslapį, turinys po viršutine juosta suliejamas, kad navigacija ir formos veiksmai liktų aiškiai matomi.
Didelio kontrasto režimu juostos fonas yra nepermatomas.

Sąrašuose įrašo veiksmai matomi tiek eilutėse, tiek lentelėje. Vien piktograma pažymėti mygtukai
turi aiškų pavadinimą ekrano skaitytuvui, o telefone jų paspaudimo vieta yra bent 44 × 44 taškai.
Įrašų rūšys, turinčios šiukšlinę, leidžia atkurti įrašą arba, turint teisę ir nesant susiejimų apribojimų, ištrinti jį visam laikui.

## Paieška ir įrašų pasirinkimas {#paieska-ir-pasirinkimas}

### Rasti skiltį ar įrašą {#bendra-paieska}

Viršutinėje juostoje paspaudus paiešką arba klavišus **⌘/Ctrl + K**, atidaroma komandų paletė.
Joje pateikiamos prieinamos skiltys, veiksmai ir įrašų paieška. Žvaigždute galima prisegti dažnai
naudojamą puslapį – prisegtos nuorodos rodomos paletės pradžioje. Telefone paieška pasiekiama iš navigacijos.

Paletėje rezultatai pateikiami vienu sąrašu: pirmiausia įrašai, kurių vardas ar pavadinimas sutapo, o kiekviena eilutė nurodo įrašo rūšį (pvz., Narys, Pareigybė, Naujiena). **Rodyti visus** nuveda į atitinkamą sąrašą, kuriame galima tikslinti paiešką ir filtrus.
Paieška nesuteikia naujų teisių: kitų įrašų ar jų veiksmų prieiga priklauso nuo tavo pareigų ir rolių.

<ChangelogNote date="2026-10-02" title="Paieška turinyje ir aiškesni filtrai">

Puslapiai ir naujienos randami pagal išsaugotą turinio tekstą. Rezultato ištrauka parodo, kur sutapo paieška. Lietuvių ir anglų tekstuose paieška atpažįsta ir skirtingas žodžio formas. Pagal aktualumą rikiuojami teksto atitikmenys; pasirinktas rikiavimas pagal datą ar pavadinimą taikomas tiesiogiai.

Pirmiausia rodomi atitikmenys paties įrašo varde ar pavadinime. Ieškant žmogaus vardo pirmiausia pateikiamas jo profilis, o pagal pareigybės pavadinimą, pavyzdžiui, „Prezidentė“ – pareigybė. Susijusių žmonių, pareigybių ir institucijų paminėjimai turi mažesnį svorį; dabartinė darbo sritis nekeičia paieškos rezultatų eilės. Sutapęs vardas ar pavadinimas paryškinamas pačioje antraštėje, taip pat viešoje dokumentų paieškoje. Dokumento pavadinimas randamas ir pradėjus rašyti žodį, pavyzdžiui, „įstat“. Kitas sutapimas paryškinamas ten, kur ta reikšmė jau rodoma, pavyzdžiui, dokumento rūšyje. Turinio ištrauka rodoma vietoj trumpo aprašymo, todėl rezultatai rašant nepailgėja; HTML žymos nerodomos.

</ChangelogNote>

### Grįžti prie sąrašo {#sarasai}

Sąrašo paieška ir filtrai veikia tik tos rūšies įrašams. Pakeitus filtrus ar rikiavimą ir
atvėrus įrašą, grįžus atgal sąrašas išlaiko pasirinktą būseną. Mygtukas **Išvalyti filtrus**
praverčia, jei tikėtinas įrašas nerodomas. Prieš kartojant kūrimą verta patikrinti, ar įrašas jau nėra sukurtas.

Filtro parinkties skaičius rodo įrašus, atitinkančius paiešką ir **kitus filtrus**, neįskaitant to filtro pasirinkimo. Pavyzdžiui, pasirinkus vieną padalinį vis dar rodomi kitų padalinių skaičiai pagal pasirinktą kalbą ir paieškos tekstą. Kelios vieno filtro reikšmės praplečia rezultatus, o skirtingi filtrai taikomi kartu. Įrašai gali patekti į kelias parinktis, todėl šie skaičiai nesumuojami. Brūkšnys reiškia, kad skaičius nežinomas.

Ilgesniame parinkčių sąraše ieškomą reikšmę galima įrašyti į filtro paieškos laukelį. Ji ieško ir tarp iš pradžių nematomų parinkčių; pasirinkimai lieka matomi ir juos galima pašalinti. Ši laikina paieška nekeičia pagrindinės frazės, filtrų ar puslapio adreso. Tie patys susitarimai galioja svetainės archyvuose ir susijusių įrašų pasirinkimo languose.

### Peržiūra šalia sąrašo {#perziura}

<ChangelogNote date="2026-10-25" title="Vienoda įrašų peržiūra">

Sąrašai ir paieška naudoja tą pačią įrašo peržiūrą. Peržiūra atsirado narių, naujienų ir renginių sąrašuose, o rodyklių klavišais galima pereiti prie kito įrašo.

</ChangelogNote>

Plačiame ekrane sąrašą galima rodyti kaip **Peržiūrą**: kairėje lieka įrašai, dešinėje – pasirinkto įrašo pagrindiniai duomenys, būsena ir veiksmai. Ši parinktis siūloma tik tuose sąrašuose, kurie turi peržiūrą. Pasirinktas įrašas įrašomas į puslapio adresą, todėl nuoroda atveria tą patį įrašą.

Peržiūroje klavišais **↑** ir **↓** pereinama prie kito įrašo, o **Enter** atveria jo puslapį. Kol rašoma lauke, klavišai lieka laukui. Tokia pati peržiūra rodoma ir susijusių įrašų pasirinkimo languose; ten rodyklėmis galima judėti ir iš paieškos laukelio. Joje matoma tik santrauka: ilgi sąrašai, pvz., institucijos ryšiai, sutrumpinami, o visi duomenys ir jų tvarkymas lieka įrašo puslapyje.

### Pasirinkti susijusį įrašą

Kai forma prašo susieti kitą įrašą, pasirinkimo lange galima ieškoti, filtruoti ir peržiūrėti radinį prieš jį pridedant. Pažymėti įrašai patvirtinami lango apačioje; jei laukas neprivalomas, pasirinkimą galima išvalyti. Telefone peržiūrėjus radinį mygtuku „Atgal į sąrašą“ grįžtama prie rezultatų. Nepasiekiamą išteklių galima peržiūrėti, bet negalima pasirinkti.

Kuriant narį ar pareigybę, perspėjimas apie galimą dublikatą parodo sutapimus ir galimus veiksmus. Tai patarimas: sutampantys vardai savaime neuždraudžia išsaugoti įrašo. Atverti ar sujungti kitą įrašą siūloma tik tada, kai turi teisę jį tvarkyti.

### Formų antraštės {#formu-antrastes}

Redaguojant įrašo pavadinimą, didžioji formos antraštė iškart atspindi rašomą tekstą. Viršutinėje
juostoje lieka išsaugotas įrašo pavadinimas, o kuriant – formos paskirtis, pvz., „Nauja naujiena“.

### Klavišai formose {#formu-klavisai}

**⌘/Ctrl + Enter** išsaugo formą. **Esc** tik išeina iš laukelio, kuriame rašai, – formos puslapis
neuždaromas. Jei atvertas pasirinkimo sąrašas, pirmas **Esc** jį užveria.

### Įrašo datos formose {#formu-datos}

Redaguojant išsaugotą įrašą, mažosios formos lange po laukais rodomos **Sukurta** ir **Atnaujinta** datos su laiku. Jos pateikiamos Vilniaus laiku, tuo pačiu stiliumi kaip pilnos formos šoninėje skiltyje. Kuriant naują įrašą datos dar nerodomos.

### Po sukūrimo {#po-sukurimo}

<ChangelogNote date="2026-10-02" title="Sukurto įrašo atvėrimas">

Atskiroje formoje sukūrus narį, pareigybę, instituciją, registracijos formą, problemą, išteklių ar rolę,
atveriamas naujo įrašo puslapis. Sukūrus naujieną, puslapį, renginį, banerį, greitąją nuorodą,
studijų programą, studijų rinkinį ar meniu punktą, atveriama jo redagavimo forma.

</ChangelogNote>

Jei sukurti įrašą galima, bet jo puslapio peržiūrėti ar redaguoti neleidžiama, atveriamas prieinamas
sąrašas; jei neprieinamas ir jis – **Pradžia**. Sėkmingai sukurtas įrašas išlieka išsaugotas.
Kuriant instituciją ar pareigybę vedlyje arba pridedant susijusį įrašą atvertame puslapyje,
liekama tame pačiame veiksme. Esamų įrašų redagavimo, šalinimo ir atkūrimo eiga nesikeičia.

## Bendrosios platformos galimybės {#bendrosios-galimybes}

Platforma turi bendrų mechanizmų, kurie vienodai veikia visose darbo srityse ir moduliuose:

### Šiukšlinė ir atkūrimas {#siuksline}

Turinio ir konfigūracijos įrašai (naujienos, puslapiai, baneriai, kalendoriaus renginiai, failai, ištekliai) palaiko laikiną ištrynimą:
- Ištrintas įrašas nepašalinamas iš duomenų bazės iš karto – jis perkeliamas į **šiukšlinę**.
- Naudotojai, turintys įrašo trynimo teisę, gali jį **atkurti** mygtuku „Atkurti“.
- Visiškas pašalinimas („Ištrinti visam laikui“) reikalauja super administratoriaus teisės, apsaugančios nuo netyčinio duomenų praradimo.
- Atkūrus arba galutinai ištrynus įrašą, sąrašas atsinaujina automatiškai. Jei atkūrimo veiksmas atidaro įrašą, iškart pereinama į jo puslapį.
- **Operaciniai įrašai** (pvz., **rezervacijos**) į šiukšlinę nekeliami: juos ištrynus, įrašas iš karto negrįžtamai pašalinamas kartu su susijusiais tarpiniais duomenimis.

### Daugiakalbiškumas ir vertimai {#vertimai}

VU SA platforma yra dvikalbė (lietuvių ir anglų k.):
- **Formose su verčiamais laukais** kalbas perjungi LT ir EN valdikliu. Privalomos kalbos ir laukai priklauso nuo įrašo rūšies – vadovaujamasi forma bei jos gidu.
  Kalba perjungiama visai formai, todėl prie laukų pavadinimų kalba nerašoma. Privalomi laukai pažymėti žvaigždute (*), o formos pradžioje esanti pastaba primena, kad kitus galima praleisti.
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
  Tamsią temą galima įjungti pasirinkus savo vardą → **Išvaizda → Tamsi tema**; pasirinkimas išsaugomas.
- **Išvaizda → Prieinamumas** leidžia padidinti tekstą, paryškinti linijas ir pabraukti nuorodas.
- Telefone ir planšetėje galima atlikti tą patį, ką kompiuteryje: telefone mygtukas **Veiksmai** ir
  pranešimai persikelia į apatinę juostą, o niekas nepasiekiama tik užvedus pelę.
- Klaviatūra: **?** parodo sparčiųjų klavišų sąrašą, **/** perkelia į sąrašo paiešką.



## Sąrašo eilutės veiksmai {#eilutes-veiksmai}

Lentelėje vienas eilutės veiksmas rodomas su tekstu: darbo žingsnis (pvz., „Darbotvarkė“), o kai jo nėra – „Redaguoti“ arba „Atidaryti“. Likę atidarymo ir redagavimo veiksmai rodomi piktogramomis su paaiškinimu. Kiti veiksmai, įskaitant ištrynimą, yra meniu „Daugiau veiksmų“ (⋯). Rodomi tik tai eilutei leidžiami veiksmai.

Stulpelį, kurio antraštėje yra rodyklės, galima rikiuoti: paspaudus antraštę tvarka apsiverčia. Tą pačią tvarką galima pasirinkti ir per „Rikiuoti“. Veiksmų stulpelis lieka dešinėje slenkant plačią lentelę. Kai nė viena rodoma eilutė neturi veiksmų, veiksmų stulpelis nerodomas. Meniu telefone atsidaro ekrano apačioje. Ištrintų įrašų atkūrimas ir galutinis ištrynimas taip pat yra ⋯ meniu; kai leidžiama tik atkurti, rodomas mygtukas „Atkurti“.

Peržiūros srityje taip pat lieka nuorodos į susijusius įrašus, o rankiniu būdu atliekamą užduotį galima pažymėti atlikta arba grąžinti į neatliktas. Kol veiksmas vykdomas, tos užduoties veiksmai laikinai neprieinami.

## Techninė informacija {#technine-informacija}

Typesense laukų svoriai ir schemos aprašyti `config/scout.php`; naršyklė gauna patikrintus kolekcijų paieškos profilius. `search:reindex` atkuria kiekvienos kolekcijos schemą, patikrina laukus ir tik tada įjungia tos kolekcijos antrą profilio versiją. Iki atkūrimo naudojami ankstesnės schemos laukai. `TYPESENSE_SEARCH_PROFILE_VERSION=1` ir konfigūracijos podėlio atnaujinimas grąžina ankstesnius paieškos laukus nekeičiant duomenų. Po Redis podėlio išvalymo profilio įjungimas atkuriamas tuo pačiu indeksavimo veiksmu.

Turinio tekstas įtraukiamas po patvirtintų pakeitimų; dinaminiai blokų sąrašai, formų pateikimai ir PDF failų tekstas neišplečiami. Lietuvių ir anglų kamienų laukai naudoja tik tikrus tos kalbos vertimus ar įrašo kalbą. Filtro skaičiavimo ir parinkčių paieškos užklausos išlaiko kolekcijos prieigos raktą ir privalomus pagrindinius filtrus.


::: details Kuriantiems platformą
Spalvos aprašytos kaip kintamieji `resources/css/theme/design-tokens.css` ir
`resources/css/theme/base-tokens.css`: `--brand` / `--brand-fill` – VU SA spalva, `--status-*` –
šešios būsenos, `--cat-1…8` – kategorijos. Taisyklės, kada kurį naudoti, – `.ai/rules/css.md` ir
`.ai/rules/js-pages-admin.md`; kontrastą tikrina Storybook istorija `Patterns/ColourSystem`.
Būsena visada rodoma per `StatusBadge`, nenaudojant atskirų spalvų.
:::

- Spalvų kintamieji abiem temoms ir kategorijų atspalvių atstumai tikrinami `designTokens.test.ts`.
- `StatusBadge` visada rodo žodį ir piktogramą, o spalvą ima iš būsenos vaidmens.
- Stačiakampius kampus, šriftą ir fokuso apvadą naršyklėje tikrina `AdminDesignSurfaceTest`, šviesią
  temą pagal nutylėjimą – `DefaultThemeTest`, prieinamumo nustatymus – `AdminAccessibilityTest`,
  telefono juostą ir sparčiuosius klavišus – `AdminShellTest`, langą **Veiksmai** – `ActionWindowTest`.
- Laikiną ištrynimą (`SoftDeletes`) ir jo elgseną sąrašuose tikrina `SoftDeletableResourcesTest`; visiškas ištrynimas reikalauja `*.forceDelete` teisės, kurią turi tik super administratorius.
- Daugiakalbių laukų elgseną (Spatie `HasTranslations`, `toFullArray()` administravimo formose ir `toArray()` lokalizuotai peržiūrai) bei vertimų vientisumą užtikrina `TranslatableExpectationsTest` ir `TranslationIntegrityTest`.

## START FM {#start-fm}

Studentų radijo gali klausytis pasirinkęs **Klausyti START FM** paskyros meniu, telefono
meniu arba komandų paletėje. Viešoje svetainėje grotuvas atidaromas muzikos natos mygtuku
puslapio apačioje. Pasirinkus radiją, jis pradeda groti iškart.

Grotuve gali pristabdyti transliaciją, keisti garsumą arba nutildyti garsą. Telefone garsumo
valdikliai išskleidžiami garsiakalbio mygtuku. Jei naršyklė neleidžia keisti garsumo,
grotuvas pasiūlo naudoti įrenginio mygtukus. Užvėrus grotuvą transliacija sustabdoma;
pristabdžius grotuvas lieka matomas, o tęsiant klausoma tiesioginės transliacijos.

Kompiuteryje grotuvas rodomas apatiniame dešiniajame kampe. Telefone jis yra virš apatinio
meniu arba formos išsaugojimo juostos. Pereinant tarp tos pačios svetainės puslapių klausymas
tęsiasi. Perkrovus puslapį arba perėjus tarp viešos svetainės ir Mano VU SA, radiją reikia
įjungti iš naujo. Nepavykus prisijungti, grotuve galima bandyti dar kartą.
